<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);
date_default_timezone_set('America/Mexico_City');

require __DIR__ . '/../config/db.php';

/*
  Filtros esperados por GET (opcionales):
    - q       : texto (folio, cliente, habitación)
    - desde   : YYYY-MM-DD
    - hasta   : YYYY-MM-DD
    - estado  : 'RESERVADA' | 'ENCURSO' | 'FINALIZADA' | '' (todos)

  Salida: <tr>…</tr> (HTML) para inyectar en el <tbody>
*/

// --------- Tomar filtros ----------
$q      = trim($_GET['q']      ?? '');
$desde  = trim($_GET['desde']  ?? '');
$hasta  = trim($_GET['hasta']  ?? '');
$estado = strtoupper(trim($_GET['estado'] ?? '')); // normalizamos

// Validar fechas si vienen
$reDate = '/^\d{4}-\d{2}-\d{2}$/';
if ($desde !== '' && !preg_match($reDate, $desde)) $desde = '';
if ($hasta !== '' && !preg_match($reDate, $hasta)) $hasta = '';

// --------- Construir WHERE dinámico ----------
$where = [];
$params = [];
$types  = '';

if ($q !== '') {
  // busca en cliente, número de habitación y folio (id)
  $where[] = "(CONCAT(cl.nombre,' ',cl.apellido_paterno,' ',COALESCE(cl.apellido_materno,'')) LIKE ?
               OR CAST(h.numero_habitacion AS CHAR) LIKE ?
               OR CAST(r.id_reservacion AS CHAR) LIKE ?)";
  $like = "%$q%";
  $params[] = $like; $params[] = $like; $params[] = $like;
  $types   .= 'sss';
}

if ($desde !== '') {
  $where[]  = "DATE(cio.hora_entrada) >= ?";
  $params[] = $desde;
  $types   .= 's';
}

if ($hasta !== '') {
  $where[]  = "DATE(COALESCE(cio.hora_salida, cio.hora_entrada)) <= ?";
  $params[] = $hasta;
  $types   .= 's';
}

if ($estado !== '') {
  // Estado lógico calculado en el SELECT. Lo filtramos con la misma expresión.
  // RESERVADA: entrada futura y sin salida
  // ENCURSO  : entrada <= ahora y sin salida
  // FINALIZADA: tiene salida
  if ($estado === 'RESERVADA') {
    $where[] = "(cio.hora_salida IS NULL AND cio.hora_entrada > NOW())";
  } elseif ($estado === 'ENCURSO') {
    $where[] = "(cio.hora_salida IS NULL AND cio.hora_entrada <= NOW())";
  } elseif ($estado === 'FINALIZADA') {
    $where[] = "(cio.hora_salida IS NOT NULL)";
  }
}

// Ensamblar
$whereSQL = '';
if (!empty($where)) {
  $whereSQL = 'WHERE ' . implode(' AND ', $where);
}

// --------- Consulta ---------
// Noches: DATEDIFF( checkout_or_now , checkin ); nunca menos de 0.
// Total: noches * h.precio
$sql = "
SELECT
  r.id_reservacion,
  CONCAT(cl.nombre,' ',cl.apellido_paterno,' ',COALESCE(cl.apellido_materno,'')) AS cliente,
  h.id_habitacion,
  h.numero_habitacion,
  h.tipo_habitacion,
  h.precio,
  cio.hora_entrada,
  cio.hora_salida,
  GREATEST(DATEDIFF(COALESCE(cio.hora_salida, NOW()), cio.hora_entrada), 0)             AS noches_calc,
  GREATEST(DATEDIFF(COALESCE(cio.hora_salida, NOW()), cio.hora_entrada), 0) * h.precio  AS total_calc,
  CASE
    WHEN cio.hora_salida IS NOT NULL THEN 'Finalizada'
    WHEN cio.hora_entrada <= NOW()    THEN 'En curso'
    ELSE 'Reservada'
  END AS estado_calc
FROM Reservacion r
JOIN Cliente    cl  ON cl.id_cliente    = r.id_cliente
JOIN Habitacion h   ON h.id_habitacion  = r.id_habitacion
JOIN CheckInOut cio ON cio.id_reservacion = r.id_reservacion
{$whereSQL}
ORDER BY r.id_reservacion DESC
";

// Ejecutar
if (!empty($params)) {
  $stmt = $mysqli->prepare($sql);
  if (!$stmt) {
    echo "<tr><td class='empty' colspan='9'>Error al preparar consulta</td></tr>";
    exit;
  }
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $res = $stmt->get_result();
} else {
  $res = $mysqli->query($sql);
}

if (!$res) {
  echo "<tr><td class='empty' colspan='9'>Error al consultar</td></tr>";
  exit;
}
if ($res->num_rows === 0) {
  echo "<tr><td class='empty' colspan='9'>Sin resultados</td></tr>";
  exit;
}

// --------- Render de filas ----------
while ($row = $res->fetch_assoc()) {
  $id      = (int)$row['id_reservacion'];
  $cli     = htmlspecialchars($row['cliente'], ENT_QUOTES, 'UTF-8');
  $habNum  = (int)$row['numero_habitacion'];
  $checkin = $row['hora_entrada'] ? (new DateTime($row['hora_entrada']))->format('Y-m-d H:i:s') : '—';
  $checkout= $row['hora_salida']  ? (new DateTime($row['hora_salida']))->format('Y-m-d H:i:s')  : '—';
  $noches  = (int)$row['noches_calc'];
  $total   = (float)$row['total_calc'];
  $estado  = $row['estado_calc'];

  // Badge de estado
  $badgeClass = 'badge';
  $badgeText  = $estado;
  if ($estado === 'Finalizada') { $badgeClass .= ' badge-gray'; }
  if ($estado === 'En curso')   { $badgeClass .= ' badge-green'; }
  if ($estado === 'Reservada')  { $badgeClass .= ' badge-blue';  }
  $badge = "<span class='{$badgeClass}'>{$badgeText}</span>";

  // Botón Check-out: solo si NO tiene hora_salida (a petición tuya, desaparece cuando ya está finalizada)
  $btnCheckout = '';
  if (empty($row['hora_salida'])) {
    $btnCheckout = "<button class='btn btn-secondary' data-act='checkout' data-id='{$id}'>Check-out</button>";
  }

  echo "<tr>
    <td>#{$id}</td>
    <td>{$cli}</td>
    <td>{$habNum}</td>
    <td>{$checkin}</td>
    <td>{$checkout}</td>
    <td>{$noches}</td>
    <td>$".number_format($total, 2)."</td>
    <td>{$badge}</td>
    <td class='actions-row'>
      {$btnCheckout}
      <button class='btn btn-secondary' data-act='eliminar' data-id='{$id}'>Eliminar</button>
    </td>
  </tr>";
}

$res->free();