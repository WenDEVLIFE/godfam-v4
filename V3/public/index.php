<?php
/**
 * Application entry point (V3/public/index.php).
 *
 * Lets the site be served with this folder as the document root, e.g.
 * https://your-domain.com/  ->  dashboard (if signed in) or login page.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/middleware/AuthMiddleware.php';

$target = isAuthenticated() ? 'dashboard.php' : 'login.php';

header('Location: ' . $target, true, 302);
exit;
