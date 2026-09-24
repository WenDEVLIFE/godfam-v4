<?php
/**
 * Comprehensive Daily Reminders & Greetings Utility
 * Handles Birthday Greetings, Wedding Anniversary Reminders, and Church Event Notifications.
 * Usage CLI: php utils/daily_reminders.php
 */

if (php_sapi_name() !== 'cli' && !defined('ALLOW_WEB_TRIGGER')) {
    http_response_code(403);
    die("Forbidden");
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/Member.php';
require_once BASE_PATH . '/models/Event.php';
require_once BASE_PATH . '/models/Notification.php';
require_once BASE_PATH . '/utils/MailService.php';

function processDailyReminders(PDO $pdo): array {
    $memberModel = new Member($pdo);
    $eventModel  = new Event($pdo);
    $notifModel  = new Notification($pdo);
    $mailer      = new MailService();

    $todayStr = date('F j, Y');

    // 1. Fetch Today's & Upcoming Celebrants & Events
    $bdayToday       = $memberModel->getBirthdayCelebrantsToday();
    $bdayUpcoming    = $memberModel->getBirthdayCelebrantsUpcoming(7);
    $annivToday      = $memberModel->getWeddingAnniversariesToday();
    $annivUpcoming   = $memberModel->getWeddingAnniversariesUpcoming(7);
    $eventsToday     = $eventModel->getTodayEvents();
    $eventsUpcoming  = $eventModel->getUpcomingEventsWithinDays(7);

    // 2. Fetch Staff/Leaders to notify
    $staffStmt = $pdo->query(
        "SELECT u.email, u.name FROM users u
         JOIN roles r ON u.role_id = r.role_id
         WHERE r.role_name IN ('Administrator','Pastor','Staff','Secretary','Committee Head')
           AND u.email IS NOT NULL AND u.email != ''"
    );
    $staffList = $staffStmt->fetchAll(PDO::FETCH_ASSOC);

    $stats = [
        'birthdays_today'    => count($bdayToday),
        'anniversaries_today'=> count($annivToday),
        'events_today'       => count($eventsToday),
        'notifications_logged'=> 0,
        'emails_sent'        => 0,
        'emails_failed'      => 0
    ];

    // --- A. Process Birthday Greetings & Notifications ---
    if (!empty($bdayToday)) {
        $names = implode(', ', array_column($bdayToday, 'full_name'));
        $title = "🎂 Today's Birthday Celebrants ({$todayStr})";
        $message = "Happy Birthday to: {$names}! Don't forget to send them your warm greetings today.";
        
        if (!$notifModel->existsToday('birthday', $title)) {
            $notifModel->create('birthday', $title, $message);
            $stats['notifications_logged']++;
        }

        // Direct Email Greeting to Individual Celebrants if email available
        foreach ($bdayToday as $c) {
            if (!empty($c['email'])) {
                $subject = "🎉 Happy Birthday from God's Family UMC, " . $c['full_name'] . "!";
                $htmlBody = generateCelebrantEmailHtml(
                    "🎂 Happy Birthday!",
                    "Dear " . htmlspecialchars($c['full_name']) . ",",
                    "On behalf of God's Family United Methodist Church, we wish you a blessed and wonderful birthday! May God grant you abundant peace, happiness, and prosperity in the year ahead.",
                    "\"The Lord bless you and keep you; the Lord make his face shine on you and be gracious to you.\" &mdash; Numbers 6:24-25"
                );
                $res = $mailer->sendAnnouncement($subject, strip_tags($htmlBody), $c['email'], date('M d, Y'), "God's Family UMC");
                if (!empty($res['success'])) {
                    $stats['emails_sent']++;
                } else {
                    $stats['emails_failed']++;
                }
            }
        }
    }

    // --- B. Process Wedding Anniversary Reminders & Greetings ---
    if (!empty($annivToday)) {
        $names = implode(', ', array_column($annivToday, 'full_name'));
        $title = "💍 Today's Wedding Anniversary Celebrants ({$todayStr})";
        $message = "Happy Wedding Anniversary to: {$names}! May God continue to bless their marriage and family.";

        if (!$notifModel->existsToday('anniversary', $title)) {
            $notifModel->create('anniversary', $title, $message);
            $stats['notifications_logged']++;
        }

        // Direct Email Greeting to Individual Anniversary Celebrants
        foreach ($annivToday as $c) {
            if (!empty($c['email'])) {
                $subject = "💖 Happy Wedding Anniversary from God's Family UMC!";
                $htmlBody = generateCelebrantEmailHtml(
                    "💍 Happy Wedding Anniversary!",
                    "Dear " . htmlspecialchars($c['full_name']) . ",",
                    "Congratulations on celebrating your wedding anniversary today! We thank God for your love and commitment to each other, and we pray for continuous blessings, joy, and grace upon your union.",
                    "\"And over all these virtues put on love, which binds them all together in perfect unity.\" &mdash; Colossians 3:14"
                );
                $res = $mailer->sendAnnouncement($subject, strip_tags($htmlBody), $c['email'], date('M d, Y'), "God's Family UMC");
                if (!empty($res['success'])) {
                    $stats['emails_sent']++;
                } else {
                    $stats['emails_failed']++;
                }
            }
        }
    }

    // --- C. Process Today's & Upcoming Church Event Reminders ---
    if (!empty($eventsToday)) {
        foreach ($eventsToday as $evt) {
            $title = "📅 Today's Church Event: " . $evt['title'];
            $timeStr = !empty($evt['time']) ? date('h:i A', strtotime($evt['time'])) : 'All Day';
            $locStr = !empty($evt['location']) ? " at " . $evt['location'] : '';
            $message = "Church event today: {$evt['title']}{$locStr} starting at {$timeStr}.";

            if (!$notifModel->existsToday('church_event', $title)) {
                $notifModel->create('church_event', $title, $message);
                $stats['notifications_logged']++;
            }
        }
    }

    // --- D. Send Summary Digest Email to Pastor & Staff ---
    if (!empty($staffList) && (!empty($bdayToday) || !empty($annivToday) || !empty($eventsToday))) {
        $digestSubject = "🔔 Daily Reminders Digest & Greetings – {$todayStr}";
        $digestHtml = generateStaffDigestHtml($todayStr, $bdayToday, $annivToday, $eventsToday, $bdayUpcoming, $annivUpcoming, $eventsUpcoming);

        foreach ($staffList as $staff) {
            $res = $mailer->sendAnnouncement($digestSubject, strip_tags($digestHtml), $staff['email'], date('M d, Y'), "Church Reminder System");
            if (!empty($res['success'])) {
                $stats['emails_sent']++;
            } else {
                $stats['emails_failed']++;
            }
        }
    }

    return $stats;
}

// Helper: Celebrant Direct Email HTML Template
function generateCelebrantEmailHtml(string $headerTitle, string $greetingName, string $bodyText, string $verse): string {
    $todayYear = date('Y');
    return "<!DOCTYPE html>
<html>
<head><style>
body { font-family: 'Segoe UI', Arial, sans-serif; color: #2d3748; background:#f7fafc; margin:0; padding:0; }
.container { max-width:580px; margin:24px auto; background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.06); border:1px solid #e2e8f0; }
.header { background:linear-gradient(135deg, #2b6cb0 0%, #1a365d 100%); color:#ffffff; padding:36px 24px; text-align:center; }
.header h1 { margin:0; font-size:24px; font-weight:700; letter-spacing:0.5px; }
.content { padding:32px 28px; line-height:1.6; }
.verse { background:#ebf8ff; border-left:4px solid #3182ce; padding:16px 20px; margin:24px 0; border-radius:0 8px 8px 0; font-style:italic; color:#2c5282; font-size:15px; }
.footer { background:#f7fafc; color:#a0aec0; padding:20px; text-align:center; font-size:13px; border-top:1px solid #edf2f7; }
</style></head>
<body>
<div class='container'>
    <div class='header'>
        <h1>{$headerTitle}</h1>
        <p style='margin:8px 0 0; opacity:.9; font-size:14px;'>God's Family United Methodist Church</p>
    </div>
    <div class='content'>
        <p style='font-size:16px; font-weight:600;'>{$greetingName}</p>
        <p>{$bodyText}</p>
        <div class='verse'>{$verse}</div>
        <p>Warmest regards,<br><strong>God's Family UMC Pastoral Team & Board</strong></p>
    </div>
    <div class='footer'>
        &copy; {$todayYear} God's Family United Methodist Church &bull; Automated Greetings
    </div>
</div>
</body>
</html>";
}

// Helper: Staff Digest HTML Template
function generateStaffDigestHtml($todayStr, $bdayToday, $annivToday, $eventsToday, $bdayUpcoming, $annivUpcoming, $eventsUpcoming): string {
    $bdayItems = '';
    foreach ($bdayToday as $b) {
        $age = !empty($b['birthday']) ? ' (' . (date('Y') - date('Y', strtotime($b['birthday']))) . ' yrs)' : '';
        $bdayItems .= "<li style='padding:4px 0;'><strong>" . htmlspecialchars($b['full_name']) . "</strong>{$age}</li>";
    }
    if (empty($bdayItems)) $bdayItems = "<li style='color:#a0aec0;'>No birthdays today.</li>";

    $annivItems = '';
    foreach ($annivToday as $a) {
        $years = !empty($a['wedding_anniversary']) ? ' (' . (date('Y') - date('Y', strtotime($a['wedding_anniversary']))) . ' yrs)' : '';
        $annivItems .= "<li style='padding:4px 0;'><strong>" . htmlspecialchars($a['full_name']) . "</strong>{$years}</li>";
    }
    if (empty($annivItems)) $annivItems = "<li style='color:#a0aec0;'>No wedding anniversaries today.</li>";

    $eventItems = '';
    foreach ($eventsToday as $e) {
        $timeStr = !empty($e['time']) ? date('h:i A', strtotime($e['time'])) : 'All Day';
        $loc = !empty($e['location']) ? ' @ ' . htmlspecialchars($e['location']) : '';
        $eventItems .= "<li style='padding:4px 0;'><strong>" . htmlspecialchars($e['title']) . "</strong> {$timeStr}{$loc}</li>";
    }
    if (empty($eventItems)) $eventItems = "<li style='color:#a0aec0;'>No church events today.</li>";

    return "<!DOCTYPE html>
<html>
<head><style>
body { font-family: 'Segoe UI', Arial, sans-serif; color: #2d3748; background:#f7fafc; margin:0; padding:0; }
.container { max-width:600px; margin:20px auto; background:#ffffff; border-radius:10px; overflow:hidden; border:1px solid #e2e8f0; }
.header { background:#1a202c; color:#ffffff; padding:28px 24px; text-align:center; }
.content { padding:24px; }
.section-title { font-size:16px; font-weight:700; color:#2b6cb0; border-bottom:2px solid #ebf8ff; padding-bottom:6px; margin-top:20px; }
ul { padding-left:20px; margin:10px 0; }
</style></head>
<body>
<div class='container'>
    <div class='header'>
        <h2 style='margin:0;'>🔔 Daily Reminders & Greetings Summary</h2>
        <p style='margin:6px 0 0; opacity:.85; font-size:14px;'>{$todayStr} &bull; God's Family UMC</p>
    </div>
    <div class='content'>
        <div class='section-title'>🎂 Today's Birthdays</div>
        <ul>{$bdayItems}</ul>

        <div class='section-title'>💍 Today's Wedding Anniversaries</div>
        <ul>{$annivItems}</ul>

        <div class='section-title'>📅 Today's Church Events</div>
        <ul>{$eventItems}</ul>

        <p style='margin-top:24px; font-size:13px; color:#718096;'>This notification is automatically generated for Church Pastors, Staff, and Leadership.</p>
    </div>
</div>
</body>
</html>";
}

// CLI Execution Block
if (php_sapi_name() === 'cli') {
    echo "[" . date('Y-m-d H:i:s') . "] Starting Daily Reminders & Greetings Process...\n";
    $results = processDailyReminders($pdo);
    echo "Completed!\n";
    echo "Birthdays Today: {$results['birthdays_today']}\n";
    echo "Anniversaries Today: {$results['anniversaries_today']}\n";
    echo "Events Today: {$results['events_today']}\n";
    echo "Notifications Logged: {$results['notifications_logged']}\n";
    echo "Emails Sent: {$results['emails_sent']}\n";
}
