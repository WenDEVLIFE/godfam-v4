<?php
/**
 * Birthday Reminder Trigger Endpoint
 * Triggers sending email greetings and notifications for today's birthday celebrants.
 * Admin / Staff access only.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../middleware/CSRF.php';
require_once __DIR__ . '/../../models/Member.php';
require_once __DIR__ . '/../../utils/MailService.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin() && !isStaff()) {
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

$memberModel = new Member($pdo);
$celebrants  = $memberModel->getBirthdayCelebrantsToday();

if (empty($celebrants)) {
    echo json_encode(['success' => true, 'message' => 'No birthday celebrants today.']);
    exit;
}

// Fetch staff emails
$staffStmt = $pdo->query(
    "SELECT u.email, u.name FROM users u
     JOIN roles r ON u.role_id = r.role_id
     WHERE r.role_name IN ('Administrator','Pastor','Staff','Secretary','Committee Head')
       AND u.email IS NOT NULL AND u.email != ''"
);
$staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

// Create system notifications
$today = date('F j, Y');
$names = implode(', ', array_column($celebrants, 'full_name'));
$notifStmt = $pdo->prepare(
    "INSERT INTO notifications (type, title, message, reminder_date)
     VALUES ('birthday', '🎂 Birthday Celebrants Today', ?, CURDATE())"
);
$notifMsg = "Today ($today) is the birthday of: $names. Don't forget to greet them!";
$notifStmt->execute([$notifMsg]);

// Send emails if staff list exists
$sentCount   = 0;
$failedCount = 0;

if (!empty($staffList)) {
    $namesList = '';
    foreach ($celebrants as $c) {
        $age = !empty($c['birthday']) ? ' (' . (date('Y') - date('Y', strtotime($c['birthday']))) . ' yrs)' : '';
        $namesList .= "<li style='padding:4px 0;'><strong>" . htmlspecialchars($c['full_name']) . "</strong>{$age}</li>";
    }

    $subject = "🎂 Birthday Celebrants Today – {$today}";
    $body = "<!DOCTYPE html>
    <html>
    <head><style>
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #333; background:#f4f7f6; margin:0; padding:0; }
    .container { max-width:580px; margin:20px auto; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08); }
    .header { background:#1a202c; color:#fff; padding:28px 24px; text-align:center; }
    .header h1 { margin:0; font-size:20px; letter-spacing:1px; }
    .content { padding:24px; }
    .verse { background:#f7f9fc; border-left:4px solid #3182ce; padding:12px 16px; margin:16px 0; font-style:italic; color:#4a5568; }
    ul { padding-left:18px; margin:10px 0 20px; }
    .footer { background:#f8f9fa; color:#7f8c8d; padding:16px; text-align:center; font-size:12px; border-top:1px solid #eee; }
    </style></head>
    <body>
    <div class='container'>
        <div class='header'>
            <h1>🎂 God's Family United Methodist Church</h1>
            <p style='margin:6px 0 0; opacity:.8; font-size:14px;'>Birthday Reminder &mdash; {$today}</p>
        </div>
        <div class='content'>
            <p>Good day! The following church member(s) are celebrating their birthday <strong>today</strong>:</p>
            <ul>{$namesList}</ul>
            <p>Please take a moment to greet them and make them feel loved by the church family! 🙏</p>
            <div class='verse'>
                \"May the Lord bless you and keep you; may the Lord make his face shine on you and be gracious to you.\"
                &mdash; Numbers 6:24-25
            </div>
        </div>
        <div class='footer'>
            &copy; " . date('Y') . " God's Family United Methodist Church &mdash; Birthday Reminder System
        </div>
    </div>
    </body>
    </html>";

    $mailer = new MailService();
    foreach ($staffList as $staff) {
        $res = $mailer->sendAnnouncement($subject, strip_tags($body), $staff['email'], date('M d, Y'), "Birthday Reminder System");
        if (!empty($res['success'])) {
            $sentCount++;
        } else {
            $failedCount++;
        }
    }
}

echo json_encode([
    'success' => true,
    'celebrants_count' => count($celebrants),
    'emails_sent' => $sentCount,
    'message' => "Birthday notification logged & " . ($sentCount > 0 ? "{$sentCount} reminder email(s) dispatched." : "system reminder recorded.")
]);
