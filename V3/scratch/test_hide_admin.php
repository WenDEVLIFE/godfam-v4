<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

$userModel = new User($pdo);

echo "Testing User::all(true) [Exclude Admin]:\n";
$usersNoAdmin = $userModel->all(true);
foreach ($usersNoAdmin as $u) {
    echo " - ID: {$u['user_id']} | Name: {$u['name']} | Role: {$u['role_name']}\n";
}

echo "\nTesting User::all(false) [Include All]:\n";
$usersAll = $userModel->all(false);
foreach ($usersAll as $u) {
    echo " - ID: {$u['user_id']} | Name: {$u['name']} | Role: {$u['role_name']}\n";
}
