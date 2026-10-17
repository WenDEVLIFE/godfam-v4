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
         WHERE r.role_name IN ('Administrator','Secretary','Treasurer')
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
                try {
                    $subject = "🎉 Happy Birthday from God's Family UMC, " . $c['full_name'] . "!";
                    $message =
                        "Dear " . htmlspecialchars($c['full_name']) . ",\n\n"
                        . "Happy Birthday! 🎂🎉\n\n"
                        . "God's Family United Methodist Church wishes you a wonderful and blessed birthday! May God continue to guide you, strengthen your faith, and fill your life with love, happiness, peace, and good health.\n\n"
                        . "May this new year of your life bring you more blessings, meaningful opportunities, and beautiful moments with your family and loved ones.\n\n"
                        . "Always remember that you are a valued member of our church family. May God's grace and love be with you today and always.\n\n"
                        . "Once again, Happy Birthday! May the Lord bless you abundantly.\n\n"
                        . "With prayers and blessings,\n"
                        . "God's Family United Methodist Church\n"
                        . "Peñaranda, Nueva Ecija\n"
                        . "Camille Jane";
                    $res = $mailer->sendAnnouncement($subject, $message, $c['email'], date('M d, Y'), "God's Family United Methodist Church");
                    if (!empty($res['success'])) {
                        $stats['emails_sent']++;
                    } else {
                        $stats['emails_failed']++;
                    }
                } catch (\Throwable $te) {
                    $stats['emails_failed']++;
                    error_log("Birthday email send error: " . $te->getMessage());
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
                try {
                    $subject = "💖 Happy Wedding Anniversary from God's Family UMC!";
                    $message =
                        "Happy Wedding Anniversary, " . htmlspecialchars($c['full_name']) . "! ❤️\n\n"
                        . "May God continue to bless your marriage with lasting love, joy, understanding, and faithfulness. May He strengthen your bond and guide you both as you continue your journey together.\n\n"
                        . "May your love grow deeper and your home remain filled with peace and God's blessings.\n\n"
                        . "Happy Anniversary, and God bless your marriage always!\n\n"
                        . "With prayers and love,\n"
                        . "God's Family United Methodist Church (GFUMC)";
                    $res = $mailer->sendAnnouncement($subject, $message, $c['email'], date('M d, Y'), "God's Family United Methodist Church (GFUMC)");
                    if (!empty($res['success'])) {
                        $stats['emails_sent']++;
                    } else {
                        $stats['emails_failed']++;
                    }
                } catch (\Throwable $te) {
                    $stats['emails_failed']++;
                    error_log("Anniversary email send error: " . $te->getMessage());
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
            try {
                $res = $mailer->sendAnnouncement($digestSubject, strip_tags($digestHtml), $staff['email'], date('M d, Y'), "Church Reminder System");
                if (!empty($res['success'])) {
                    $stats['emails_sent']++;
                } else {
                    $stats['emails_failed']++;
                }
            } catch (\Throwable $te) {
                $stats['emails_failed']++;
                error_log("Staff digest email send error: " . $te->getMessage());
            }
        }
    }

    return $stats;
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

        <p style='margin-top:24px; font-size:13px; color:#718096;'>This notification is automatically generated for Church Leadership.</p>
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
