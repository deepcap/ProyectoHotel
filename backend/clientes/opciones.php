<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

$sql = "SELECT id_cliente, nombre, apellido_paterno, apellido_materno, telefono, correo
        FROM Cliente
        ORDER BY nombre, apellido_paterno";
$res = $mysqli->query($sql);

if(!$res){
  echo '<option value="">(Error al cargar clientes)</option>';
  exit;
}

echo '<option value="">-- Selecciona cliente --</option>';

while($row = $res->fetch_assoc()){
  $id  = (int)$row['id_cliente'];
  $nom = trim(($row['nombre']??'').' '.($row['apellido_paterno']??'').' '.($row['apellido_materno']??''));
  $nom = htmlspecialchars($nom, ENT_QUOTES, 'UTF-8');
  $tel = htmlspecialchars((string)($row['telefono']??''), ENT_QUOTES, 'UTF-8');
  $cor = htmlspecialchars((string)($row['correo']??''), ENT_QUOTES, 'UTF-8');

  $meta = [];
  if($tel!=='') $meta[] = $tel;
  if($cor!=='') $meta[] = $cor;
  $suffix = $meta ? ' — '.implode(' · ',$meta) : '';

  echo "<option value=\"{$id}\">{$nom}{$suffix}</option>";
}