<?php
// backend/clientes/crear.php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/ui.php';

// Solo POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
  http_response_code(405);
  page_notice('Método no permitido', '<p>Usa el formulario de registro de cliente.</p>', 'error', [
    ['href'=>'/public/pages/clientes.html','label'=>'Volver al formulario']
  ]);
  exit;
}

// Entradas
$nombre  = trim($_POST['nombre']            ?? '');
$apPat   = trim($_POST['apellido_paterno']  ?? '');
$apMat   = trim($_POST['apellido_materno']  ?? '');
$tel     = trim($_POST['telefono']          ?? '');
$correo  = trim($_POST['correo']            ?? '');

// Validaciones básicas
$errores = [];
if ($nombre==='')  $errores[]='El nombre es obligatorio.';
if ($apPat==='')   $errores[]='El apellido paterno es obligatorio.';
if ($tel==='')     $errores[]='El teléfono es obligatorio.';
if ($correo!=='' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  $errores[]='El correo no tiene un formato válido.';
}

if ($errores) {
  http_response_code(422);
  $li = '<ul>'.implode('', array_map(fn($e)=>'<li>'.htmlspecialchars($e).'</li>', $errores)).'</ul>';
  page_notice('Errores de validación', $li, 'error', [
    ['href'=>'/public/pages/clientes.html','label'=>'Corregir y reintentar'],
    ['href'=>'/public/pages/menu-completo.html','label'=>'Menú']
  ], "https://images.pexels.com/photos/3183150/pexels-photo-3183150.jpeg");
  exit;
}

// Insert
$stmt = $mysqli->prepare("
  INSERT INTO Cliente (nombre, apellido_paterno, apellido_materno, correo, telefono)
  VALUES (?, ?, ?, ?, ?)
");
if (!$stmt) {
  http_response_code(500);
  page_notice('Error interno', '<pre>'.htmlspecialchars($mysqli->error).'</pre>', 'error', [
    ['href'=>'/public/pages/clientes.html','label'=>'Volver al formulario']
  ]);
  exit;
}
$stmt->bind_param('sssss', $nombre, $apPat, $apMat, $correo, $tel);

if (!$stmt->execute()) {
  // Manejo amable de correo duplicado si tu tabla lo tiene como UNIQUE
  $msg = stripos($stmt->error, 'duplicate') !== false
    ? 'Ese correo ya está registrado.'
    : 'No se pudo guardar el cliente.';
  http_response_code(500);
  page_notice('No se pudo guardar', '<p>'.htmlspecialchars($msg).'</p>', 'error', [
    ['href'=>'/public/pages/clientes.html','label'=>'Volver al formulario']
  ]);
  $stmt->close(); exit;
}
$nuevoID = $stmt->insert_id;
$stmt->close();

// ÉXITO (sin mostrar datos sensibles)
page_notice('✅ Cliente registrado', '<p>Operación completada.</p>', 'success', [
  ['href'=>'/public/pages/clientes.html','label'=>'Registrar otro'],
  ['href'=>'/public/pages/menu-completo.html','label'=>'Menú'],
  ['href'=>'/public/pages/consultas.html','label'=>'Ir a consultas']
], "https://images.pexels.com/photos/3183150/pexels-photo-3183150.jpeg");