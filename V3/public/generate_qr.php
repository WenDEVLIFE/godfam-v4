<?php
/**
 * Entry point for QR Code generation.
 * This script initializes the QrController and generates the QR code for a member.
 */

// Define project base path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/controllers/QrController.php';
require_once BASE_PATH . '/config/database.php';

// Ensure user is logged in
requireLogin();

$member_id = $_GET['id'] ?? null;

if (!$member_id) {
    header("HTTP/1.0 400 Bad Request");
    echo "Member ID is required.";
    exit;
}

// Initialize Controller and Generate
$qrController = new QrController($pdo);
$qrController->generate($member_id);
