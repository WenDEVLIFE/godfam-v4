<?php
/**
 * Daily Reminders & Greetings Trigger Endpoint
 * Triggers sending email greetings and logging notifications for birthdays, wedding anniversaries, and church events.
 * Admin / Staff access only.
 */

define('ALLOW_WEB_TRIGGER', true);
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../middleware/CSRF.php';
require_once __DIR__ . '/../../utils/daily_reminders.php';

header('Content-Type: application/json; charset=utf-8');

if (!isStaff()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$csrfToken = $_POST['csrf_token'] ?? ($input['csrf_token'] ?? '');
if (!validateCsrfToken($csrfToken)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

try {
    // Manual trigger always forces a run (and marks today as processed so the
    // automatic dashboard run does not duplicate it).
    $results = runDailyRemindersOncePerDay($pdo, true);
    if ($results === null) {
        $results = ['birthdays_today' => 0, 'anniversaries_today' => 0, 'events_today' => 0, 'emails_sent' => 0, 'emails_failed' => 0];
    }

    $msg = "Reminders & Greetings processed successfully! (" .
           "Birthdays: {$results['birthdays_today']}, " .
           "Anniversaries: {$results['anniversaries_today']}, " .
           "Events: {$results['events_today']})";
    if ($results['emails_sent'] > 0) {
        $msg .= " - Sent {$results['emails_sent']} emails.";
    }

    echo json_encode([
        'success' => true,
        'birthdays_today' => $results['birthdays_today'],
        'anniversaries_today' => $results['anniversaries_today'],
        'events_today' => $results['events_today'],
        'emails_sent' => $results['emails_sent'],
        'emails_failed' => $results['emails_failed'] ?? 0,
        'message' => $msg
    ]);
} catch (\Throwable $e) {
    http_response_code(200);
    echo json_encode([
        'success' => false,
        'error' => 'Error processing daily reminders: ' . $e->getMessage()
    ]);
}
