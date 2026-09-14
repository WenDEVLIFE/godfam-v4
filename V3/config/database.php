<?php
/**
 * Database Configuration
 * Uses PDO for secure database interactions.
 */

// Ensure BASE_PATH is defined only once
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Load project-root .env if present (local-friendly)
require_once BASE_PATH . '/config/env.php';

/**
 * Environment-based configuration.
 * Defaults are suitable for local XAMPP only.
 */
$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_NAME = getenv('DB_NAME') ?: 'churchgods';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS');
if ($DB_PASS === false) $DB_PASS = '';
$DB_CHARSET = getenv('DB_CHARSET') ?: 'utf8mb4';

$APP_DEBUG = getenv('APP_DEBUG');
$APP_DEBUG = ($APP_DEBUG === '1' || strtolower((string)$APP_DEBUG) === 'true');

try {
    $dsn = "mysql:host=" . $DB_HOST . ";dbname=" . $DB_NAME . ";charset=" . $DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (\PDOException $e) {
    error_log('Database Connection Failed: ' . $e->getMessage());
    if ($APP_DEBUG) {
        die("Database Connection Failed: " . $e->getMessage());
    }
    die("Database Connection Failed. Please contact the administrator.");
}
