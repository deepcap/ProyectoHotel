<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');

ini_set('display_errors','1');
error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
// Si usas sesiones reales, aquí incluirías require_login.php

/*
  Columnas esperadas (según tu PDF):
  - ID cobro
  - Fecha transacción
  - Monto
  - Método de pago
  - Ticket
  - Cliente (nombre completo)
  - Habitación (número)
*/
$sql = "SELECT 
  cbr.id_cobro,
  cbr.fecha_transaccion,
  cbr.monto,
  tp.metodo AS metodo_pago,
  t.numero_ticket,
  CONCAT(cl.nombre,' ',cl.apellido_paterno,' ',COALESCE(cl.apellido_materno,'')) AS cliente,
  h.numero_habitacion
FROM Cobro cbr
JOIN Ticket t       ON cbr.id_ticket = t.id_ticket
JOIN TipoPago tp    ON cbr.id_tipo_pago = tp.id_tipo_pago
JOIN Reservacion r  ON t.id_reservacion = r.id_reservacion
JOIN Cliente cl     ON r.id_cliente = cl.id_cliente
JOIN Habitacion h   ON r.id_habitacion = h.id_habitacion
ORDER BY cbr.fecha_transaccion DESC";

$res = $mysqli->query($sql);

if(!$res){
  http_response_code(500);
  echo '<tr><td class="empty" colspan="7">Error al consultar</td></tr>';
  exit;
}

if($res->num_rows === 0){
  echo '<tr><td class="empty" colspan="7">Sin resultados</td></tr>';
  exit;
}

while($row = $res->fetch_assoc()){
  $id     = (int)$row['id_cobro'];
  $fecha  = htmlspecialchars(substr((string)$row['fecha_transaccion'],0,19), ENT_QUOTES, 'UTF-8');
  $monto  = number_format((float)$row['monto'], 2);
  $metodo = htmlspecialchars((string)$row['metodo_pago'], ENT_QUOTES, 'UTF-8');
  $ticket = htmlspecialchars((string)$row['numero_ticket'], ENT_QUOTES, 'UTF-8');
  $cliente= htmlspecialchars((string)$row['cliente'], ENT_QUOTES, 'UTF-8');
  $hab    = htmlspecialchars((string)$row['numero_habitacion'], ENT_QUOTES, 'UTF-8');

  echo "<tr>";
  echo "<td>{$id}</td>";
  echo "<td>{$fecha}</td>";
  echo "<td class=\"num\">{$monto}</td>";
  echo "<td>{$metodo}</td>";
  echo "<td>{$ticket}</td>";
  echo "<td>{$cliente}</td>";
  echo "<td>{$hab}</td>";
  echo "</tr>";
}