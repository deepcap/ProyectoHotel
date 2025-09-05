<?php
// backend/empleados/crear.php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require __DIR__ . '/../config/db.php';
require __DIR__ . '/../lib/ui.php';

// Solo POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    page_notice('Método no permitido', '<p>Usa el formulario de alta de empleado.</p>', 'error', [
        ['href'=>'/public/pages/registro-empleado.html','label'=>'Volver al formulario']
    ]);
    exit;
}

// Datos
$nombre     = trim($_POST['nombre']     ?? '');
$apellidoP  = trim($_POST['apellidoP']  ?? '');
$apellidoM  = trim($_POST['apellidoM']  ?? '');
$telefono   = trim($_POST['telefono']   ?? '');
$areaInput  = trim($_POST['area']       ?? ''); // ahora viene ID (select)
$turnoRaw   = trim($_POST['turno']      ?? ''); // "1","2","3"

// Validaciones
$errores = [];
if ($nombre === '')     $errores[] = 'Nombre es requerido.';
if ($apellidoP === '')  $errores[] = 'Apellido paterno es requerido.';
if ($telefono === '')   $errores[] = 'Teléfono es requerido.';
if ($areaInput === '')  $errores[] = 'Área es requerida.';
if ($turnoRaw === '' || !ctype_digit($turnoRaw)) $errores[] = 'Turno inválido.';
$turnoId = (int)$turnoRaw;
if (!in_array($turnoId, [1,2,3], true)) $errores[] = 'Turno fuera de rango (1,2,3).';

if ($errores) {
    http_response_code(422);
    $li = '<ul>'.implode('', array_map(fn($e)=>'<li>'.htmlspecialchars($e).'</li>', $errores)).'</ul>';
    page_notice('Errores de validación', $li, 'error', [
        ['href'=>'/public/pages/registro-empleado.html','label'=>'Corregir y reintentar'],
        ['href'=>'/public/pages/menu-completo.html','label'=>'Menú']
    ], "https://images.pexels.com/photos/3184465/pexels-photo-3184465.jpeg");
    exit;
}

// Detectar columnas reales
$cols = []; $types = [];
$resCols = $mysqli->query("SHOW COLUMNS FROM Empleado");
if ($resCols) {
    while ($c = $resCols->fetch_assoc()) {
        $field = strtolower($c['Field']);
        $cols[$field]  = true;
        $types[$field] = strtolower($c['Type']);
    }
    $resCols->free();
}
$hasIdArea    = isset($cols['id_area']);
$hasAreaText  = isset($cols['area']);
$areaIsInt    = $hasAreaText && str_contains($types['area'] ?? '', 'int');

$hasIdTurno   = isset($cols['id_turno']);
$hasTurnoText = isset($cols['turno']);
$turnoIsInt   = $hasTurnoText && str_contains($types['turno'] ?? '', 'int');

// Resolver área (ID y nombre)
$areaId = null; $areaNombre = null;
if (ctype_digit($areaInput)) {
    $areaId = (int)$areaInput;
    $chk = $mysqli->prepare('SELECT nombre_area FROM Area WHERE id_area = ? LIMIT 1');
    $chk->bind_param('i', $areaId);
    $chk->execute(); $chk->bind_result($areaNombre);
    if (!$chk->fetch()) { $areaNombre = null; }
    $chk->close();
    if ($areaNombre === null) {
        http_response_code(422);
        page_notice('Área inexistente', '<p>El área seleccionada no existe.</p>', 'error', [
            ['href'=>'/public/pages/registro-empleado.html','label'=>'Volver al formulario']
        ], "https://images.pexels.com/photos/3184465/pexels-photo-3184465.jpeg");
        exit;
    }
} else {
    $areaNombre = $areaInput;
    $sel = $mysqli->prepare('SELECT id_area FROM Area WHERE nombre_area = ? LIMIT 1');
    $sel->bind_param('s', $areaNombre);
    $sel->execute(); $sel->bind_result($areaId);
    if (!$sel->fetch()) {
        $sel->close();
        $insA = $mysqli->prepare('INSERT INTO Area (nombre_area) VALUES (?)');
        $insA->bind_param('s', $areaNombre);
        if (!$insA->execute()) {
            http_response_code(500);
            page_notice('No se pudo crear el área', '<pre>'.htmlspecialchars($mysqli->error).'</pre>', 'error', [
                ['href'=>'/public/pages/registro-empleado.html','label'=>'Volver al formulario']
            ]);
            exit;
        }
        $areaId = $insA->insert_id;
        $insA->close();
    } else {
        $sel->close();
    }
}

// Asegurar turnos si aplica
if ($hasIdTurno) {
    $turnosSeed = [1=>'mañana', 2=>'tarde', 3=>'noche'];
    foreach ($turnosSeed as $id => $txt) {
        $up = $mysqli->prepare('INSERT IGNORE INTO Turno (id_turno, tipo_turno) VALUES (?, ?)');
        $up->bind_param('is', $id, $txt);
        $up->execute(); $up->close();
    }
}

// Construir INSERT
$campos  = ['nombre','apellido_paterno','apellido_materno','telefono'];
$marcas  = ['?','?','?','?'];
$tipos   = 'ssss';
$valores = [$nombre, $apellidoP, $apellidoM, $telefono];

// Área
if ($hasIdArea) {
    $campos[]='id_area'; $marcas[]='?'; $tipos.='i'; $valores[] = $areaId;
} elseif ($hasAreaText) {
    if ($areaIsInt) {
        $campos[]='area'; $marcas[]='?'; $tipos.='i'; $valores[] = $areaId;
    } else {
        if ($areaNombre === null) {
            $q=$mysqli->prepare('SELECT nombre_area FROM Area WHERE id_area=?');
            $q->bind_param('i',$areaId); $q->execute(); $q->bind_result($areaNombre); $q->fetch(); $q->close();
        }
        $campos[]='area'; $marcas[]='?'; $tipos.='s'; $valores[] = $areaNombre ?? '';
    }
}

// Turno
if ($hasIdTurno) {
    $campos[]='id_turno'; $marcas[]='?'; $tipos.='i'; $valores[] = $turnoId;
} elseif ($hasTurnoText) {
    if ($turnoIsInt) {
        $campos[]='turno'; $marcas[]='?'; $tipos.='i'; $valores[] = $turnoId;
    } else {
        $campos[]='turno'; $marcas[]='?'; $tipos.='s'; $valores[] = (string)$turnoId;
    }
}

$sql = 'INSERT INTO Empleado ('.implode(',', $campos).') VALUES ('.implode(',', $marcas).')';
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    page_notice('Error interno', '<pre>'.htmlspecialchars($mysqli->error).'</pre>', 'error', [
        ['href'=>'/public/pages/registro-empleado.html','label'=>'Volver al formulario']
    ]);
    exit;
}
$stmt->bind_param($tipos, ...$valores);
if (!$stmt->execute()) {
    http_response_code(500);
    page_notice('No se pudo guardar el empleado', '<pre>'.htmlspecialchars($stmt->error).'</pre>', 'error', [
        ['href'=>'/public/pages/registro-empleado.html','label'=>'Volver al formulario']
    ]);
    $stmt->close(); exit;
}
$nuevoID = $stmt->insert_id;
$stmt->close();

// OK
$body  = '<p><strong>ID:</strong> '.(int)$nuevoID.'</p>';
$body .= '<p><strong>Nombre:</strong> '.htmlspecialchars($nombre).' '.htmlspecialchars($apellidoP).' '.htmlspecialchars($apellidoM).'</p>';
$body .= '<p><strong>Área (ID):</strong> '.(int)$areaId.' · <strong>Turno:</strong> '.(int)$turnoId.'</p>';

page_notice('✅ Empleado registrado', $body, 'success', [
    ['href'=>'/public/pages/registro-empleado.html','label'=>'Registrar otro'],
    ['href'=>'/public/pages/menu-completo.html','label'=>'Menú'],
    ['href'=>'/public/pages/consultas.html','label'=>'Ir a consultas']
], "https://images.pexels.com/photos/3184465/pexels-photo-3184465.jpeg");
