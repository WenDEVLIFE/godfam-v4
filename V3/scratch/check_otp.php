<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/MailService.php';

echo "--- TESTING SEND OTP MAIL ---\n";
$ms = new MailService();
$res = $ms->sendOTPMail('gfymchurch@gmail.com', '123456');
echo "Result: ";
var_dump($res);
