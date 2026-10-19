<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/utils/MailService.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/User.php';

$userModel = new User($pdo);
$emails = $userModel->getEmails();
echo "Emails found in User model:\n";
print_r($emails);

$mailService = new MailService();
echo "Testing send to pastor.benjamin@church.org:\n";
$res = $mailService->sendAnnouncement("Test", "Test body", "pastor.benjamin@church.org");
print_r($res);
