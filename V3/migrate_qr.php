<?php
require_once 'config/database.php';

try {
    echo "Updating members table...\n";
    $pdo->exec("ALTER TABLE members ADD COLUMN qr_token VARCHAR(255) UNIQUE AFTER status");
    echo "Migration successful.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column 'qr_token' already exists.\n";
    } else {
        die("Migration failed: " . $e->getMessage() . "\n");
    }
}
