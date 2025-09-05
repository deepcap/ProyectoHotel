<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '';
$params = [];

if ($q !== '') {
  $where = "WHERE c.nombre LIKE ? OR c.apellido_paterno LIKE ? OR c.apellido_materno LIKE ? OR c.telefono LIKE ? OR c.correo LIKE ?";
  $like = '%' . $q . '%';
}

$sql = "SELECT c.id_cliente, c.nombre, c.apellido_paterno, c.apellido_materno, c.telefono, c.correo
        FROM Cliente c
        $where
        ORDER BY c.id_cliente DESC";

if ($q !== '') {
  $stmt = $mysqli->prepare($sql);
  $stmt->bind_param('sssss', $like, $like, $like, $like, $like);
  $stmt->execute();
  $res = $stmt->get_result();
} else {
  $res = $mysqli->query($sql);
}

if(!$res){
  http_response_code(500);
  echo '<tr><td class="empty" colspan="5">Error al consultar</td></tr>';
  exit;
}

if($res->num_rows===0){
  echo '<tr><td class="empty" colspan="5">Sin resultados</td></tr>';
  exit;
}

while($row=$res->fetch_assoc()){
  $id  = (int)$row['id_cliente'];
  $nom = htmlspecialchars(trim(($row['nombre']??'').' '.($row['apellido_paterno']??'').' '.($row['apellido_materno']??'')), ENT_QUOTES, 'UTF-8');
  $tel = htmlspecialchars((string)($row['telefono']??''), ENT_QUOTES, 'UTF-8');
  $cor = htmlspecialchars((string)($row['correo']??''), ENT_QUOTES, 'UTF-8');

  echo "<tr>";
  echo "<td>{$id}</td>";
  echo "<td>{$nom}</td>";
  echo "<td>{$tel}</td>";
  echo "<td>{$cor}</td>";
  echo "<td class='actions-row'>
          <button class='btn btn-primary' data-act='editar' data-id='{$id}'>Editar</button>
          <button class='btn btn-danger' data-act='eliminar' data-id='{$id}'>Eliminar</button>
        </td>";
  echo "</tr>";
}