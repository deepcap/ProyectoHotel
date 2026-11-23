<?php
/**
 * Hotel Paraíso — router para el servidor embebido de PHP (solo desarrollo local).
 *
 * Uso (desde la raíz del proyecto):
 *     php -S 127.0.0.1:8000 router.php
 *
 * Estrategia: DENEGAR POR DEFECTO. Solo se atiende:
 *   1) "/" y "/public/"          -> redirección a /public/index.html
 *   2) /public/...               -> archivos estáticos con extensión permitida
 *   3) /backend/<ruta>.php       -> únicamente los endpoints listados en ENDPOINTS
 * Todo lo demás (.git, .env, *.sql, respaldos, logs, backend/config, backend/lib,
 * router.php, README.md, etc.) responde 404.
 *
 * Al crear un endpoint nuevo hay que agregarlo a ENDPOINTS; si no, responderá 404.
 * Este archivo NO sustituye a un servidor de producción (Apache/Nginx).
 */
declare(strict_types=1);

// Solo tiene sentido con el servidor embebido de PHP.
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

const PUBLIC_EXTENSIONS = [
    'html' => true, 'css' => true, 'js' => true,
    'png' => true, 'jpg' => true, 'jpeg' => true, 'gif' => true, 'svg' => true,
    'webp' => true, 'ico' => true,
    'woff' => true, 'woff2' => true, 'ttf' => true,
];

const ENDPOINTS = [
    '/backend/areas/opciones.php'             => true,
    '/backend/clientes/actualizar.php'        => true,
    '/backend/clientes/crear.php'             => true,
    '/backend/clientes/eliminar.php'          => true,
    '/backend/clientes/listar.php'            => true,
    '/backend/clientes/opciones.php'          => true,
    '/backend/clientes/ver.php'               => true,
    '/backend/empleados/actualizar.php'       => true,
    '/backend/empleados/crear.php'            => true,
    '/backend/empleados/eliminar.php'         => true,
    '/backend/empleados/listar.php'           => true,
    '/backend/habitaciones/actualizar.php'    => true,
    '/backend/habitaciones/crear.php'         => true,
    '/backend/habitaciones/eliminar.php'      => true,
    '/backend/habitaciones/listar.php'        => true,
    '/backend/habitaciones/opciones.php'      => true,
    '/backend/pagos/opciones.php'             => true,
    '/backend/reportes/cobros.php'            => true,
    '/backend/reportes/cobros_pdf.php'        => true,
    '/backend/reportes/reservaciones.php'     => true,
    '/backend/reportes/reservaciones_pdf.php' => true,
    '/backend/reportes/totales.php'           => true,
    '/backend/reportes/totales_pdf.php'       => true,
    '/backend/reservaciones/checkin.php'      => true, // existe pero hoy ninguna pantalla lo usa
    '/backend/reservaciones/checkout.php'     => true,
    '/backend/reservaciones/crear.php'        => true,
    '/backend/reservaciones/disponibles.php'  => true,
    '/backend/reservaciones/eliminar.php'     => true,
    '/backend/reservaciones/listar.php'       => true,
];

function deny(int $code = 404): bool
{
    http_response_code($code);
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: no-store');
    echo $code === 403 ? "403 Prohibido\n" : "404 No encontrado\n";
    return true; // true = la respuesta ya fue enviada por el router
}

// 1) Solo clientes locales (protección extra si alguien arranca con 0.0.0.0).
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    return deny(403);
}

// 2) Ruta cruda (sin query string). Se valida ANTES y DESPUÉS de decodificar.
$raw = (string)($_SERVER['REQUEST_URI'] ?? '/');
$raw = explode('?', $raw, 2)[0];
$raw = explode('#', $raw, 2)[0];

if ($raw === '' || $raw[0] !== '/') {
    return deny(403);
}

$path = rawurldecode($raw);

// 3) Rechazos estructurales: bytes de control, barras invertidas, doble
//    codificación (queda un '%' tras decodificar), segmentos vacíos, '.', '..'
//    o que empiecen por punto (.git, .env, .DS_Store...).
if (preg_match('/[\x00-\x1F\x7F\\\\%]/', $path)) {
    return deny(403);
}
$segments = explode('/', substr($path, 1));
foreach ($segments as $i => $seg) {
    $isLast = ($i === count($segments) - 1);
    if ($seg === '' && !$isLast) {           // "//" en medio de la ruta
        return deny(403);
    }
    if ($seg === '.' || $seg === '..' || ($seg !== '' && $seg[0] === '.')) {
        return deny(403);
    }
}

$root      = __DIR__;
$publicDir = $root . DIRECTORY_SEPARATOR . 'public';

// 4) Raíz (y /public/, que antes servía el index) -> página de inicio.
if ($path === '/' || $path === '/public/') {
    header('Location: /public/index.html', true, 302);
    return true;
}

// 5) Endpoints del backend: coincidencia EXACTA con la lista (sin PATH_INFO).
if (isset(ENDPOINTS[$path])) {
    $file = realpath($root . $path);
    $expected = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);
    if ($file === false || $file !== $expected || !is_file($file)) {
        return deny(404);
    }
    return false; // el servidor embebido ejecuta el script solicitado
}

// 6) Estáticos de /public: extensión permitida y archivo real dentro de public/.
if (str_starts_with($path, '/public/')) {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!isset(PUBLIC_EXTENSIONS[$ext])) {
        return deny(404);
    }
    $file = realpath($root . $path);
    if ($file === false || !is_file($file)
        || !str_starts_with($file, $publicDir . DIRECTORY_SEPARATOR)) {
        return deny(404);
    }
    return false; // el servidor embebido sirve el archivo estático
}

// 7) Todo lo demás: denegado.
return deny(404);
