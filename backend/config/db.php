<?php
// backend/config/db.php
// Crea la conexión $mysqli a partir de las variables del archivo .env (raíz del proyecto).
// Variables obligatorias: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS (esta puede estar vacía).
// No hay valores por defecto: si falta algo, se responde un error genérico y el detalle
// se registra en logs/app.log.
declare(strict_types=1);

require_once __DIR__ . '/env.php';

$mysqli = (static function (): mysqli {
    // Termina la petición con un 500 genérico; el detalle solo va al log.
    $fail = static function (string $detail): never {
        hotel_log('DB: ' . $detail);

        $isJson = false;
        foreach (headers_list() as $h) {
            if (stripos($h, 'content-type: application/json') === 0) {
                $isJson = true;
                break;
            }
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Cache-Control: no-store');
            if (!$isJson) {
                header('Content-Type: text/plain; charset=UTF-8');
            }
        }
        $msg = 'Servicio no disponible. Intenta más tarde.';
        echo $isJson
            ? json_encode(['ok' => false, 'msg' => $msg], JSON_UNESCAPED_UNICODE)
            : $msg . "\n";
        exit;
    };

    try {
        $env = hotel_env_load(dirname(__DIR__, 2) . '/.env');
    } catch (Throwable $e) {
        $fail($e->getMessage());
    }

    // Validación: se reportan NOMBRES de variables, nunca sus valores.
    $missing = [];
    foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER'] as $key) {
        if (!isset($env[$key]) || trim($env[$key]) === '') {
            $missing[] = $key;
        }
    }
    if (!array_key_exists('DB_PASS', $env)) {   // debe existir, aunque esté vacía
        $missing[] = 'DB_PASS';
    }
    if ($missing) {
        $fail('faltan variables obligatorias en .env: ' . implode(', ', $missing));
    }
    if (!ctype_digit($env['DB_PORT']) || (int)$env['DB_PORT'] < 1 || (int)$env['DB_PORT'] > 65535) {
        $fail('DB_PORT inválido en .env (debe ser un número entre 1 y 65535)');
    }

    try {
        $conn = new mysqli(
            $env['DB_HOST'],
            $env['DB_USER'],
            $env['DB_PASS'],
            $env['DB_NAME'],
            (int)$env['DB_PORT']
        );
        $conn->set_charset('utf8mb4');
    } catch (mysqli_sql_exception $e) {
        // El mensaje de MySQL no incluye la contraseña.
        $fail('no se pudo conectar a MySQL (código ' . $e->getCode() . '): ' . $e->getMessage());
    }

    // Por si el modo de reporte de mysqli no lanza excepciones.
    if ($conn->connect_errno) {
        $fail('no se pudo conectar a MySQL (código ' . $conn->connect_errno . '): ' . $conn->connect_error);
    }

    return $conn;
})();
