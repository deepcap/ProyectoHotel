<?php
// backend/config/env.php
// Cargador mínimo de variables de entorno desde el archivo .env de la raíz.
// Sin dependencias externas. No se expone por HTTP (router.php lo bloquea).
declare(strict_types=1);

/**
 * Lee el archivo .env y devuelve sus pares CLAVE => valor.
 *
 * Formato admitido:  CLAVE=valor   (una por línea)
 *   - Líneas vacías y las que empiezan con # se ignoran.
 *   - CLAVE: mayúsculas, dígitos y guion bajo (p. ej. DB_HOST).
 *   - El valor puede ir entre comillas simples o dobles; se quitan.
 *   - No hay interpolación de variables ni comentarios al final de la línea.
 *
 * El archivo .env es la ÚNICA fuente: las variables del entorno del sistema
 * NO lo sustituyen. Motivo: nombres genéricos como DB_USER suelen estar
 * definidos por otras herramientas (p. ej. en ~/.zshrc) y harían que la app
 * se conectara con credenciales ajenas sin que nadie lo note.
 *
 * @return array<string,string>
 * @throws RuntimeException si el archivo no existe, no se puede leer o tiene líneas inválidas.
 */
function hotel_env_load(string $file): array
{
    if (!is_file($file) || !is_readable($file)) {
        throw new RuntimeException("No se encontró o no se puede leer el archivo de entorno: {$file}");
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException("No se pudo leer el archivo de entorno: {$file}");
    }

    $vars = [];
    foreach ($lines as $n => $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (!preg_match('/^([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/', $line, $m)) {
            // No se incluye el contenido de la línea: podría tener una contraseña.
            throw new RuntimeException('Línea inválida en .env (línea ' . ($n + 1) . ')');
        }
        $key = $m[1];
        $val = $m[2];
        $len = strlen($val);
        if ($len >= 2 && ($val[0] === '"' || $val[0] === "'") && $val[$len - 1] === $val[0]) {
            $val = substr($val, 1, -1);
        }
        $vars[$key] = $val;
    }

    return $vars;
}

/**
 * Registra un mensaje en logs/app.log (o en el log de PHP si no se puede escribir).
 * Nunca registrar contraseñas.
 */
function hotel_log(string $message): void
{
    $line = '[' . date('Y-m-d H:i:s P') . '] ' . $message . PHP_EOL;
    $file = dirname(__DIR__, 2) . '/logs/app.log';
    if (!@error_log($line, 3, $file)) {
        error_log(rtrim($line));
    }
}
