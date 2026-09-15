<?php
require_once __DIR__ . '/../config/database.php';

$newPassword = 'admin123';
$hashed = password_hash($newPassword, PASSWORD_DEFAULT);

try {
    // Update password for camillejanemadrid629@gmail.com and user_id 1
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'camillejanemadrid629@gmail.com' OR user_id = 1 OR role_id = 1");
    $stmt->execute([$hashed]);
    echo "Admin password updated successfully. Rows affected: " . $stmt->rowCount() . "\n";
    echo "Target Email: camillejanemadrid629@gmail.com\n";
    echo "New Plain Password: $newPassword\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
