<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);
date_default_timezone_set('America/Mexico_City');

require __DIR__ . '/../config/db.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false,'msg'=>'Método no permitido']); exit;
}

// Validar ID
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit;
}

// Verificar existencia
$st = $mysqli->prepare("SELECT id_habitacion FROM Reservacion WHERE id_reservacion=?");
$st->bind_param('i',$id); $st->execute(); $st->bind_result($id_hab);
if (!$st->fetch()) { $st->close(); echo json_encode(['ok'=>false,'msg'=>'Reservación no existe']); exit; }
$st->close();

$mysqli->begin_transaction();
try {
  // 1) Si la reservación está en curso (sin salida), liberar la habitación
  $q = $mysqli->prepare("SELECT hora_salida FROM CheckInOut WHERE id_reservacion=?");
  $q->bind_param('i',$id); $q->execute(); $q->bind_result($salida); $q->fetch(); $q->close();
  if ($salida === null) {
    $updH = $mysqli->prepare("UPDATE Habitacion SET estado='Disponible' WHERE id_habitacion=?");
    $updH->bind_param('i',$id_hab); $updH->execute(); $updH->close();
  }

  // 2) Borrar cobros ligados a tickets de esta reservación
  $delCobros = $mysqli->prepare("
    DELETE c FROM Cobro c
    JOIN Ticket t ON t.id_ticket = c.id_ticket
    WHERE t.id_reservacion = ?
  ");
  $delCobros->bind_param('i',$id); $delCobros->execute(); $delCobros->close();

  // 3) Borrar tickets de la reservación
  $delTickets = $mysqli->prepare("DELETE FROM Ticket WHERE id_reservacion=?");
  $delTickets->bind_param('i',$id); $delTickets->execute(); $delTickets->close();

  // 4) Desligar servicios asociados (conservar histórico)
  $updSrv = $mysqli->prepare("UPDATE Servicio SET id_reservacion=NULL WHERE id_reservacion=?");
  $updSrv->bind_param('i',$id); $updSrv->execute(); $updSrv->close();

  // 5) Borrar registro de CheckInOut
  $delCio = $mysqli->prepare("DELETE FROM CheckInOut WHERE id_reservacion=?");
  $delCio->bind_param('i',$id); $delCio->execute(); $delCio->close();

  // 6) Borrar la reservación
  $delRes = $mysqli->prepare("DELETE FROM Reservacion WHERE id_reservacion=?");
  $delRes->bind_param('i',$id); $delRes->execute(); $delRes->close();

  $mysqli->commit();
  echo json_encode(['ok'=>true,'msg'=>'Reservación eliminada']);
} catch (Throwable $e) {
  $mysqli->rollback();
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error al eliminar: '.$e->getMessage()]);
}