<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');

ini_set('display_errors','1');
error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
// Si deseas proteger por sesión real, aquí incluirías require_login.php

$sql = "SELECT 
  r.id_reservacion,
  r.fecha_reservacion,
  CONCAT(c.nombre,' ',c.apellido_paterno,' ',COALESCE(c.apellido_materno,'')) AS cliente,
  h.numero_habitacion,
  h.precio
FROM Reservacion r
JOIN Cliente c    ON r.id_cliente = c.id_cliente
JOIN Habitacion h ON r.id_habitacion = h.id_habitacion
ORDER BY r.id_reservacion DESC";

$res = $mysqli->query($sql);

if(!$res){
  http_response_code(500);
  echo '<tr><td class="empty" colspan="5">Error al consultar</td></tr>';
  exit;
}

if($res->num_rows === 0){
  echo '<tr><td class="empty" colspan="5">Sin resultados</td></tr>';
  exit;
}

while($row = $res->fetch_assoc()){
  $id     = (int)$row['id_reservacion'];
  $fecha  = htmlspecialchars(substr((string)$row['fecha_reservacion'],0,19), ENT_QUOTES, 'UTF-8');
  $cliente= htmlspecialchars((string)$row['cliente'], ENT_QUOTES, 'UTF-8');
  $hab    = htmlspecialchars((string)$row['numero_habitacion'], ENT_QUOTES, 'UTF-8');
  $precio = number_format((float)$row['precio'], 2);

  echo "<tr>";
  echo "<td>{$id}</td>";
  echo "<td>{$fecha}</td>";
  echo "<td>{$cliente}</td>";
  echo "<td>{$hab}</td>";
  echo "<td style=\"text-align:right;\">{$precio}</td>";
  echo "</tr>";
}