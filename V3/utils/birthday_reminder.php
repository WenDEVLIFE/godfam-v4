<?php
/**
 * Daily Reminders & Greetings CLI Script (Includes Birthdays, Wedding Anniversaries & Church Events)
 * Usage: php utils/birthday_reminder.php
 * Schedule with Windows Task Scheduler to run daily.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This script must be run from the command line.");
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/utils/daily_reminders.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting Reminders & Greetings...\n";
$stats = processDailyReminders($pdo);
echo "Done processing daily reminders & greetings!\n";
