<?php
require_once 'config/database.php';
$stmt = $pdo->query("SELECT * FROM roles");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['role_id'] . ": " . $row['role_name'] . "\n";
}
