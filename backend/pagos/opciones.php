<?php
// backend/pagos/opciones.php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');

ini_set('display_errors','1');
error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

// Si tu tabla es TipoPago(id_tipo_pago, metodo) –ajusta nombres si difieren
$sql = "SELECT id_tipo_pago, metodo FROM TipoPago ORDER BY id_tipo_pago ASC";
$res = $mysqli->query($sql);

if (!$res) {
  echo '<option value="">(Error al cargar métodos)</option>';
  exit;
}

echo '<option value="">-- Selecciona método --</option>';

while ($row = $res->fetch_assoc()) {
  $id  = (int)$row['id_tipo_pago'];
  $txt = htmlspecialchars((string)$row['metodo'], ENT_QUOTES, 'UTF-8');
  // data-metodo por si luego lo quieres leer sin pedir de nuevo
  echo "<option value=\"{$id}\" data-metodo=\"{$txt}\">{$txt}</option>";
}