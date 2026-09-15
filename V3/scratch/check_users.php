<?php
require_once __DIR__ . '/../config/database.php';

$users = $pdo->query("SELECT user_id, role_id, member_id, name, email FROM users")->fetchAll();
echo "ALL USERS IN DATABASE:\n";
foreach ($users as $u) {
    echo "ID: " . $u['user_id'] . " | Role: " . $u['role_id'] . " | Name: " . $u['name'] . " | Email: " . $u['email'] . "\n";
}
