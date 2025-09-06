<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

$sql = "SELECT id_habitacion, numero, tipo, estado, personas, precio
        FROM Habitacion
        ORDER BY numero";
$res = $mysqli->query($sql);

if(!$res){
  echo '<option value="">(Error al cargar habitaciones)</option>';
  exit;
}

echo '<option value="">-- Selecciona habitación --</option>';

while($h = $res->fetch_assoc()){
  $id   = (int)$h['id_habitacion'];
  $num  = htmlspecialchars((string)$h['numero'], ENT_QUOTES, 'UTF-8');
  $tipo = htmlspecialchars((string)$h['tipo'], ENT_QUOTES, 'UTF-8');
  $cap  = (int)($h['personas'] ?? 0);
  $prc  = (float)($h['precio'] ?? 0);

  $label = "#{$num} · {$tipo} ({$cap} pax) · $".number_format($prc,2);
  $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');

  echo "<option value=\"{$id}\" data-tipo=\"{$tipo}\" data-personas=\"{$cap}\" data-precio=\"{$prc}\">{$label}</option>";
}