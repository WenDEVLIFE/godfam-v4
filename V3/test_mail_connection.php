<?php
/**
 * Test Mail Connection
 * 
 * Run this to verify if your SMTP settings in config/mail.php are correct.
 */

define('BASE_PATH', __DIR__);
require_once __DIR__ . '/utils/MailService.php';

echo "<h2>PHPMailer Connection Test</h2>";

$config = require __DIR__ . '/config/mail.php';
echo "<p><strong>Configured Username:</strong> {$config['username']}</p>";
echo "<p><strong>SMTP Host:</strong> {$config['host']}:{$config['port']}</p>";

$mailService = new MailService();
$testEmail = $config['username']; // Send a test to yourself

echo "<p>Attempting to send a test email to: <strong>$testEmail</strong>...</p>";

$result = $mailService->sendAnnouncement(
    "Test Connection", 
    "This is a formal test email from God's Family United Methodist Church system. If you received this, your SMTP settings are correct!", 
    $testEmail
);

if (is_array($result) && !empty($result['success'])) {
    echo "<div style='color: green; font-weight: bold; padding: 10px; border: 1px solid green;'>SUCCESS! Check your inbox (and spam folder).</div>";
} else {
    echo "<div style='color: red; font-weight: bold; padding: 10px; border: 1px solid red;'>FAILED! Error: Message could not be sent.</div>";
    echo "<p><em>Note: If using Gmail, make sure you used an 'App Password' and not your regular password.</em></p>";
}
