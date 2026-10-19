<?php
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/utils/MailService.php';

$mailService = new MailService();
$targetEmail = 'medinajrfrouen@gmail.com';

echo "=== 1. TESTING ANNOUNCEMENT EMAIL ===\n";
$annResult = $mailService->sendAnnouncement(
    "Live Test Announcement",
    "Hello Wenwen, this is a live test announcement sent to check delivery for medinajrfrouen@gmail.com.",
    $targetEmail,
    date('M d, Y h:i A'),
    "Church Admin"
);
echo "Announcement Result: ";
print_r($annResult);

echo "\n=== 2. TESTING OTP EMAIL ===\n";
$testOtp = sprintf("%06d", random_int(100000, 999999));
$otpResult = $mailService->sendOTPMail($targetEmail, $testOtp);
echo "OTP Result ($testOtp): ";
var_dump($otpResult);
