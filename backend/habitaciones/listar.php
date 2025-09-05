<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

$q = trim($_GET['q'] ?? '');
$sql = "SELECT id_habitacion, numero_habitacion, precio, tipo_habitacion, estado, cantidad_personas FROM Habitacion";
$params=[]; $types='';
if($q!==''){
  $sql.=" WHERE CAST(numero_habitacion AS CHAR) LIKE CONCAT('%', ?, '%')
           OR LOWER(tipo_habitacion) LIKE CONCAT('%', ?, '%')
           OR LOWER(estado) LIKE CONCAT('%', ?, '%')";
  $q2 = mb_strtolower($q,'UTF-8');
  $params = [$q,$q2,$q2]; $types='sss';
}
$sql.=" ORDER BY numero_habitacion ASC";

$stmt=$mysqli->prepare($sql);
if($params) $stmt->bind_param($types, ...$params);
$stmt->execute(); $res=$stmt->get_result();

if($res->num_rows===0){ echo '<tr><td class="empty" colspan="7">Sin resultados</td></tr>'; exit; }

while($r=$res->fetch_assoc()){
  $id=(int)$r['id_habitacion'];
  $num=htmlspecialchars((string)$r['numero_habitacion']);
  $pre=htmlspecialchars(number_format((float)$r['precio'],2,'.',''));
  $tipo=htmlspecialchars($r['tipo_habitacion']);
  $est =htmlspecialchars($r['estado']);
  $per =htmlspecialchars((string)$r['cantidad_personas']);

  echo "<tr>
    <td>{$id}</td>
    <td>#{$num}</td>
    <td>{$tipo}</td>
    <td>{$est}</td>
    <td>{$per}</td>
    <td>\${$pre}</td>
    <td>
      <button class='btn btn-primary'
        data-act='editar' data-id='{$id}'
        data-numero='{$num}' data-precio='{$pre}'
        data-tipo='{$tipo}' data-estado='{$est}' data-personas='{$per}'>Editar</button>
      <button class='btn btn-danger' data-act='eliminar' data-id='{$id}'>Eliminar</button>
    </td>
  </tr>";
}
$stmt->close();