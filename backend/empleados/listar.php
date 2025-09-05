<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

// Detectar columnas (id_area/area, id_turno/turno)
$cols=[]; $res=$mysqli->query("SHOW COLUMNS FROM Empleado");
if($res){ while($c=$res->fetch_assoc()){ $cols[strtolower($c['Field'])]=true; } $res->free(); }
$hasIdArea = isset($cols['id_area']);
$hasArea   = isset($cols['area']);
$hasIdTurno= isset($cols['id_turno']);
$hasTurno  = isset($cols['turno']);

$q = trim($_GET['q'] ?? '');
$base = "SELECT e.id_empleado, e.nombre, e.apellido_paterno, e.apellido_materno, e.telefono";

if($hasIdArea)  $base .= ", e.id_area, a.nombre_area";
elseif($hasArea) $base .= ", e.area";

if($hasIdTurno)  $base .= ", e.id_turno, t.tipo_turno";
elseif($hasTurno) $base .= ", e.turno";

$base .= " FROM Empleado e";

if($hasIdArea)  $base .= " LEFT JOIN Area a ON e.id_area = a.id_area";
if($hasIdTurno) $base .= " LEFT JOIN Turno t ON e.id_turno = t.id_turno";

$where=[]; $params=[]; $types='';
if($q!==''){
  $where[]="LOWER(CONCAT(e.nombre,' ',e.apellido_paterno,' ',COALESCE(e.apellido_materno,''))) LIKE CONCAT('%', ?, '%')";
  $params[] = mb_strtolower($q,'UTF-8'); $types.='s';
  $where[]="LOWER(e.telefono) LIKE CONCAT('%', ?, '%')";
  $params[] = mb_strtolower($q,'UTF-8'); $types.='s';
  if($hasArea) { $where[]="LOWER(CAST(e.area AS CHAR)) LIKE CONCAT('%', ?, '%')"; $params[] = mb_strtolower($q,'UTF-8'); $types.='s'; }
  if($hasIdArea) { $where[]="LOWER(a.nombre_area) LIKE CONCAT('%', ?, '%')"; $params[] = mb_strtolower($q,'UTF-8'); $types.='s'; }
}
if($where) $base .= " WHERE ".implode(" OR ", $where);
$base .= " ORDER BY e.id_empleado DESC";

$stmt=$mysqli->prepare($base);
if($params) $stmt->bind_param($types, ...$params);
$stmt->execute(); $rs=$stmt->get_result();

if($rs->num_rows===0){ echo '<tr><td class="empty" colspan="7">Sin resultados</td></tr>'; exit; }

while($r=$rs->fetch_assoc()){
  $id = (int)$r['id_empleado'];
  $nombre = htmlspecialchars($r['nombre']);
  $apP = htmlspecialchars($r['apellido_paterno']);
  $apM = htmlspecialchars($r['apellido_materno'] ?? '');
  $tel= htmlspecialchars($r['telefono'] ?? '');

  // Área
  $areaNombre=''; $areaId='';
  if($hasIdArea){ $areaId=(int)($r['id_area'] ?? 0); $areaNombre=htmlspecialchars($r['nombre_area'] ?? ""); }
  elseif($hasArea){ $areaNombre=htmlspecialchars((string)$r['area']); }

  // Turno
  $turnoVal='';
  if($hasIdTurno){ $turnoVal = (string)($r['id_turno'] ?? ''); }
  elseif($hasTurno){ $turnoVal = htmlspecialchars((string)$r['turno']); }

  echo "<tr>
    <td>{$id}</td>
    <td>{$nombre}</td>
    <td>{$apP} {$apM}</td>
    <td>{$tel}</td>
    <td>".($areaNombre!==''?$areaNombre:'—')."</td>
    <td>".($turnoVal!==''?$turnoVal:'—')."</td>
    <td>
      <button class='btn btn-primary'
        data-act='editar' data-id='{$id}'
        data-nombre='{$nombre}' data-apellidop='{$apP}' data-apellidom='{$apM}'
        data-telefono='{$tel}' data-areaid='{$areaId}'
        data-turno='{$turnoVal}'>Editar</button>
      <button class='btn btn-danger' data-act='eliminar' data-id='{$id}'>Eliminar</button>
    </td>
  </tr>";
}
$stmt->close();