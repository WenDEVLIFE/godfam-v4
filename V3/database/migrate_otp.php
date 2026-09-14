<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';

try {
    // Add attempts column if not exists
    $pdo->exec("ALTER TABLE password_resets ADD COLUMN IF NOT EXISTS attempts INT DEFAULT 0");
    
    // Increase OTP column size for hashing
    $pdo->exec("ALTER TABLE password_resets MODIFY COLUMN otp VARCHAR(255) NOT NULL");
    
    echo "Migration Successful: Added 'attempts' and increased 'otp' column size.\n";
} catch (PDOException $e) {
    echo "Migration Failed: " . $e->getMessage() . "\n";
}
