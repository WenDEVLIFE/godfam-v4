<?php
/**
 * Attendance View - Record and manage service attendance.
 * Accessible by Admin and Staff.
 */

$page_title = 'Event Attendance';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/AttendanceController.php';
require_once __DIR__ . '/../controllers/MemberController.php';
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

$controller = new AttendanceController($pdo);
$memberController = new MemberController($pdo);
$eventController = new EventController($pdo);

$event = $eventController->index(); // This is a bit inefficient, find() would be better
$event_data = null;
foreach($event as $e) {
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

// Handle Attendance Recording
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed.");
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'record') {
        $result = $controller->record($_POST);
        if ($result === true) $success = "Attendance recorded successfully.";
        else $error = $result;
    } elseif ($action === 'record_timeout') {
        $result = $controller->recordTimeOutManual($_POST['attendance_id']);
        if ($result === true) $success = "Time Out recorded successfully.";
        else $error = $result;
    }
}

$attendeeList = $controller->viewAttendance($eventId);
$allMembers = $memberController->index();
$csrf_token = generateCsrfToken();

// Include Layout Header
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="fade-in attendance-container">
    <div class="print-header d-none-screen d-block-print mb-4" style="display: none; font-family: 'Arial', sans-serif;">
        <div style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 15px;">
            <img src="assets/images/logo.png" alt="Logo" style="height: 70px; width: 70px; border-radius: 50%; object-fit: cover; border: 1px solid #eee;">
            <div style="text-align: center; line-height: 1.2;">
                <div style="font-size: 9pt; color: #555; text-transform: uppercase; letter-spacing: 1px;">The United Methodist Church</div>
                <div style="font-size: 8pt; color: #555; text-transform: uppercase;">South Nueva Ecija Philippine Annual Conference</div>
                <div style="font-size: 8pt; color: #555; text-transform: uppercase;">Southeast Nueva Ecija District</div>
                <div style="font-size: 12pt; font-weight: 800; color: #000; margin-top: 2px; text-transform: uppercase;">God's Family United Methodist Church</div>
            </div>
        </div>
        <hr style="border: 0; border-top: 2px solid #000; margin: 5px 0;">
        <div style="text-align: center;">
            <h3 style="text-transform: uppercase; letter-spacing: 2px; margin: 10px 0 0 0; font-weight: bold;">Attendance Report</h3>
            <p class="text-muted" style="margin-top: 5px; font-size: 0.95rem;">Event: <?php echo htmlspecialchars($event_data['title']); ?> &nbsp;|&nbsp; Date: <?php echo date('F d, Y', strtotime($event_data['date'])); ?></p>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <div>
            <h2 class="mb-1"><?php echo htmlspecialchars($event_data['title']); ?></h2>
            <p class="text-muted"><i class='bx bx-calendar'></i> <?php echo date('F d, Y', strtotime($event_data['date'])); ?> | Attendance Tracking</p>
        </div>
        <div class="header-actions d-flex gap-2">
            <div class="dropdown" style="position:relative;">
                <button type="button" class="btn btn-outline-secondary" onclick="toggleExportMenu()">
                    <i class='bx bx-export'></i> Export Log
                </button>
                <div id="exportMenu" class="dropdown-menu" style="position:absolute; right:0; z-index:1000; min-width:160px; display:none;">
                    <a href="attendance_export.php?event_id=<?php echo $eventId; ?>&format=csv" class="dropdown-item">Excel (.csv)</a>
                    <a href="javascript:void(0)" onclick="openPdfModal()" class="dropdown-item">PDF Report Preview</a>
                </div>
            </div>
            <button onclick="window.print()" class="btn btn-outline-secondary">
                <i class='bx bx-printer'></i> Print Report
            </button>
            <a href="scan_attendance.php?event_id=<?php echo $eventId; ?>" class="btn btn-primary">
                <i class='bx bx-scan'></i> Scanner
            </a>
            <a href="events.php" class="btn btn-outline-primary">
                <i class='bx bx-arrow-back'></i> Back
            </a>
        </div>
    </div>

    <!-- Attendance Summary -->
    <div class="stats-grid mb-4">
        <div class="stat-card">
            <div class="stat-icon"><i class='bx bxs-user-check'></i></div>
            <div>
                <div class="stat-label">Present</div>
                <div class="stat-number"><?php echo count($attendeeList); ?></div>
            </div>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Log Table -->
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center no-print">
                    <span>Recorded Attendance</span>
                    <input type="text" id="logFilter" class="form-control form-control-sm" style="width:200px;" placeholder="Quick filter list...">
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="attendanceTable">
                            <thead>
                                <tr>
                                    <th>Member Name</th>
                                    <th>Status</th>
                                    <th>Time In</th>
                                    <th class="text-right">Time Out</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($attendeeList)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">No attendance recorded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($attendeeList as $attendee): ?>
                                    <tr class="log-row">
                                        <td class="member-name-cell">
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar" style="width: 28px; height: 28px; font-size: 0.7rem; margin-right: 12px; background: #edf2f7; color: #4a5568; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                                    <?php echo strtoupper(substr($attendee['full_name'], 0, 1)); ?>
                                                </div>
                                                <strong><?php echo htmlspecialchars($attendee['full_name']); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">Present</span>
                                        </td>
                                        <td>
                                            <?php echo date('h:i A', strtotime($attendee['created_at'] ?? 'now')); ?>
                                        </td>
                                        <td class="text-right">
                                            <?php if (empty($attendee['time_out'])): ?>
                                                <form method="POST" style="display:inline;" class="no-print">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                    <input type="hidden" name="action" value="record_timeout">
                                                    <input type="hidden" name="attendance_id" value="<?php echo $attendee['attendance_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-warning" style="font-size: 0.75rem; padding: 4px 8px;"><i class='bx bx-log-out-circle'></i> Time Out</button>
                                                </form>
                                                <span class="d-none d-block-print">---</span>
                                            <?php else: ?>
                                                <span><?php echo date('h:i A', strtotime($attendee['time_out'])); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Entry Sidebar -->
        <div class="col-lg-4 mb-4 no-print">
            <div class="card">
                <div class="card-header">Manual Entry</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="record">
                        <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                        <input type="hidden" name="date" value="<?php echo $event_data['date']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Search Member</label>
                            <input type="text" id="memberSearchInput" class="form-control" list="membersList" placeholder="Type member name..." autocomplete="off">
                            <datalist id="membersList">
                                <?php foreach ($allMembers as $member): ?>
                                    <option value="<?php echo htmlspecialchars($member['full_name']); ?>" data-id="<?php echo $member['member_id']; ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <input type="hidden" name="member_id" id="hiddenMemberId">
                        </div>

                        <script>
                        document.getElementById('memberSearchInput').addEventListener('input', function() {
                            const val = this.value;
                            const options = document.getElementById('membersList').options;
                            let foundId = "";
                            
                            for (let i = 0; i < options.length; i++) {
                                if (options[i].value === val) {
                                    foundId = options[i].getAttribute('data-id');
                                    break;
                                }
                            }
                            document.getElementById('hiddenMemberId').value = foundId;
                        });
                        </script>
                        
                        <div class="mb-4">
                            <input type="hidden" name="status" value="Present">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-2">
                            Record Attendance <i class='bx bx-check'></i>
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4 bg-light">
                <div class="card-body text-center py-4">
                    <i class='bx bx-mobile-alt text-primary' style="font-size: 2.5rem; margin-bottom: 10px;"></i>
                    <h5>Mobile Scanning</h5>
                    <p class="small text-muted mb-3">Use your phone to scan member ID QR codes for faster entry tracking.</p>
                    <a href="scan_attendance.php?event_id=<?php echo $eventId; ?>" class="btn btn-outline-primary btn-sm px-4">Open Scanner</a>
                </div>
            </div>
        </div>
    </div> <!-- .row -->

    <!-- Print Footer with Signatures -->
    <div class="print-footer d-none-screen d-block-print mt-5" style="display: none;">
        <div style="display: flex; justify-content: space-around; margin-top: 50px; padding: 0 40px;">
            <div style="text-align: left;">
                <p style="margin-bottom: 40px;">Prepared by:</p>
                <div style="border-bottom: 1px solid #000; width: 220px;"></div>
                <p style="font-weight: bold; margin-top: 5px;"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                <p class="small text-muted">System Admin</p>
            </div>
            <div style="text-align: left;">
                <p style="margin-bottom: 40px;">Noted by:</p>
                <div style="border-bottom: 1px solid #000; width: 220px;"></div>
                <p style="font-weight: bold; margin-top: 5px;">Reverend / Pastor</p>
                <p class="small text-muted">Church Official</p>
            </div>
        </div>
        <div style="margin-top: 60px; text-align: center; font-size: 0.8rem; color: #777; border-top: 1px solid #eee; padding-top: 10px;">
            God's Family United Methodist Church - Official Attendance Record | Generated on <?php echo date('F d, Y h:i A'); ?>
        </div>
    </div>
</div> <!-- .fade-in -->

<style>
/* Attendance Page Grid Overrides */
@media (min-width: 992px) {
    .attendance-container .row {
        display: flex;
        flex-wrap: wrap;
        gap: 0;
    }
    .attendance-container .col-lg-8 {
        flex: 0 0 66.666667%;
        max-width: 66.666667%;
        margin: 0;
    }
    .attendance-container .col-lg-4 {
        flex: 0 0 33.333333%;
        max-width: 33.333333%;
        margin: 0;
        padding-left: 20px;
    }
}

@media print {
    .d-none-screen { display: block !important; }
    .no-print { display: none !important; }
    body { background: white !important; font-size: 11pt; color: black; }
    .card { border: none !important; box-shadow: none !important; }
    .card-header { display: none !important; }
    .table-responsive { overflow: visible !important; }
    table { width: 100% !important; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #000 !important; padding: 6px !important; color: black !important; }
    th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
    .badge { 
        background: transparent !important; 
        color: black !important; 
        border: 1px solid #000 !important; 
        box-shadow: none !important;
        text-transform: uppercase;
        font-size: 8pt !important;
    }
    .text-muted, .small { color: black !important; display: inline-block !important; visibility: visible !important; opacity: 1 !important; }
    .col-lg-4, .manual-entry-card, .header-actions { display: none !important; }
    .col-lg-8 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }
    .stats-grid { display: none !important; }
}
</style>

<!-- PDF Report Preview Modal -->
<div class="modal-overlay" id="pdfReportModal" style="z-index: 1100;">
    <div class="modal-content" style="max-width: 95%; width: 1100px; height: 90vh; display: flex; flex-direction: column;">
        <div class="modal-header d-flex justify-content-between align-items-center" style="padding: 10px 20px;">
            <h4 class="mb-0">PDF Report Preview</h4>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-primary" onclick="printIframe()">
                    <i class='bx bx-printer'></i> Print / Save PDF
                </button>
                <button class="btn btn-sm btn-secondary" onclick="closePdfModal()">
                    <i class='bx bx-x'></i> Close
                </button>
            </div>
        </div>
        <div class="modal-body p-0" style="flex: 1; overflow: hidden;">
            <iframe id="pdfFrame" src="" style="width: 100%; height: 100%; border: none;"></iframe>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // List Filtering (Table Filter)
    const filterInput = document.getElementById('logFilter');
    if (filterInput) {
        filterInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.log-row').forEach(row => {
                const name = row.querySelector('.member-name-cell').textContent.toLowerCase();
                row.style.display = name.includes(query) ? '' : 'none';
            });
        });
    }

    // Dropdown toggle closure
    window.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            const menu = document.getElementById('exportMenu');
            if (menu) menu.style.display = 'none';
        }
    });
});

function toggleExportMenu() {
    const menu = document.getElementById('exportMenu');
    if (menu) {
        menu.style.display = (menu.style.display === 'none') ? 'block' : 'none';
    }
}

function openPdfModal() {
    const frame = document.getElementById('pdfFrame');
    const url = "attendance_export.php?event_id=<?php echo $eventId; ?>&format=pdf&autoprint=false&hidemenu=true";
    frame.src = url;
    document.getElementById('pdfReportModal').classList.add('active');
    document.getElementById('exportMenu').style.display = 'none';
}

function closePdfModal() {
    document.getElementById('pdfReportModal').classList.remove('active');
    document.getElementById('pdfFrame').src = "";
}

function printIframe() {
    const frame = document.getElementById('pdfFrame');
    if (frame.contentWindow) {
        frame.contentWindow.print();
    }
}
</script>

<?php 
// Include Layout Footer
include __DIR__ . '/layout/footer.php';
?>
