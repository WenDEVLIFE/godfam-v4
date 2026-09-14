<?php
/**
 * Dashboard View - Primary landing after login.
 * Adapts to Admin, Staff, and Member roles.
 */

$page_title = 'Dashboard Overview';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../models/Member.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Attendance.php';

// Ensure user is logged in
requireLogin();

// Fetch stats based on roles
$memberModel = new Member($pdo);
$eventModel = new Event($pdo);
$attendanceModel = new Attendance($pdo);

$total_members = 0;
$upcoming_events_count = 0;
$today_attendance = 0;
$my_attendance_count = 0;
$my_qr_token = '';

if (isAdmin() || isStaff()) {
    // Admin/Staff Stats
    $stmt = $pdo->query("SELECT COUNT(*) FROM members");
    $total_members = $stmt->fetchColumn();
    
    $upcoming_events_count = $eventModel->countUpcoming();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE()");
    $today_attendance = $stmt->fetchColumn();
}

if (isMember()) {
    // Member Specific Data
    $member_id = $_SESSION['member_id'] ?? null;
    if (!$member_id && !empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT member_id FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$_SESSION['user_id']]);
        $member_id = $stmt->fetchColumn();
        if ($member_id) {
            $_SESSION['member_id'] = (int)$member_id;
        }
    }

    $my_attendance_logs = [];
    $missed_sessions = 0;

    if ($member_id) {
        $my_attendance_count = $attendanceModel->countAttendedServices($member_id);
        $my_attendance_logs = $attendanceModel->getByMember($member_id);
        
        $past_events = $eventModel->getPast();
        $past_events_count = count($past_events);
        $missed_sessions = max(0, $past_events_count - $my_attendance_count);
        
        $member_data = $memberModel->find($member_id);
        $my_qr_token = $member_data['qr_token'] ?? '';
    }
}

// Get recent events for all dashboards
$recent_events = $eventModel->getUpcoming(5);

// Include Layout Header
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="mb-4">
    <h1 class="page-title">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></h1>
    <p class="text-muted small">System Management Console Dashboard</p>
</div>

<?php if (isAdmin() || isStaff()): ?>
<!-- Admin/Staff Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class='bx bxs-group'></i></div>
        <div>
            <div class="stat-label">Total Members</div>
            <div class="stat-number"><?php echo number_format($total_members); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class='bx bxs-calendar'></i></div>
        <div>
            <div class="stat-label">Upcoming Events</div>
            <div class="stat-number"><?php echo number_format($upcoming_events_count); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon"><i class='bx bxs-check-square'></i></div>
        <div>
            <div class="stat-label">Attended Today</div>
            <div class="stat-number"><?php echo number_format($today_attendance); ?></div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">Management Quick Actions</div>
    <div class="card-body">
        <div class="d-flex gap-2">
            <a href="members.php" class="btn btn-outline-primary">Manage Members</a>
            <a href="events.php" class="btn btn-outline-primary">Manage Events</a>
            <a href="scan_attendance.php" class="btn btn-primary">Scan QR Attendance</a>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (isMember()): ?>
<!-- Member Dashboard -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class='bx bxs-check-circle'></i></div>
        <div>
            <div class="stat-label">Services Attended</div>
            <div class="stat-number"><?php echo number_format($my_attendance_count); ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: #fef2f2; color: #dc2626;"><i class='bx bxs-x-circle'></i></div>
        <div>
            <div class="stat-label">Missed Services</div>
            <div class="stat-number text-danger"><?php echo number_format($missed_sessions); ?></div>
        </div>
    </div>
    
    <a href="profile.php" class="stat-card" style="text-decoration: none; color: inherit;">
        <div class="stat-icon"><i class='bx bxs-id-card'></i></div>
        <div>
            <div class="stat-label">My Digital ID</div>
            <div class="stat-number">View QR</div>
        </div>
    </a>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>My Recent Attendance</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($my_attendance_logs)): ?>
            <p class="p-4 text-center text-muted">No attendance recorded yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date Attended</th>
                            <th>Event Title</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($my_attendance_logs, 0, 5) as $log): ?>
                        <tr>
                            <td class="small"><?php echo date('M d, Y', strtotime($log['date'])); ?></td>
                            <td class="font-weight-600"><?php echo htmlspecialchars($log['event_title'] ?? 'Church Service'); ?></td>
                            <td><span class="badge badge-success"><?php echo htmlspecialchars($log['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Recent & Upcoming Events</span>
        <a href="events.php" class="small">View All</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recent_events)): ?>
            <p class="p-4 text-center text-muted">No events scheduled.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Event Title</th>
                            <th>Location</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_events as $event): ?>
                        <tr>
                            <td class="small"><?php echo date('M d, Y', strtotime($event['date'])); ?></td>
                            <td class="font-weight-600"><?php echo htmlspecialchars($event['title']); ?></td>
                            <td><?php echo htmlspecialchars($event['location'] ?? 'Main Hall'); ?></td>
                            <td class="small"><?php echo date('h:i A', strtotime($event['time'] ?? '00:00:00')); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</div>

<?php 
// Include Layout Footer
include __DIR__ . '/layout/footer.php';
?>
