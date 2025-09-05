<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

$nombre  = trim($_POST['nombre'] ?? '');
$apPat   = trim($_POST['apellido_paterno'] ?? '');
$apMat   = trim($_POST['apellido_materno'] ?? '');
$tel     = trim($_POST['telefono'] ?? '');
$correo  = trim($_POST['correo'] ?? '');

if ($nombre==='' || $apPat==='' || $tel==='') {
  http_response_code(400); exit('Faltan campos obligatorios');
}

$stmt = $mysqli->prepare("INSERT INTO Cliente (nombre, apellido_paterno, apellido_materno, correo, telefono) VALUES (?,?,?,?,?)");
$stmt->bind_param('sssss', $nombre, $apPat, $apMat, $correo, $tel);

if($stmt->execute()){
  http_response_code(200); echo 'OK';
}else{
  http_response_code(500); echo 'Error al insertar';
}