<?php
define('BASE_PATH', dirname(__DIR__));
require_once __DIR__ . '/database.php';

try {
    // Check if email column exists using MySQL syntax
    $stmt = $pdo->query("SHOW COLUMNS FROM members");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasEmail = false;
    $hasPhone = false;

    foreach ($cols as $col) {
        if ($col['Field'] === 'email') $hasEmail = true;
        if ($col['Field'] === 'phone') $hasPhone = true;
    }

    if (!$hasEmail) {
        $pdo->exec("ALTER TABLE members ADD COLUMN email VARCHAR(100) AFTER full_name");
        echo "Added 'email' column.<br>";
    }
    if (!$hasPhone) {
        $pdo->exec("ALTER TABLE members ADD COLUMN phone VARCHAR(20) AFTER email");
        echo "Added 'phone' column.<br>";
    }

    echo "Database migration complete.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
