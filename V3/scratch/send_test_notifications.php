<?php
/**
 * Send real test notifications from the CLI.
 *
 * Usage: php scratch/send_test_notifications.php [email]
 */

if (php_sapi_name() !== 'cli') {
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/utils/TestNotifier.php';

$email = isset($argv[1]) && trim((string)$argv[1]) !== ''
    ? trim((string)$argv[1])
    : 'camillemadrid629@gmail.com';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "FAIL - Invalid recipient email address: {$email}\n";
    exit(1);
}

$notifier = new TestNotifier($pdo);

$results = [];
$results[] = $notifier->sendAnnouncementEmail(
    $email,
    $notifier->latestAnnouncementImage(),
    'Sample Announcement',
    'This is a test announcement with an attached photo. If you can see the picture below, image delivery is working.'
);
$results[] = $notifier->sendBirthdayEmail($email);
$results[] = $notifier->sendAnniversaryEmail($email);

$exitCode = 0;
foreach ($results as $result) {
    $status = !empty($result['success']) ? 'OK' : 'FAIL';
    if ($status === 'FAIL') {
        $exitCode = 1;
    }
    echo $status . ' - ' . (string)($result['label'] ?? 'Item') . ' - ' . (string)($result['detail'] ?? '') . "\n";
}

exit($exitCode);
