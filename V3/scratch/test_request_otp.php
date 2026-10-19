<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/controllers/AuthController.php';

echo "Testing requestOTP for medinajrfrouen@gmail.com:\n";
$res = requestOTP('medinajrfrouen@gmail.com', 'Wenwen');
var_dump($res);

echo "MySQL NOW(): " . $pdo->query('SELECT NOW()')->fetchColumn() . "\n";
echo "PHP date(): " . date('Y-m-d H:i:s') . "\n";
echo "PHP timezone: " . date_default_timezone_get() . "\n";

