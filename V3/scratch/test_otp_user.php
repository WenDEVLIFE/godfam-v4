<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/utils/MailService.php';

$ms = new MailService();
echo "Testing sendOTPMail to medinajrfrouen@gmail.com...\n";
$res = $ms->sendOTPMail('medinajrfrouen@gmail.com', '987654');
var_dump($res);
