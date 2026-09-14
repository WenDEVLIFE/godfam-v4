<?php
/**
 * CSRF Protection Middleware
 * Basic implementation to generate and validate CSRF tokens.
 */

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $envSecure = getenv('SESSION_COOKIE_SECURE');
    $cookieSecure = ($envSecure === '1' || strtolower((string)$envSecure) === 'true') ? true : $isHttps;

    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => $cookieSecure,
        'cookie_samesite' => 'Lax',
    ]);
}

/**
 * Generate a new CSRF token and store it in the session if it doesn't exist.
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the CSRF token from the POST request.
 */
function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        header("HTTP/1.1 403 Forbidden");
        die("CSRF Token Validation Failed.");
    }
    return true;
}
