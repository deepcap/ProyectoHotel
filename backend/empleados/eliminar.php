<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); exit('Método no permitido'); }
$id=(int)($_POST['id_empleado']??0);
if($id<=0){ http_response_code(422); exit('id_empleado inválido'); }

$stmt=$mysqli->prepare('DELETE FROM Empleado WHERE id_empleado=?');
$stmt->bind_param('i',$id);
if(!$stmt->execute()){ http_response_code(500); exit('Error al eliminar'); }
$stmt->close();