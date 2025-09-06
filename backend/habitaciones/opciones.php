<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

/**
 * Este script NO asume nombres fijos de columnas.
 * Lee todos los campos (*) y luego detecta el nombre real de:
 * - id_habitacion
 * - numero (o num_habitacion / numero_habitacion / numero_hab)
 * - tipo (o tipo_habitacion)
 * - personas (o capacidad / cantidad_personas / pax)
 * - precio (o costo / tarifa / precio_noche)
 */

$sql = "SELECT * FROM Habitacion ORDER BY id_habitacion ASC";
$res = $mysqli->query($sql);

if (!$res) {
  http_response_code(500);
  echo '<option value="">(Error al consultar Habitacion)</option>';
  exit;
}

if ($res->num_rows === 0) {
  echo '<option value="">(No hay habitaciones)</option>';
  exit;
}

// Detecta nombres reales de columnas mirando la 1ª fila
$first = $res->fetch_assoc();
$cols  = array_change_key_case($first, CASE_LOWER); // keys en minúscula

// helpers para tomar el primer nombre que exista
$findCol = function(array $candidatos) use ($cols) {
  foreach ($candidatos as $c) {
    if (array_key_exists(strtolower($c), $cols)) return $c;
  }
  return null;
};

$colId       = $findCol(['id_habitacion', 'idhabitacion', 'id']);
$colNumero   = $findCol(['numero','num_habitacion','numero_habitacion','numero_hab','hab_numero']);
$colTipo     = $findCol(['tipo','tipo_habitacion']);
$colPersonas = $findCol(['personas','capacidad','cantidad_personas','pax']);
$colPrecio   = $findCol(['precio','costo','tarifa','precio_noche']);

if (!$colId) {
  http_response_code(500);
  echo '<option value="">(No se encontró columna ID en Habitacion)</option>';
  exit;
}

// volvemos a posicionar el puntero al inicio para iterar todas las filas
$res->data_seek(0);

echo '<option value="">Selecciona una habitación</option>';

while ($row = $res->fetch_assoc()) {
  // lee usando el nombre detectado (con fallback seguro)
  $id  = isset($row[$colId])       ? (int)$row[$colId]       : 0;
  $num = $colNumero   ? (string)$row[$colNumero]   : '';
  $tip = $colTipo     ? (string)$row[$colTipo]     : '';
  $per = $colPersonas ? (int)$row[$colPersonas]    : 0;
  $pre = $colPrecio   ? (float)$row[$colPrecio]    : 0.0;

  // etiqueta legible
  $etqNum = $num !== '' ? "#{$num}" : "ID {$id}";
  $label  = trim($etqNum . ' — ' . ($tip ?: 'Tipo?') . ' (' . ($per ?: '?') . ' pax)');

  // sanitiza
  $label = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
  $tip   = htmlspecialchars($tip,   ENT_QUOTES, 'UTF-8');

  // arma los data-* para el preview/cálculo
  $dataTipo     = $tip ?: '—';
  $dataPersonas = $per > 0 ? $per : 0;
  $dataPrecio   = $pre > 0 ? $pre : 0;

  echo "<option value=\"{$id}\" data-tipo=\"{$dataTipo}\" data-personas=\"{$dataPersonas}\" data-precio=\"{$dataPrecio}\">{$label}</option>";
}