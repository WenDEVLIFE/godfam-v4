<?php
require_once __DIR__ . '/../config/database.php';
$stmt = $pdo->query("SELECT password FROM users WHERE email = 'camillejanemadrid629@gmail.com' LIMIT 1");
echo $stmt->fetchColumn() . "\n";
