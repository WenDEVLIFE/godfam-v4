<?php
/**
 * Account Creation Script for haruheou@gmail.com
 */

require_once __DIR__ . '/../config/database.php';

$email = 'haruheou@gmail.com';
$plainPassword = 'password123';
$name = 'Haruhe Ou';
$roleId = 1; // Administrator

try {
    // 1. Check if user already exists
    $checkStmt = $pdo->prepare("SELECT user_id, email FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update password if account already exists
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ?, name = ?, role_id = ? WHERE user_id = ?");
        $updateStmt->execute([$hashedPassword, $name, $roleId, $existing['user_id']]);
        echo "SUCCESS: User account already existed (User ID: {$existing['user_id']}). Credentials and password updated successfully.\n";
    } else {
        // Create member record if needed, or link to user
        $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE email = ?");
        $memberStmt->execute([$email]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
        $memberId = $member ? $member['member_id'] : null;

        if (!$memberId) {
            $qrToken = bin2hex(random_bytes(16));
            $insertMember = $pdo->prepare("INSERT INTO members (full_name, email, status, qr_token) VALUES (?, ?, 'active', ?)");
            $insertMember->execute([$name, $email, $qrToken]);
            $memberId = $pdo->lastInsertId();
            echo "INFO: Created linked member record (Member ID: {$memberId}).\n";
        }

        // Insert new user account
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
        $insertUser = $pdo->prepare("INSERT INTO users (role_id, member_id, name, email, password) VALUES (?, ?, ?, ?, ?)");
        $insertUser->execute([$roleId, $memberId, $name, $email, $hashedPassword]);
        $userId = $pdo->lastInsertId();

        echo "SUCCESS: Account created successfully!\n";
        echo "User ID: {$userId}\n";
        echo "Member ID: {$memberId}\n";
        echo "Name: {$name}\n";
        echo "Email: {$email}\n";
        echo "Role ID: {$roleId} (Administrator)\n";
    }
} catch (Exception $e) {
    echo "ERROR: Failed to create account: " . $e->getMessage() . "\n";
    exit(1);
}
