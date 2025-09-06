<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

$in  = $_GET['check_in']  ?? '';
$out = $_GET['check_out'] ?? '';

$reDate = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($reDate,$in) || !preg_match($reDate,$out) || $in >= $out) {
  echo json_encode(['ok'=>false,'msg'=>'Rango de fechas inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Convertimos a DATETIME con mismas horas que usarás al crear
$inDT  = $in  . ' 15:00:00';
$outDT = $out . ' 12:00:00';

// Traemos habitaciones
$sqlH = "SELECT id_habitacion, numero_habitacion, tipo_habitacion, cantidad_personas, precio FROM Habitacion ORDER BY numero_habitacion ASC";
$rh   = $mysqli->query($sqlH);
if (!$rh) {
  echo json_encode(['ok'=>false,'msg'=>'Error al consultar habitaciones'], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = [];
while ($h = $rh->fetch_assoc()) {
  $id_hab = (int)$h['id_habitacion'];

  // ¿Existe traslape en este rango?
  $sqlO = "
    SELECT 1
    FROM Reservacion r
    JOIN CheckInOut cio ON cio.id_reservacion = r.id_reservacion
    WHERE r.id_habitacion = ?
      AND NOT (cio.hora_salida <= ? OR cio.hora_entrada >= ?)
    LIMIT 1";
  $st = $mysqli->prepare($sqlO);
  $st->bind_param('iss', $id_hab, $inDT, $outDT);
  $st->execute();
  $busy = (bool)$st->get_result()->fetch_row();
  $st->close();

  $data[] = [
    'id_habitacion' => $id_hab,
    'numero'        => (int)$h['numero_habitacion'],
    'tipo'          => $h['tipo_habitacion'],
    'personas'      => (int)$h['cantidad_personas'],
    'precio'        => (float)$h['precio'],
    'disponible'    => !$busy
  ];
}

echo json_encode(['ok'=>true,'data'=>$data], JSON_UNESCAPED_UNICODE);