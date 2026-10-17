<?php
function app_env(string $key): ?string
{
    $value = getenv($key);
    if ($value !== false) return $value;
    // Some PHP runtimes expose injected variables through these arrays.
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
    return is_scalar($value) ? (string)$value : null;
}

// Environment Vercel takes precedence over the optional local .env file.
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (preg_match('/^[A-Z][A-Z0-9_]*$/', $key) && app_env($key) === null) {
            putenv($key . '=' . trim(trim($value), "\"'"));
        }
    }
}
date_default_timezone_set('Asia/Jakarta');
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
