<?php
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE role_id = 1 OR user_id = 1 OR email = 'admin@church.com'");
    $stmt->execute(['camillejanemadrid629@gmail.com']);
    echo "Admin email updated in database. Rows affected: " . $stmt->rowCount() . "\n";

    $check = $pdo->query("SELECT user_id, name, email, role_id FROM users WHERE role_id = 1")->fetchAll();
    echo "Current Admin User(s):\n";
    print_r($check);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
