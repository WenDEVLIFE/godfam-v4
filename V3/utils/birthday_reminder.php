<?php
/**
 * Birthday Reminder CLI Script
 * Usage: php utils/birthday_reminder.php
 * Schedule with Windows Task Scheduler to run daily.
 */

// Prevent running via browser
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("This script must be run from the command line.");
}

// Bootstrap
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/utils/MailService.php';

// ---- Fetch today's birthday members ----
$stmt = $pdo->query(
    "SELECT full_name, birthday FROM members
     WHERE birthday IS NOT NULL
       AND MONTH(birthday) = MONTH(CURDATE())
       AND DAY(birthday)   = DAY(CURDATE())
     ORDER BY full_name ASC"
);
$celebrants = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($celebrants)) {
    log_message("No birthday celebrants today (" . date('Y-m-d') . "). No email sent.");
    exit(0);
}

// ---- Fetch all admin/staff emails ----
$staffStmt = $pdo->query(
    "SELECT u.email, u.name FROM users u
     JOIN roles r ON u.role_id = r.role_id
     WHERE r.role_name IN ('Administrator','Pastor','Staff','Secretary','Committee Head')
       AND u.email IS NOT NULL"
);
$staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($staffList)) {
    log_message("No staff emails found. Cannot send reminder.");
    exit(1);
}

// ---- Build email body ----
$today      = date('F j, Y');
$namesList  = '';
foreach ($celebrants as $c) {
    $age = '';
    if (!empty($c['birthday'])) {
        $age = ' (' . (date('Y') - date('Y', strtotime($c['birthday']))) . ' yrs)';
    }
    $namesList .= "<li style='padding:4px 0;'><strong>" . htmlspecialchars($c['full_name']) . "</strong>{$age}</li>";
}

$subject = "🎂 Birthday Celebrants Today – {$today}";
$body = "<!DOCTYPE html>
<html>
<head><style>
body { font-family: 'Segoe UI', Arial, sans-serif; color: #333; background:#f4f7f6; margin:0; padding:0; }
.container { max-width:580px; margin:20px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
.header { background:#1a202c; color:#fff; padding:32px 24px; text-align:center; }
.header h1 { margin:0; font-size:22px; letter-spacing:1px; }
.content { padding:30px 28px; }
.verse { background:#f7f9fc; border-left:4px solid #3182ce; padding:12px 16px; margin:16px 0; font-style:italic; color:#4a5568; }
ul { padding-left:18px; margin:10px 0 20px; }
.footer { background:#f8f9fa; color:#7f8c8d; padding:16px; text-align:center; font-size:12px; border-top:1px solid #eee; }
</style></head>
<body>
<div class='container'>
    <div class='header'>
        <h1>🎂 God's Family United Methodist Church</h1>
        <p style='margin:6px 0 0; opacity:.8; font-size:14px;'>Birthday Celebrants – {$today}</p>
    </div>
    <div class='content'>
        <p>Good day! The following church members are celebrating their birthday <strong>today</strong>:</p>
        <ul>{$namesList}</ul>
        <p>Please take a moment to greet them and make them feel loved by the church family. 🙏</p>
        <div class='verse'>
            \"May the Lord bless you and keep you; may the Lord make his face shine on you and be gracious to you.\"
            &mdash; Numbers 6:24-25
        </div>
    </div>
    <div class='footer'>
        &copy; " . date('Y') . " God's Family United Methodist Church &mdash; Auto-generated birthday reminder
    </div>
</div>
</body>
</html>";

// ---- Send to each staff member ----
$mailer   = new MailService();
$sent     = 0;
$failed   = 0;
foreach ($staffList as $staff) {
    $result = $mailer->sendAnnouncement(
        $subject,
        strip_tags($body),           // plain-text fallback via existing method signature
        $staff['email'],
        date('M d, Y'),
        "Birthday Reminder System"
    );
    // Use direct send for HTML body
    if ($result['success']) {
        $sent++;
        log_message("Sent to: " . $staff['email']);
    } else {
        $failed++;
        log_message("FAILED to send to: " . $staff['email'] . " — " . ($result['error'] ?? 'unknown error'));
    }
}

log_message("Done. Sent: {$sent}, Failed: {$failed}. Celebrants: " . count($celebrants));
exit($failed > 0 ? 1 : 0);

// ---- Logging helper ----
function log_message(string $msg): void {
    $logDir  = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/birthday_reminder.log';
    $line    = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    echo $line;
}
