<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if($id<=0){ http_response_code(400); echo json_encode(['error'=>'id']); exit; }

$stmt = $mysqli->prepare("SELECT id_cliente, nombre, apellido_paterno, apellido_materno, telefono, correo FROM Cliente WHERE id_cliente=?");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if($row = $res->fetch_assoc()){
  echo json_encode($row);
}else{
  http_response_code(404); echo json_encode(['error'=>'no encontrado']);
}