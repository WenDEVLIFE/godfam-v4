<?php
/**
 * Minimal .env loader for local environments.
 *
 * This project primarily reads configuration via getenv(). On many XAMPP setups,
 * environment variables are not set via Apache by default; this loader allows
 * using a project-root `.env` file without adding new dependencies.
 *
 * - Safe: only sets vars that are not already defined in the environment.
 * - Format: KEY=VALUE lines, supports quoted values, ignores blank lines/comments.
 */
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

$envFile = BASE_PATH . DIRECTORY_SEPARATOR . '.env';
if (!is_file($envFile) || !is_readable($envFile)) {
    return;
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES);
if ($lines === false) return;

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) continue;

    $pos = strpos($line, '=');
    if ($pos === false) continue;

    $key = trim(substr($line, 0, $pos));
    $val = trim(substr($line, $pos + 1));
    if ($key === '') continue;

    // Do not override existing environment
    if (getenv($key) !== false) continue;

    // Remove surrounding quotes
    if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
        $val = substr($val, 1, -1);
    }

    // Normalize common booleans
    if (strcasecmp($val, 'true') === 0) $val = 'true';
    if (strcasecmp($val, 'false') === 0) $val = 'false';

    putenv($key . '=' . $val);
    $_ENV[$key] = $val;
}

