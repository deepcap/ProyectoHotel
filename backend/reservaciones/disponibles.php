<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

// Recibe ?check_in=YYYY-MM-DD&check_out=YYYY-MM-DD
$in  = $_GET['check_in']  ?? '';
$out = $_GET['check_out'] ?? '';

function is_date($s){ return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) === 1; }
if(!is_date($in) || !is_date($out) || $in >= $out){
  http_response_code(422);
  echo json_encode(['ok'=>false,'msg'=>'Rango de fechas inválido']);
  exit;
}

/*
 Disponibilidad:
  Tomamos todas las habitaciones y marcamos como OCUPADA aquellas que tengan
  al menos una reservación en estados activos (ajusta estados si tu tabla los tiene)
  que TRASLAPE el rango [check_in, check_out).

  Traslape si: NOT( r.check_out <= in OR r.check_in >= out )
*/
$sql = "
  SELECT
    h.id_habitacion,
    h.numero,
    h.tipo,
    h.personas,
    h.precio,
    h.estado AS estado_hab,
    CASE WHEN EXISTS (
      SELECT 1
      FROM Reservacion r
      WHERE r.id_habitacion = h.id_habitacion
        AND (
          (r.estado IS NULL) OR (r.estado NOT IN ('CANCELADA')) -- ajusta si no manejas estado
        )
        AND NOT (r.check_out <= ? OR r.check_in >= ?)
    ) THEN 0 ELSE 1 END AS disponible
  FROM Habitacion h
  ORDER BY h.numero
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ss', $in, $out);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while($r = $res->fetch_assoc()){
  $rows[] = [
    'id_habitacion' => (int)$r['id_habitacion'],
    'numero'        => (string)$r['numero'],
    'tipo'          => (string)$r['tipo'],
    'personas'      => (int)$r['personas'],
    'precio'        => (float)$r['precio'],
    'estado_hab'    => (string)$r['estado_hab'],
    'disponible'    => (int)$r['disponible'] === 1
  ];
}

echo json_encode(['ok'=>true, 'check_in'=>$in, 'check_out'=>$out, 'data'=>$rows], JSON_UNESCAPED_UNICODE);
