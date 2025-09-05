<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'ID inválido']);
  exit;
}

// 1) ¿Tiene reservaciones?
$chk = $mysqli->prepare("SELECT COUNT(*) AS n FROM Reservacion WHERE id_cliente=?");
$chk->bind_param('i', $id);
$chk->execute();
$rc = $chk->get_result()->fetch_assoc();
if (($rc['n'] ?? 0) > 0) {
  http_response_code(409); // conflicto
  echo json_encode([
    'ok'=>false,
    'msg'=>"No se puede eliminar: el cliente tiene {$rc['n']} reservación(es) asociada(s)."
  ]);
  exit;
}

// 2) Elimina
$stmt = $mysqli->prepare("DELETE FROM Cliente WHERE id_cliente=?");
$stmt->bind_param('i', $id);

try {
  $stmt->execute();
  if ($stmt->affected_rows > 0) {
    echo json_encode(['ok'=>true,'msg'=>'Cliente eliminado']);
  } else {
    http_response_code(404);
    echo json_encode(['ok'=>false,'msg'=>'Cliente no encontrado']);
  }
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'msg'=>'Error al eliminar']);
}