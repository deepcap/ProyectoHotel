<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');

ini_set('display_errors','1');
error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
// Si usas sesiones reales, aquí incluirías require_login.php

/*
  Resultado esperado por fila:
  - Año
  - Mes (nombre)
  - Día (número)
  - Total cobrado (suma de c.monto)
*/
$sql = "SELECT
  YEAR(c.fecha_transaccion)       AS Anio,
  MONTHNAME(c.fecha_transaccion)  AS Mes,
  MONTH(c.fecha_transaccion)      AS MesNum,
  DAY(c.fecha_transaccion)        AS Dia,
  SUM(c.monto)                    AS TotalCobrado
FROM Cobro c
GROUP BY Anio, Mes, MesNum, Dia
ORDER BY Anio, MesNum, Dia";

$res = $mysqli->query($sql);

if(!$res){
  http_response_code(500);
  echo '<tr><td class="empty" colspan="4">Error al consultar</td></tr>';
  exit;
}

if($res->num_rows === 0){
  echo '<tr><td class="empty" colspan="4">Sin resultados</td></tr>';
  exit;
}

while($row = $res->fetch_assoc()){
  $anio  = htmlspecialchars((string)$row['Anio'], ENT_QUOTES, 'UTF-8');
  $mes   = htmlspecialchars((string)$row['Mes'], ENT_QUOTES, 'UTF-8');   // nombre del mes
  $dia   = htmlspecialchars((string)$row['Dia'], ENT_QUOTES, 'UTF-8');
  $total = number_format((float)$row['TotalCobrado'], 2);

  echo "<tr>";
  echo "<td>{$anio}</td>";
  echo "<td>{$mes}</td>";
  echo "<td>{$dia}</td>";
  echo "<td class=\"num\">{$total}</td>";
  echo "</tr>";
}