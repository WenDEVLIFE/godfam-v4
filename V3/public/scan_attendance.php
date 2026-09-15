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
if (!isAdmin() && !isStaff()) {
    header("Location: dashboard.php");
    exit;
}

$eventId = $_GET['event_id'] ?? null;
if (!$eventId) {
    header("Location: events.php");
    exit;
}

$eventController = new EventController($pdo);
$attendanceController = new AttendanceController($pdo);

// Find event data
$event_list = $eventController->index();
$event_data = null;
foreach($event_list as $e) {
    if($e['event_id'] == $eventId) {
        $event_data = $e;
        break;
    }
}

if (!$event_data) {
    die("Event not found.");
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
}
</style>

<div class="fade-in py-2">
    <!-- Top Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 style="font-weight: 800; font-size: 1.85rem; color: var(--primary-color); margin-bottom: 6px;">Attendance Scanner</h2>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge badge-info" style="font-size: 0.85rem; padding: 6px 12px; font-weight: 700;">
                    Event: <?php echo htmlspecialchars($event_data['title']); ?>
                </span>
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
    <div class="row g-4">
        <!-- Left Column: Scanner Window & Controls -->
        <div class="col-lg-5 col-xl-4">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-header bg-transparent py-3 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
                    <h5 style="margin: 0; font-weight: 800; color: var(--dark-text); font-size: 1.05rem;">LIVE SCANNER</h5>
                    <span class="scanner-status-badge">
                        <span class="scanner-status-pulse"></span> Ready
                    </span>
                </div>
                <div class="card-body p-3">
                    <div id="reader" style="width: 100%; min-height: 220px; background: #f8fafc;"></div>
                </div>
                <div class="card-footer bg-transparent p-4" style="border-top: 1px solid rgba(0,0,0,0.05);">
                    <form id="scan-form" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($eventId); ?>">
                        <input type="hidden" name="date" value="<?php echo htmlspecialchars($event_data['date']); ?>">
                        <input type="hidden" name="mode" id="scan_mode" value="time_in">
                        <input type="hidden" name="status" id="scan_status" value="Present">

                        <!-- Mode Segmented Toggle Buttons -->
                        <div class="mode-toggle-container">
                            <button type="button" class="mode-toggle-btn active" id="btnModeCheckIn" onclick="setScanMode('time_in')">
                                <i class='bx bx-log-in-circle'></i> Check In
                            </button>
                            <button type="button" class="mode-toggle-btn" id="btnModeTimeOut" onclick="setScanMode('time_out')">
                                <i class='bx bx-log-out-circle'></i> Time Out
                            </button>
                        </div>

                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0" style="border-top-left-radius: 8px; border-bottom-left-radius: 8px; border-color: #cbd5e1; color: var(--muted-text); padding-left: 14px; padding-right: 10px;">
                                <i class='bx bx-qr-scan' style="font-size: 1.25rem;"></i>
                            </span>
                            <input type="text" id="qr_token" name="qr_token" class="form-control border-start-0" autofocus placeholder="Scan QR or type Member ID..." required style="height: 46px; font-size: 0.95rem; border-color: #cbd5e1; box-shadow: none;">
                            <button class="btn btn-primary px-3" type="submit" id="submitBtn" style="height: 46px; border-top-right-radius: 8px; border-bottom-right-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                                <i class='bx bx-send' style="font-size: 1.1rem;"></i>
                            </button>
                        </div>
                    </form>
                    <p class="text-muted small mt-2 mb-0" style="font-size: 0.8rem;">
                        <i class='bx bx-info-circle'></i> Physical scanners automatically submit when focused.
                    </p>
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
                            <i class='bx bx-target-lock text-primary' style="font-size: 1.1rem;"></i> Keep the text box focused when using USB barcode guns.
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Column: Live Recent Scans Feed -->
        <div class="col-lg-7 col-xl-8">
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
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
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

    document.addEventListener("DOMContentLoaded", function() {
        const qrInput = document.getElementById('qr_token');
        if (qrInput) {
            qrInput.focus();
        }

        document.body.addEventListener('click', (e) => {
            if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT' && document.activeElement.tagName !== 'BUTTON') {
                if (qrInput) qrInput.focus();
            }
        });

        // Submit on enter
        if (qrInput) {
            qrInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('scan-form').submit();
                }
            });
        }

        function onScanSuccess(decodedText, decodedResult) {
            if (qrInput) {
                qrInput.value = decodedText;
                document.getElementById('scan-form').submit();
            }
        }

        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            { 
                fps: 10, 
                qrbox: {width: 240, height: 240},
                showTorchButtonIfSupported: true,
                aspectRatio: 1.0
            },
            false
        );
        
        html5QrcodeScanner.render(onScanSuccess);
    });
</script>

<?php 
// Include Layout Footer
include __DIR__ . '/layout/footer.php';
?>
