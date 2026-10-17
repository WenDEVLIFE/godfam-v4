<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';

try {
    // Add member_type column if not exists
    $pdo->exec("ALTER TABLE members ADD COLUMN IF NOT EXISTS member_type VARCHAR(50) NOT NULL DEFAULT 'Member' AFTER full_name");

    echo "Migration Successful: Added 'member_type' column to 'members'.\n";
} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
