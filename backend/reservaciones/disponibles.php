<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);
date_default_timezone_set('America/Mexico_City');

require __DIR__ . '/../config/db.php';

$in  = trim($_GET['check_in']  ?? '');
$out = trim($_GET['check_out'] ?? '');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$in) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$out)) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'msg'=>'Fechas inválidas']); exit;
}

$ci = (new DateTime($in.' 15:00:00'))->format('Y-m-d H:i:s');  // 3pm
$co = (new DateTime($out.' 11:00:00'))->format('Y-m-d H:i:s'); // 11am

// Para cada habitación, indicamos si hay traslape con alguna reserva (CIO)
$sql = "
SELECT
  h.id_habitacion,
  h.numero_habitacion AS numero,
  h.tipo_habitacion   AS tipo,
  h.cantidad_personas AS personas,
  h.precio,
  CASE WHEN EXISTS (
    SELECT 1
    FROM Reservacion r
    JOIN CheckInOut  c ON c.id_reservacion = r.id_reservacion
    WHERE r.id_habitacion = h.id_habitacion
      AND c.hora_entrada < ?                               -- entrada_exist < salida_nueva
      AND COALESCE(c.hora_salida,'9999-12-31 23:59:59') > ? -- salida_exist > entrada_nueva
  )
  THEN 0 ELSE 1 END AS disponible
FROM Habitacion h
ORDER BY h.numero_habitacion ASC
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('ss',$co,$ci);
$stmt->execute();
$res = $stmt->get_result();
$data = [];
while ($row = $res->fetch_assoc()) {
  $row['id_habitacion'] = (int)$row['id_habitacion'];
  $row['personas']      = (int)$row['personas'];
  $row['precio']        = (float)$row['precio'];
  $row['disponible']    = (bool)$row['disponible'];
  $data[] = $row;
}
$stmt->close();

echo json_encode(['ok'=>true,'data'=>$data]);