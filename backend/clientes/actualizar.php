<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

$id     = (int)($_POST['id'] ?? 0);
$nombre = trim($_POST['nombre'] ?? '');
$apPat  = trim($_POST['apellido_paterno'] ?? '');
$apMat  = trim($_POST['apellido_materno'] ?? '');
$tel    = trim($_POST['telefono'] ?? '');
$correo = trim($_POST['correo'] ?? '');

if($id<=0 || $nombre==='' || $apPat==='' || $tel===''){
  http_response_code(400); exit('Datos inválidos');
}

$stmt = $mysqli->prepare("UPDATE Cliente SET nombre=?, apellido_paterno=?, apellido_materno=?, correo=?, telefono=? WHERE id_cliente=?");
$stmt->bind_param('sssssi', $nombre, $apPat, $apMat, $correo, $tel, $id);

if($stmt->execute()){
  http_response_code(200); echo 'OK';
}else{
  http_response_code(500); echo 'Error al actualizar';
}