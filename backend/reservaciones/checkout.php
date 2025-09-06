<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'msg'=>'Método no permitido']);
  exit;
}

// Validar id
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'ID inválido']);
  exit;
}

// 1) Marcar hora_salida si aún no tiene salida
$upd = $mysqli->prepare("
  UPDATE CheckInOut cio
  JOIN Reservacion r ON r.id_reservacion = cio.id_reservacion
  SET cio.hora_salida = NOW()
  WHERE cio.id_reservacion = ? AND cio.hora_salida IS NULL
");
$upd->bind_param('i', $id);
$upd->execute();

if ($upd->affected_rows <= 0) {
  // No había fila pendiente (ya tenía salida o no existe)
  echo json_encode(['ok'=>false,'msg'=>'La reservación ya tenía check-out o no existe']);
  exit;
}
$upd->close();

// 2) Poner la habitación en "Disponible"
$upd2 = $mysqli->prepare("
  UPDATE Habitacion h
  JOIN Reservacion r ON r.id_habitacion = h.id_habitacion
  SET h.estado = 'Disponible'
  WHERE r.id_reservacion = ?
");
$upd2->bind_param('i', $id);
$upd2->execute();
$upd2->close();

echo json_encode(['ok'=>true,'msg'=>'Check-out registrado']);