<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Update user 1 to admin@godsfamchurch.com if it currently has camille123
    $pdo->exec("UPDATE users SET email = 'admin@godsfamchurch.com' WHERE user_id = 1 AND email = 'camille123'");
    
    // Ensure user 2 with email camillejanemadrid629@gmail.com is an active Administrator (role_id = 1)
    $pdo->exec("UPDATE users SET role_id = 1, status = 'active' WHERE email = 'camillejanemadrid629@gmail.com'");
    
    // Also update user_id 1 email directly to camillejanemadrid629@gmail.com if user 2 is deleted/merged
    // Or set user_id 1 email to camillejanemadrid629@gmail.com
    $stmt = $pdo->prepare("SELECT user_id, name, email, role_id FROM users WHERE email = 'camillejanemadrid629@gmail.com'");
    $stmt->execute();
    $admin = $stmt->fetch();

    echo "ADMIN USER CONFIGURED:\n";
    print_r($admin);

    $allAdmins = $pdo->query("SELECT user_id, name, email, role_id FROM users WHERE role_id = 1")->fetchAll();
    echo "\nALL ADMINISTRATOR USERS:\n";
    print_r($allAdmins);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
