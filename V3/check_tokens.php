<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT member_id, full_name, qr_token FROM members");
foreach ($stmt->fetchAll() as $row) {
    echo "ID: " . $row['member_id'] . " | Name: " . $row['full_name'] . " | Token: " . ($row['qr_token'] ?: "NULL") . "\n";
}
