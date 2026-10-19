<?php
/**
 * QR Attendance Scanner - Role-based attendance tracking.
 * Accessible by Admin and Staff.
 */

$page_title = 'Attendance Scanner';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';
require_once __DIR__ . '/../controllers/EventController.php';

// Ensure user is authorized
if (!canManage()) {
    header("Location: dashboard.php");
    exit;
}

$eventController = new EventController($pdo);
$attendanceController = new AttendanceController($pdo);

// Find all events
$event_list = $eventController->index();
$eventId = !empty($_GET['event_id']) ? (int)$_GET['event_id'] : null;

// Fallback: If no event_id provided, automatically resolve best matching event (today, upcoming, or latest)
if (!$eventId && !empty($event_list)) {
    $today = date('Y-m-d');
    // 1. Event happening today
    foreach ($event_list as $e) {
        if ($e['date'] === $today) {
            $eventId = (int)$e['event_id'];
            break;
        }
    }
    // 2. Next upcoming event
    if (!$eventId) {
        foreach ($event_list as $e) {
            if ($e['date'] >= $today) {
                $eventId = (int)$e['event_id'];
                break;
            }
        }
    }
    // 3. Most recent event
    if (!$eventId && !empty($event_list[0])) {
        $eventId = (int)$event_list[0]['event_id'];
    }
}

// Only redirect to events.php if there are literally zero events created in the system
if (!$eventId || empty($event_list)) {
    header("Location: events.php");
    exit;
}

$event_data = null;
foreach($event_list as $e) {
    if((int)$e['event_id'] === (int)$eventId) {
        $event_data = $e;
        break;
    }
}

if (!$event_data && !empty($event_list[0])) {
    $event_data = $event_list[0];
    $eventId = (int)$event_data['event_id'];
}

$error = '';
$success = '';

// Handle Scan Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF Token Validation Failed.");
    }
    
    // We expect qr_token or member_id + event_id + date
    $result = $attendanceController->recordByQr($_POST, $pdo);
    
    // recordByQr() returns boolean true on success, or a descriptive error string on failure
    if ($result === true) {
        $success = "Attendance recorded successfully.";
    } else {
        $error = $result; // Already a descriptive error string from the controller
    }
}

$csrf_token = generateCsrfToken();

// Fetch Recent Scans for Live Feed
$stmt = $pdo->prepare("
    SELECT a.*, m.full_name, m.email 
    FROM attendance a 
    JOIN members m ON a.member_id = m.member_id 
    WHERE a.event_id = ? 
    ORDER BY a.attendance_id DESC
");
$stmt->execute([$eventId]);
$recentScans = $stmt->fetchAll();

// Fetch All Members for Manual Dropdown Fallback
$memberStmt = $pdo->query("SELECT member_id, full_name, member_type, email, phone, qr_token FROM members ORDER BY full_name ASC");
$allMembersList = $memberStmt->fetchAll();

// Include Layout Header
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<style>
.mode-toggle-container {
    display: flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 10px;
    gap: 4px;
    margin-bottom: 1rem;
}
.mode-toggle-btn {
    flex: 1;
    text-align: center;
    padding: 10px 14px;
    font-size: 0.88rem;
    font-weight: 700;
    border-radius: 8px;
    cursor: pointer;
    border: none;
    background: transparent;
    color: #64748b;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.mode-toggle-btn.active {
    background: #ffffff;
    color: var(--primary-color, #1e3a8a);
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.scanner-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 4px 10px;
    border-radius: 20px;
    background: rgba(34, 197, 94, 0.1);
    color: #16a34a;
}
.scanner-status-pulse {
    width: 8px;
    height: 8px;
    background: #22c55e;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
    animation: pulse-ring 1.8s infinite;
}
@keyframes pulse-ring {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}
#reader {
    border: none !important;
    border-radius: 8px;
    overflow: hidden;
}
#reader video {
    border-radius: 8px;
    width: 100% !important;
    height: auto !important;
    object-fit: cover;
}
#reader #qr-shaded-region,
#reader svg,
#reader .qr-shaded-region {
    display: none !important;
}

/* Scanner 2-Column Responsive Layout */
.scanner-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 24px;
    align-items: start;
}
@media (min-width: 992px) {
    .scanner-grid {
        grid-template-columns: 380px 1fr;
    }
}
@media (min-width: 1280px) {
    .scanner-grid {
        grid-template-columns: 410px 1fr;
    }
}

/* Facebook-Style Auto-Suggest Search Bar */
.fb-suggest-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    max-height: 250px;
    overflow-y: auto;
    z-index: 1060;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.08);
}
.fb-suggest-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s ease;
}
.fb-suggest-item:last-child {
    border-bottom: none;
}
.fb-suggest-item:hover,
.fb-suggest-item.active {
    background: #e7f0fd;
}
.fb-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.8rem;
    color: #1e3a8a;
    background: #dbeafe;
    flex-shrink: 0;
    overflow: hidden;
}
.fb-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.fb-suggest-name {
    font-weight: 700;
    color: #1e293b;
    font-size: 0.84rem;
    line-height: 1.25;
}
.fb-suggest-sub {
    font-size: 0.72rem;
    color: #64748b;
    line-height: 1.25;
    margin-top: 1px;
}
.fb-suggest-empty {
    padding: 14px;
    text-align: center;
    color: #94a3b8;
    font-size: 0.82rem;
}
.fb-highlight {
    background: #fef08a;
    font-weight: 800;
    padding: 0 2px;
    border-radius: 2px;
}
.fb-member-type-pill {
    display: inline-block;
    padding: 2px 8px;
    font-size: 0.68rem;
    font-weight: 700;
    line-height: 1.2;
    color: #334155;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    white-space: nowrap;
}

/* Manual Entry search block */
.fb-manual-heading {
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.6px;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 8px;
}
.fb-search-wrap {
    position: relative;
}
.fb-search-input {
    width: 100%;
    height: 42px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    padding-left: 38px;
    padding-right: 70px;
    font-size: 0.88rem;
    box-shadow: none;
}
.fb-search-input:focus {
    border-color: #93c5fd;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}
.fb-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.1rem;
    color: #94a3b8;
    pointer-events: none;
}
.fb-search-caret {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.1rem;
    color: #94a3b8;
    pointer-events: none;
}
.fb-search-clear-btn {
    position: absolute;
    right: 32px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #94a3b8;
    font-size: 1.1rem;
    line-height: 1;
    padding: 2px 4px;
    cursor: pointer;
}
.fb-search-clear-btn:hover {
    color: #475569;
}
.fb-record-btn {
    width: 100%;
    height: 40px;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
</style>

<div class="fade-in py-2">
    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 style="font-weight: 800; font-size: 1.85rem; color: var(--primary-color); margin-bottom: 6px;">Attendance Scanner</h2>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge badge-info" style="font-size: 0.85rem; padding: 6px 12px; font-weight: 700; white-space: normal; word-break: break-word; overflow-wrap: break-word; max-width: 100%; line-height: 1.4; display: inline-block;">
                    Event: <?php echo htmlspecialchars($event_data['title']); ?>
                </span>
                <?php if (count($event_list) > 1): ?>
                    <select class="form-select form-select-sm d-inline-block" style="width: auto; height: 32px; font-size: 0.82rem; border-color: #cbd5e1; border-radius: 6px;" onchange="if(this.value) window.location.href='scan_attendance.php?event_id=' + this.value;" title="Switch to another event">
                        <?php foreach ($event_list as $ev): ?>
                            <option value="<?php echo (int)$ev['event_id']; ?>" <?php echo ((int)$ev['event_id'] === (int)$eventId) ? 'selected' : ''; ?>>
                                Switch: <?php echo htmlspecialchars($ev['title']); ?> (<?php echo date('M d', strtotime($ev['date'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <span class="text-muted small" style="font-weight: 500;">
                    <i class='bx bx-calendar'></i> <?php echo date('M d, Y', strtotime($event_data['date'])); ?>
                </span>
                <?php if (!empty($event_data['location'])): ?>
                    <span class="text-muted small" style="font-weight: 500;">
                        &bull; <i class='bx bx-map'></i> <?php echo htmlspecialchars($event_data['location']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div>
            <a href="attendance.php?event_id=<?php echo $eventId; ?>" class="btn btn-outline-primary shadow-sm" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; font-weight: 600; border-radius: 8px;">
                <i class='bx bx-list-ul' style="font-size: 1.2rem;"></i> View Attendance Log
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger shadow-sm mb-4" style="border-radius: 8px; padding: 14px 18px;">
            <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success shadow-sm mb-4" style="border-radius: 8px; padding: 14px 18px;">
            <i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- Main Scanner Layout Grid -->
    <div class="scanner-grid mb-4">
        <!-- Left Column: Scanner Window & Controls -->
        <div class="scanner-left-pane">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-transparent py-3 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
                    <h5 style="margin: 0; font-weight: 800; color: var(--dark-text); font-size: 1.05rem;">LIVE SCANNER</h5>
                    <span class="scanner-status-badge">
                        <span class="scanner-status-pulse"></span> Ready
                    </span>
                </div>
                <div class="card-body p-3">
                    <!-- Camera Source Switcher -->
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2 p-2" style="background: #f1f5f9; border-radius: 8px;">
                        <label for="cameraSelect" class="small font-weight-700 text-muted mb-0 d-flex align-items-center gap-1" style="font-size: 0.78rem; white-space: nowrap;">
                            <i class='bx bx-camera text-primary'></i> Camera:
                        </label>
                        <select id="cameraSelect" class="form-select form-select-sm" style="font-size: 0.82rem; height: 32px; border-radius: 6px; border-color: #cbd5e1; flex: 1;">
                            <option value="">Detecting cameras...</option>
                        </select>
                        <button type="button" id="btnRestartCamera" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center" title="Refresh Cameras" onclick="initCamera()" style="height: 32px; padding: 0 8px; border-radius: 6px;">
                            <i class='bx bx-refresh'></i>
                        </button>
                    </div>

                    <!-- Scanner Box -->
                    <div id="reader" style="width: 100%; min-height: 240px; background: #0f172a; border-radius: 8px; overflow: hidden; position: relative;">
                        <div id="scannerOverlay" style="display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 240px; color: #94a3b8; text-align: center; padding: 20px;">
                            <i class='bx bx-loader-alt bx-spin' style="font-size: 2.2rem; color: #3b82f6; margin-bottom: 8px;"></i>
                            <span id="scannerStatusText" style="font-size: 0.88rem; font-weight: 600;">Initializing camera...</span>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent p-4" style="border-top: 1px solid rgba(0,0,0,0.05);">
                    <form id="scan-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($eventId); ?>">
                        <input type="hidden" name="date" value="<?php echo htmlspecialchars($event_data['date']); ?>">
                        <input type="hidden" name="mode" id="scan_mode" value="time_in">
                        <input type="hidden" name="status" id="scan_status" value="Present">
                        <input type="hidden" name="member_id" id="scan_member_id" value="">

                        <!-- Mode Segmented Toggle Buttons -->
                        <div class="mode-toggle-container">
                            <button type="button" class="mode-toggle-btn active" id="btnModeCheckIn" onclick="setScanMode('time_in')">
                                <i class='bx bx-log-in-circle'></i> Check In
                            </button>
                            <button type="button" class="mode-toggle-btn" id="btnModeTimeOut" onclick="setScanMode('time_out')">
                                <i class='bx bx-log-out-circle'></i> Time Out
                            </button>
                        </div>

                        <!-- QR token is filled by the camera scanner (or manual search) and submitted automatically -->
                        <input type="hidden" name="qr_token" id="qr_token" value="">
                    </form>
                    <p class="text-muted small mt-2 mb-0" style="font-size: 0.8rem;">
                        <i class='bx bx-info-circle'></i> The camera submits automatically when a QR code is scanned.
                    </p>

                    <!-- General Search Bar with Facebook-Style Auto-Suggest -->
                    <div class="mt-3 pt-3 border-top position-relative" id="scannerSearchContainer">
                        <div class="fb-manual-heading">Manual Entry</div>
                        <label for="fb_member_search" class="form-label font-weight-700 small text-muted mb-1" style="font-size: 0.82rem;">Search Member</label>
                        <div class="fb-search-wrap">
                            <i class='bx bx-search fb-search-icon'></i>
                            <input type="text" id="fb_member_search" class="fb-search-input" placeholder="Type member name..." autocomplete="off">
                            <button type="button" id="btn_clear_search" class="fb-search-clear-btn" style="display: none;" onclick="clearMemberSearch()" title="Clear">
                                <i class='bx bx-x'></i>
                            </button>
                            <i class='bx bx-chevron-down fb-search-caret'></i>
                            <!-- Floating Facebook-style Auto-Suggest Dropdown Results -->
                            <div id="fb_suggest_box" class="fb-suggest-dropdown shadow-lg" style="display: none;"></div>
                        </div>
                        <button type="button" class="btn btn-primary fb-record-btn mt-2" onclick="submitManualSearch()">
                            <i class='bx bx-check'></i> Record Attendance
                        </button>
                        <p class="text-muted small mt-2 mb-0" style="font-size: 0.78rem;">
                            <i class='bx bx-info-circle'></i> If QR code doesn't scan, type name above. Click any suggested match or press Enter to record attendance.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Scanner Tips Card -->
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-header bg-transparent py-3 px-4" style="border-bottom: 1px solid rgba(0,0,0,0.05);">
                    <h5 style="margin: 0; font-weight: 800; color: var(--dark-text); font-size: 0.95rem;">SCANNING GUIDELINES</h5>
                </div>
                <div class="card-body p-4">
                    <ul class="list-unstyled mb-0" style="font-size: 0.88rem; color: #64748b;">
                        <li class="mb-2 d-flex align-items-center gap-2">
                            <i class='bx bx-sun text-warning' style="font-size: 1.1rem;"></i> Ensure adequate lighting for camera scanning.
                        </li>
                        <li class="mb-2 d-flex align-items-center gap-2">
                            <i class='bx bx-mobile-alt text-info' style="font-size: 1.1rem;"></i> Maximize screen brightness on mobile QR passes.
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class='bx bx-edit text-primary' style="font-size: 1.1rem;"></i> If a QR won't scan, use the manual name search below.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Column: Live Recent Scans Feed -->
        <div class="scanner-right-pane">
            <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; min-height: 520px;">
                <div class="card-header bg-transparent py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
                    <h5 style="margin: 0; font-weight: 800; color: var(--dark-text); font-size: 1.05rem;">LIVE RECENT SCANS</h5>
                    <span class="badge badge-info" style="font-size: 12px; padding: 6px 12px; border-radius: 20px;">
                        <?php echo count($recentScans); ?> Checked In
                    </span>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($recentScans)): ?>
                        <div class="mb-3">
                            <input type="text" id="scanSearchInput" class="form-control" placeholder="Search scanned attendees..." onkeyup="filterScanFeed()" style="height: 42px; border-radius: 8px;">
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0" id="recentScansTable">
                                <thead>
                                    <tr>
                                        <th style="padding-left: 12px;">Attendee Name</th>
                                        <th>Email Address</th>
                                        <th>Check In Time</th>
                                        <th>Time Out</th>
                                        <th class="text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentScans as $scan): ?>
                                    <tr style="border-bottom: 1px solid rgba(0,0,0,0.03);">
                                        <td style="padding-left: 12px; font-weight: 700; color: var(--dark-text);">
                                            <div class="d-flex align-items-center gap-2">
                                                <div style="width: 32px; height: 32px; background: rgba(30, 58, 138, 0.08); color: var(--navy-blue); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.8rem;">
                                                    <?php echo strtoupper(substr($scan['full_name'], 0, 1)); ?>
                                                </div>
                                                <div><?php echo htmlspecialchars($scan['full_name']); ?></div>
                                            </div>
                                        </td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($scan['email'] ?? '---'); ?></td>
                                        <td class="small font-weight-600">
                                            <i class='bx bx-time-five text-success'></i> <?php echo date('h:i A', strtotime($scan['created_at'])); ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo !empty($scan['time_out']) ? date('h:i A', strtotime($scan['time_out'])) : '---'; ?>
                                        </td>
                                        <td class="text-right">
                                            <?php if (!empty($scan['time_out'])): ?>
                                                <span class="badge badge-secondary" style="background: #e2e8f0; color: #475569; font-size: 11px;">TIMED OUT</span>
                                            <?php else: ?>
                                                <span class="badge badge-success" style="font-size: 11px;">PRESENT</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class='bx bx-qr-scan' style="font-size: 3.5rem; color: #cbd5e1; display: block; margin-bottom: 1rem;"></i>
                            <h5 style="font-weight: 700; color: #64748b;">No Attendance Scans Yet</h5>
                            <p class="small mb-0">Scanned attendees for this event will appear in real-time here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Scanner Script -->
<script src="assets/js/html5-qrcode.min.js" type="text/javascript"></script>
<script>
    let html5QrCode = null;
    let currentCameraId = null;
    let isScanning = false;

    function setScanMode(mode) {
        document.getElementById('scan_mode').value = mode;
        const btnCheckIn = document.getElementById('btnModeCheckIn');
        const btnTimeOut = document.getElementById('btnModeTimeOut');
        
        if (mode === 'time_out') {
            document.getElementById('scan_status').value = 'Time Out';
            btnCheckIn.classList.remove('active');
            btnTimeOut.classList.add('active');
        } else {
            document.getElementById('scan_status').value = 'Present';
            btnTimeOut.classList.remove('active');
            btnCheckIn.classList.add('active');
        }
    }

    function filterScanFeed() {
        const input = document.getElementById('scanSearchInput');
        if (!input) return;
        const filter = input.value.toLowerCase();
        const table = document.getElementById('recentScansTable');
        if (!table) return;
        const trs = table.getElementsByTagName('tr');

        for (let i = 1; i < trs.length; i++) {
            const tdName = trs[i].getElementsByTagName('td')[0];
            const tdEmail = trs[i].getElementsByTagName('td')[1];
            if (tdName || tdEmail) {
                const nameText = tdName ? tdName.textContent || tdName.innerText : '';
                const emailText = tdEmail ? tdEmail.textContent || tdEmail.innerText : '';
                if (nameText.toLowerCase().indexOf(filter) > -1 || emailText.toLowerCase().indexOf(filter) > -1) {
                    trs[i].style.display = '';
                } else {
                    trs[i].style.display = 'none';
                }
            }
        }
    }

    const allMembers = <?php echo json_encode($allMembersList, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    let searchActiveIndex = -1;
    let currentFilteredMembers = [];

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function(char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
        });
    }

    function highlightMatch(text, query) {
        if (!query) return escapeHtml(text);
        const safeText = escapeHtml(text);
        const safeQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp('(' + safeQuery + ')', 'gi');
        return safeText.replace(regex, '<span class="fb-highlight">$1</span>');
    }

    function renderSuggestions(members, query) {
        const box = document.getElementById('fb_suggest_box');
        if (!box) return;
        currentFilteredMembers = members;
        searchActiveIndex = -1;

        if (members.length === 0) {
            box.innerHTML = `
                <div class="fb-suggest-empty">
                    <i class='bx bx-user-x' style="font-size: 1.5rem; display: block; margin-bottom: 4px; opacity: 0.6;"></i>
                    No members found matching "<strong>${escapeHtml(query)}</strong>"
                </div>
            `;
            box.style.display = 'block';
            return;
        }

        let html = '';
        members.forEach((m, idx) => {
            const initial = (m.full_name || '?').trim().charAt(0).toUpperCase();
            const avatarHtml = m.photo_path 
                ? `<div class="fb-avatar"><img src="${escapeHtml(m.photo_path)}" alt="${escapeHtml(m.full_name)}"></div>`
                : `<div class="fb-avatar">${initial}</div>`;
            const memberIdFormatted = 'ID #' + String(m.member_id).padStart(4, '0');
            const contactInfo = m.phone ? m.phone : (m.email ? m.email : '');
            const memberType = m.member_type || 'Member';

            html += `
                <div class="fb-suggest-item" data-index="${idx}" onclick="selectMemberFromSuggest(${m.member_id})">
                    <div class="d-flex align-items-center gap-2" style="flex: 1; min-width: 0;">
                        ${avatarHtml}
                        <div style="min-width: 0; flex: 1;">
                            <div class="fb-suggest-name text-truncate">
                                ${highlightMatch(m.full_name, query)}
                            </div>
                            <div class="fb-suggest-sub text-truncate">
                                <span class="badge bg-light text-secondary border" style="font-size: 9px; padding: 1px 4px;">${memberIdFormatted}</span>
                                ${contactInfo ? `&bull; ${escapeHtml(contactInfo)}` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <span class="fb-member-type-pill">${escapeHtml(memberType)}</span>
                    </div>
                </div>
            `;
        });

        box.innerHTML = html;
        box.style.display = 'block';
    }

    function selectMemberFromSuggest(memberId) {
        const member = allMembers.find(m => Number(m.member_id) === Number(memberId));
        if (!member) return;

        const scanMemberId = document.getElementById('scan_member_id');
        const qrInput = document.getElementById('qr_token');
        const searchInput = document.getElementById('fb_member_search');

        if (scanMemberId) scanMemberId.value = member.member_id;
        if (qrInput) qrInput.value = member.qr_token || member.member_id;
        if (searchInput) searchInput.value = member.full_name;

        hideSuggestions();

        // Submit attendance form immediately
        document.getElementById('scan-form').submit();
    }

    function submitManualSearch() {
        const searchInput = document.getElementById('fb_member_search');
        if (!searchInput) return;
        const query = searchInput.value.trim();
        if (!query) {
            searchInput.focus();
            return;
        }

        // If an item is active with keyboard navigation
        if (searchActiveIndex >= 0 && currentFilteredMembers[searchActiveIndex]) {
            selectMemberFromSuggest(currentFilteredMembers[searchActiveIndex].member_id);
            return;
        }

        // If suggestions are currently shown, pick the top match
        if (currentFilteredMembers.length > 0) {
            selectMemberFromSuggest(currentFilteredMembers[0].member_id);
            return;
        }

        // Try direct find in allMembers
        const found = allMembers.find(m => 
            (m.full_name || '').toLowerCase() === query.toLowerCase() ||
            String(m.member_id) === query.replace(/[^0-9]/g, '')
        );
        if (found) {
            selectMemberFromSuggest(found.member_id);
            return;
        }

        // Fallback: send query to backend to resolve via controller
        const qrInput = document.getElementById('qr_token');
        if (qrInput) {
            qrInput.value = query;
            document.getElementById('scan-form').submit();
        }
    }

    function clearMemberSearch() {
        const searchInput = document.getElementById('fb_member_search');
        const clearBtn = document.getElementById('btn_clear_search');
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        if (clearBtn) clearBtn.style.display = 'none';
        hideSuggestions();
    }

    function hideSuggestions() {
        const box = document.getElementById('fb_suggest_box');
        if (box) box.style.display = 'none';
        searchActiveIndex = -1;
    }

    function updateActiveSuggestItem() {
        const box = document.getElementById('fb_suggest_box');
        if (!box) return;
        const items = box.querySelectorAll('.fb-suggest-item');
        items.forEach((item, idx) => {
            if (idx === searchActiveIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    async function initCamera() {
        const overlay = document.getElementById('scannerOverlay');
        const statusText = document.getElementById('scannerStatusText');
        const cameraSelect = document.getElementById('cameraSelect');
        const statusBadge = document.querySelector('.scanner-status-badge');

        if (overlay) overlay.style.display = 'flex';
        if (statusText) {
            statusText.innerHTML = `
                <i class='bx bx-loader-alt bx-spin' style="font-size: 2.2rem; color: #3b82f6; margin-bottom: 8px;"></i>
                <div>Detecting cameras...</div>
            `;
        }

        try {
            if (typeof Html5Qrcode === 'undefined') {
                throw new Error("Scanner library failed to load. Please check your internet or refresh.");
            }

            const devices = await Html5Qrcode.getCameras();
            if (!devices || devices.length === 0) {
                throw new Error("No camera devices detected. Make sure OBS Virtual Camera or your webcam is connected and enabled.");
            }

            if (cameraSelect) {
                cameraSelect.innerHTML = '';
                let obsCameraId = null;

                devices.forEach((device, index) => {
                    const opt = document.createElement('option');
                    opt.value = device.id;
                    const label = device.label || `Camera ${index + 1}`;
                    opt.textContent = label;

                    // Automatically identify and prioritize OBS Virtual Camera
                    if (label.toLowerCase().includes('obs') || label.toLowerCase().includes('virtual')) {
                        obsCameraId = device.id;
                        opt.textContent = '★ ' + label + ' (OBS Virtual Camera)';
                    }
                    cameraSelect.appendChild(opt);
                });

                // Prefer OBS Virtual Camera if present, otherwise first detected camera
                currentCameraId = obsCameraId || devices[0].id;
                cameraSelect.value = currentCameraId;

                cameraSelect.onchange = function() {
                    switchCamera(this.value);
                };
            } else {
                currentCameraId = devices[0].id;
            }

            await startScanning(currentCameraId);

        } catch (err) {
            console.error("Camera Init Error:", err);
            if (statusText) {
                statusText.innerHTML = `
                    <div style="color: #ef4444; margin-bottom: 8px;">
                        <i class='bx bx-error-circle' style="font-size: 2.5rem;"></i>
                    </div>
                    <div style="font-weight: 700; color: #f87171; font-size: 0.95rem; margin-bottom: 4px;">Camera Unavailable</div>
                    <div style="font-size: 0.8rem; line-height: 1.4; max-width: 290px; margin: 0 auto 12px; color: #cbd5e1;">
                        ${err.message || 'Please grant camera permission in your browser.'}
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" onclick="initCamera()" style="font-size: 0.8rem; padding: 4px 14px;">
                        <i class='bx bx-refresh'></i> Grant Permission / Retry
                    </button>
                `;
            }
            if (statusBadge) {
                statusBadge.style.background = 'rgba(239, 68, 68, 0.1)';
                statusBadge.style.color = '#ef4444';
                statusBadge.innerHTML = '<span style="width:8px;height:8px;background:#ef4444;border-radius:50%;display:inline-block;"></span> Offline';
            }
        }
    }

    async function startScanning(cameraId) {
        const overlay = document.getElementById('scannerOverlay');
        const qrInput = document.getElementById('qr_token');
        const statusBadge = document.querySelector('.scanner-status-badge');

        if (!html5QrCode) {
            html5QrCode = new Html5Qrcode("reader");
        }

        if (isScanning) {
            try {
                await html5QrCode.stop();
            } catch (e) {
                console.warn("Stop scanner error:", e);
            }
            isScanning = false;
        }

        const config = {
            fps: 20,
            experimentalFeatures: {
                useBarCodeDetectorIfSupported: true
            }
        };

        const qrCodeSuccessCallback = (decodedText, decodedResult) => {
            if (qrInput) {
                qrInput.value = decodedText;
                if (statusBadge) {
                    statusBadge.style.background = 'rgba(59, 130, 246, 0.2)';
                    statusBadge.style.color = '#2563eb';
                    statusBadge.innerHTML = '<i class="bx bx-check"></i> Scanned!';
                }
                document.getElementById('scan-form').submit();
            }
        };

        await html5QrCode.start(
            cameraId,
            config,
            qrCodeSuccessCallback,
            (errorMessage) => {
                // Ignore per-frame decode misses
            }
        );

        isScanning = true;
        if (overlay) overlay.style.display = 'none';

        if (statusBadge) {
            statusBadge.style.background = 'rgba(34, 197, 94, 0.1)';
            statusBadge.style.color = '#16a34a';
            statusBadge.innerHTML = '<span class="scanner-status-pulse"></span> Active';
        }
    }

    async function switchCamera(newCameraId) {
        currentCameraId = newCameraId;
        const overlay = document.getElementById('scannerOverlay');
        const statusText = document.getElementById('scannerStatusText');
        if (overlay) {
            overlay.style.display = 'flex';
            if (statusText) {
                statusText.innerHTML = `
                    <i class='bx bx-loader-alt bx-spin' style="font-size: 2.2rem; color: #3b82f6; margin-bottom: 8px;"></i>
                    <div>Switching to selected camera...</div>
                `;
            }
        }
        try {
            await startScanning(newCameraId);
        } catch (err) {
            console.error("Camera Switch Error:", err);
            alert("Failed to switch camera: " + (err.message || err));
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        const qrInput = document.getElementById('qr_token');
        const searchInput = document.getElementById('fb_member_search');
        const clearBtn = document.getElementById('btn_clear_search');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                if (clearBtn) clearBtn.style.display = query ? 'inline-flex' : 'none';

                if (query.length === 0) {
                    hideSuggestions();
                    return;
                }

                const qLower = query.toLowerCase();
                const matched = allMembers.filter(m => {
                    const name = (m.full_name || '').toLowerCase();
                    const email = (m.email || '').toLowerCase();
                    const phone = (m.phone || '').toLowerCase();
                    const idStr = String(m.member_id || '');
                    return name.includes(qLower) || email.includes(qLower) || phone.includes(qLower) || idStr.includes(qLower);
                }).slice(0, 10);

                renderSuggestions(matched, query);
            });

            searchInput.addEventListener('keydown', function(e) {
                const box = document.getElementById('fb_suggest_box');
                const isOpen = box && box.style.display !== 'none';

                if (e.key === 'ArrowDown') {
                    if (isOpen && currentFilteredMembers.length > 0) {
                        e.preventDefault();
                        searchActiveIndex = (searchActiveIndex + 1) % currentFilteredMembers.length;
                        updateActiveSuggestItem();
                    }
                } else if (e.key === 'ArrowUp') {
                    if (isOpen && currentFilteredMembers.length > 0) {
                        e.preventDefault();
                        searchActiveIndex = (searchActiveIndex - 1 + currentFilteredMembers.length) % currentFilteredMembers.length;
                        updateActiveSuggestItem();
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    submitManualSearch();
                } else if (e.key === 'Escape') {
                    hideSuggestions();
                }
            });

            // Focus on search input when clicking the search box container
            searchInput.addEventListener('focus', function() {
                if (this.value.trim().length > 0) {
                    this.dispatchEvent(new Event('input'));
                }
            });
        }

        // Close auto-suggest box when clicking outside
        document.addEventListener('click', function(e) {
            const container = document.getElementById('scannerSearchContainer');
            if (container && !container.contains(e.target)) {
                hideSuggestions();
            }
        });

        if (qrInput) {
            qrInput.focus();
        }

        document.body.addEventListener('click', (e) => {
            const container = document.getElementById('scannerSearchContainer');
            if (container && container.contains(e.target)) {
                return; // Don't redirect focus if user is searching manually
            }
            if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT' && document.activeElement.tagName !== 'BUTTON') {
                if (qrInput) qrInput.focus();
            }
        });

        // Submit on enter for physical scanner input
        if (qrInput) {
            qrInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('scan-form').submit();
                }
            });
        }

        // Initialize camera
        initCamera();
    });
</script>

<?php 
// Include Layout Footer
include __DIR__ . '/layout/footer.php';
?>
