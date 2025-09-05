<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); exit('Método no permitido'); }

$id = (int)($_POST['id_habitacion'] ?? 0);
$numero = trim($_POST['numero'] ?? '');
$precio = trim($_POST['precio'] ?? '');
$tipo   = trim($_POST['tipo']   ?? '');
$estado = trim($_POST['estado'] ?? '');
$pers   = trim($_POST['personas'] ?? '');

$err=[];
if($id<=0) $err[]='ID inválido';
if($numero===''||!ctype_digit($numero)) $err[]='Número inválido';
if($precio===''||!is_numeric($precio)) $err[]='Precio inválido';
if($tipo==='') $err[]='Tipo requerido';
if($estado==='') $err[]='Estado requerido';
if($pers===''||!ctype_digit($pers)) $err[]='Capacidad inválida';
if($err){ http_response_code(422); exit(implode("\n",$err)); }

$num=(int)$numero; $pre=(float)$precio; $per=(int)$pers;

$sql="UPDATE Habitacion SET numero_habitacion=?, precio=?, tipo_habitacion=?, estado=?, cantidad_personas=? WHERE id_habitacion=?";
$stmt=$mysqli->prepare($sql);
$stmt->bind_param('idssii',$num,$pre,$tipo,$estado,$per,$id);
if(!$stmt->execute()){ http_response_code(500); exit('Error al actualizar'); }
$stmt->close();