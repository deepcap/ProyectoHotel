<?php
// backend/habitaciones/crear.php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/ui.php';

// Solo POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    page_notice('Método no permitido', '<p>Usa el formulario de alta de habitación.</p>', 'error', [
        ['href'=>'/public/pages/habitacion.html','label'=>'Volver al formulario']
    ]);
    exit;
}

// Recibir (⚠️ sin fechas aquí)
$numero   = trim($_POST['numero']   ?? '');
$precio   = trim($_POST['precio']   ?? '');
$tipo     = trim($_POST['tipo']     ?? '');
$estado   = trim($_POST['estado']   ?? '');
$personas = trim($_POST['personas'] ?? '');

// Validaciones
$errores = [];
if ($numero === ''   || !ctype_digit($numero))  $errores[] = 'Número de habitación inválido.';
if ($precio === ''   || !is_numeric($precio))   $errores[] = 'Precio inválido.';
if ($tipo === '')                                $errores[] = 'Tipo es requerido.';
if ($estado === '')                              $errores[] = 'Estado es requerido.';
if ($personas === '' || !ctype_digit($personas)) $errores[] = 'Cantidad de personas inválida.';

if ($errores) {
    http_response_code(422);
    $li = '<ul>'.implode('', array_map(fn($e)=>'<li>'.htmlspecialchars($e).'</li>', $errores)).'</ul>';
    page_notice('Errores de validación', $li, 'error', [
        ['href'=>'/public/pages/habitacion.html','label'=>'Corregir y reintentar'],
        ['href'=>'/public/pages/menu-completo.html','label'=>'Menú']
    ]);
    exit;
}

$num = (int)$numero; $pre = (float)$precio; $per = (int)$personas;

// Duplicado
$check = $mysqli->prepare('SELECT id_habitacion FROM Habitacion WHERE numero_habitacion = ?');
$check->bind_param('i', $num);
$check->execute(); $check->store_result();
if ($check->num_rows > 0) {
    $check->close();
    http_response_code(409);
    page_notice('Número ya registrado', '<p>Ya existe una habitación con el número <strong>'.(int)$num.'</strong>.</p>', 'error', [
        ['href'=>'/public/pages/habitacion.html','label'=>'Volver al formulario'],
        ['href'=>'/public/pages/consultas.html','label'=>'Ir a consultas']
    ]);
    exit;
}
$check->close();

// Insert
$sql = 'INSERT INTO Habitacion (numero_habitacion, precio, tipo_habitacion, estado, cantidad_personas)
        VALUES (?, ?, ?, ?, ?)';
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    page_notice('Error interno', '<pre>'.htmlspecialchars($mysqli->error).'</pre>', 'error', [
        ['href'=>'/public/pages/habitacion.html','label'=>'Volver al formulario']
    ]);
    exit;
}
$stmt->bind_param('idssi', $num, $pre, $tipo, $estado, $per);

if (!$stmt->execute()) {
    http_response_code(500);
    page_notice('No se pudo guardar', '<pre>'.htmlspecialchars($stmt->error).'</pre>', 'error', [
        ['href'=>'/public/pages/habitacion.html','label'=>'Volver al formulario']
    ]);
    $stmt->close(); exit;
}
$stmt->close();

// ✅ ÉXITO — mensaje minimalista (sin datos sensibles)
page_notice(
    '✅ Habitación registrada',
    '<p>El registro se completó correctamente.</p>',
    'success',
    [
        ['href'=>'/public/pages/habitacion.html','label'=>'Registrar otra'],
        ['href'=>'/public/pages/menu-completo.html','label'=>'Menú'],
        ['href'=>'/public/pages/consultas.html','label'=>'Ir a consultas']
    ],
    "https://images.pexels.com/photos/271624/pexels-photo-271624.jpeg"
);