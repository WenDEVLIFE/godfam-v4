<?php
require_once __DIR__ . '/config/database.php';
$sql = file_get_contents(__DIR__ . '/database/migrate_anniversary_reminders.sql');
try {
    $pdo->exec($sql);
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Migration Error: " . $e->getMessage() . "\n";
}
