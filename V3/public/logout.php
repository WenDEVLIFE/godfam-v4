<?php
/**
 * Logout Handler
 * Standardizes the logout process for all users.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../controllers/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    die("Method Not Allowed");
}

// CSRF protection for logout
validateCsrfToken($_POST['csrf_token'] ?? '');

// Trigger the logout logic in AuthController
logout();
