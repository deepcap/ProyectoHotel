<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
function out($ok,$msg){ echo json_encode(['ok'=>$ok,'msg'=>$msg], JSON_UNESCAPED_UNICODE); exit; }
if($_SERVER['REQUEST_METHOD']!=='POST') out(false,'Método no permitido');

$id = (int)($_POST['id_reservacion'] ?? 0);
if($id<=0) out(false,'ID inválido');

$mysqli->begin_transaction();
try {
  // Marca hora_entrada si está NULL
  $st = $mysqli->prepare("UPDATE CheckInOut SET hora_entrada = COALESCE(hora_entrada, NOW()) WHERE id_reservacion=?");
  $st->bind_param('i',$id);
  $st->execute();

  // Opcional: poner habitación en Ocupada
  $st = $mysqli->prepare("UPDATE Habitacion h
                          JOIN Reservacion r ON r.id_habitacion = h.id_habitacion
                          SET h.estado='Ocupada'
                          WHERE r.id_reservacion=?");
  $st->bind_param('i',$id);
  $st->execute();

  $mysqli->commit();
  out(true,'Check-in marcado');
} catch(Throwable $e){
  $mysqli->rollback();
  out(false,'Error: '.$e->getMessage());
}