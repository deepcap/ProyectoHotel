<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

header('Content-Type: text/html; charset=UTF-8');

// Consulta cobros con datos relacionados
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

if (!$res || $res->num_rows === 0) {
    echo '<tr><td class="empty" colspan="7">Sin resultados</td></tr>';
    exit;
}

while($row = $res->fetch_assoc()){
    $id   = (int)$row['id_cobro'];
    $fecha= htmlspecialchars(substr($row['fecha_transaccion'],0,19), ENT_QUOTES, 'UTF-8');
    $monto= '$'.number_format((float)$row['monto'],2).' MXN'; // 👈 añadimos MXN
    $met  = htmlspecialchars($row['metodo_pago'], ENT_QUOTES, 'UTF-8');
    $tic  = htmlspecialchars($row['numero_ticket'], ENT_QUOTES, 'UTF-8');
    $cli  = htmlspecialchars($row['cliente'], ENT_QUOTES, 'UTF-8');
    $hab  = htmlspecialchars($row['numero_habitacion'], ENT_QUOTES, 'UTF-8');

    echo "<tr>
      <td>{$id}</td>
      <td>{$fecha}</td>
      <td class='num'>{$monto}</td>
      <td>{$met}</td>
      <td>{$tic}</td>
      <td>{$cli}</td>
      <td>{$hab}</td>
    </tr>";
}