<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);
date_default_timezone_set('America/Mexico_City');

require __DIR__ . '/../config/db.php';

/*
  Espera (POST):
    - id_cliente     (int)
    - id_habitacion  (int)
    - check_in       (YYYY-MM-DD)
    - check_out      (YYYY-MM-DD)
    - notas          (opcional)
*/

// --- Validar método ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'msg'=>'Método no permitido']);
  exit;
}

// --- Tomar y sanear entradas ---
$id_cliente    = isset($_POST['id_cliente'])    ? (int)$_POST['id_cliente']    : 0;
$id_habitacion = isset($_POST['id_habitacion']) ? (int)$_POST['id_habitacion'] : 0;
$check_in_raw  = trim($_POST['check_in']  ?? '');
$check_out_raw = trim($_POST['check_out'] ?? '');
$notas         = trim($_POST['notas']      ?? '');

// Validar básicos
if ($id_cliente <= 0 || $id_habitacion <= 0 || $check_in_raw === '' || $check_out_raw === '') {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Faltan datos obligatorios']);
  exit;
}

// Validar formato de fecha
$re = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($re, $check_in_raw) || !preg_match($re, $check_out_raw)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Formato de fecha inválido']);
  exit;
}

// Asegurar que check_out > check_in
$inDT  = new DateTime($check_in_raw);
$outDT = new DateTime($check_out_raw);
if ($outDT <= $inDT) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'La fecha de salida debe ser posterior a la entrada']);
  exit;
}

// Definir horas “estándar” de operación (puedes ajustar)
$ciDT = new DateTime($check_in_raw . ' 15:00:00');  // 3pm
$coDT = new DateTime($check_out_raw . ' 11:00:00'); // 11am

// 1) Verificar que cliente y habitación existan
$chkQ = $mysqli->prepare("SELECT COUNT(*) FROM Cliente WHERE id_cliente=?");
$chkQ->bind_param('i',$id_cliente);
$chkQ->execute(); $chkQ->bind_result($cntC); $chkQ->fetch(); $chkQ->close();
if ($cntC == 0) {
  http_response_code(404);
  echo json_encode(['ok'=>false,'msg'=>'Cliente no encontrado']);
  exit;
}

$chkH = $mysqli->prepare("SELECT COUNT(*) FROM Habitacion WHERE id_habitacion=?");
$chkH->bind_param('i',$id_habitacion);
$chkH->execute(); $chkH->bind_result($cntH); $chkH->fetch(); $chkH->close();
if ($cntH == 0) {
  http_response_code(404);
  echo json_encode(['ok'=>false,'msg'=>'Habitación no encontrada']);
  exit;
}

// 2) Verificar DISPONIBILIDAD (no traslape)
//   Traslapa si: (entrada_exist < salida_nueva) AND (COALESCE(salida_exist, +inf) > entrada_nueva)
$dis = $mysqli->prepare("
  SELECT COUNT(*)
  FROM CheckInOut cio
  JOIN Reservacion r ON r.id_reservacion = cio.id_reservacion
  WHERE r.id_habitacion = ?
    AND cio.hora_entrada < ?
    AND COALESCE(cio.hora_salida, '9999-12-31 23:59:59') > ?
");
$coStr = $coDT->format('Y-m-d H:i:s');
$ciStr = $ciDT->format('Y-m-d H:i:s');
$dis->bind_param('iss', $id_habitacion, $coStr, $ciStr);
$dis->execute(); $dis->bind_result($ocupadas); $dis->fetch(); $dis->close();

if ($ocupadas > 0) {
  http_response_code(409);
  echo json_encode(['ok'=>false,'msg'=>'La habitación no está disponible en ese rango']);
  exit;
}

// 3) Transacción: insertar Reservacion + CheckInOut
$mysqli->begin_transaction();

try {
  // a) Crear reservación (la fecha_reservacion = hoy)
  $hoy = (new DateTime('now'))->format('Y-m-d');
  $insR = $mysqli->prepare("
    INSERT INTO Reservacion (fecha_reservacion, id_cliente, id_habitacion)
    VALUES (?, ?, ?)
  ");
  $insR->bind_param('sii', $hoy, $id_cliente, $id_habitacion);
  if (!$insR->execute()) throw new Exception('No se pudo crear la reservación');
  $id_reservacion = (int)$mysqli->insert_id;
  $insR->close();

  // b) Crear CheckInOut con hora_entrada planeada y sin salida (se registrará al hacer Check-out)
  $cioIn  = $ciDT->format('Y-m-d H:i:s');
  $insCIO = $mysqli->prepare("
    INSERT INTO CheckInOut (hora_entrada, hora_salida, id_reservacion)
    VALUES (?, NULL, ?)
  ");
  $insCIO->bind_param('si', $cioIn, $id_reservacion);
  if (!$insCIO->execute()) throw new Exception('No se pudo registrar la entrada');
  $insCIO->close();

  // c) (Opcional) Si el check-in es HOY o ya pasó, marcar la habitación como "Ocupada".
  $hoyMidnight = new DateTime(date('Y-m-d') . ' 00:00:00');
  if ($ciDT <= $hoyMidnight) {
    $updH = $mysqli->prepare("UPDATE Habitacion SET estado='Ocupada' WHERE id_habitacion=?");
    $updH->bind_param('i',$id_habitacion);
    $updH->execute();
    $updH->close();
  }

  $mysqli->commit();

  echo json_encode([
    'ok'  => true,
    'msg' => 'Reservación creada',
    'id'  => $id_reservacion
  ]);
} catch (Throwable $e) {
  $mysqli->rollback();
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error al crear la reservación']);
}