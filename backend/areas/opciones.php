<?php
// backend/areas/opciones.php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';

// Traer todas las áreas ordenadas por nombre
$sql = "SELECT id_area, nombre_area FROM Area ORDER BY nombre_area ASC";
$res = $mysqli->query($sql);

header('Content-Type: text/html; charset=utf-8');

if (!$res || $res->num_rows === 0) {
    echo '<option value="">(Sin áreas registradas)</option>';
    exit;
}

// IMPORTANTE: el value será el NOMBRE del área, porque tu crear.php
// acepta `areaNombre` y resuelve id_area si corresponde.
// Si prefieres enviar el ID, cambia value a $id y ajusta crear.php.
echo '<option value="">Selecciona un área</option>';
while ($row = $res->fetch_assoc()) {
    $id  = (int)$row['id_area'];
    $nom = htmlspecialchars($row['nombre_area'] ?? '', ENT_QUOTES, 'UTF-8');
    echo '<option value="'.$nom.'" data-id="'.$id.'">'.$nom.'</option>';
}
$res->free();
