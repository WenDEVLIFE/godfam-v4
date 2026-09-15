<?php
require_once __DIR__ . '/../config/database.php';

$email = 'haruheou@gmail.com';

echo "Searching members table for $email:\n";
$stmt = $pdo->prepare("SELECT member_id, full_name, email FROM members WHERE email = ? OR full_name LIKE ?");
$stmt->execute([$email, '%Haruhe%']);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($members);

echo "\nSearching users table for $email:\n";
$stmt = $pdo->prepare("SELECT user_id, name, email, role_id FROM users WHERE email = ?");
$stmt->execute([$email]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
