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
    if (!validateCsrfToken($_POST['csrf_token'])) {
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

// Include Layout Header
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Attendance Scanner</h1>
            <p class="text-muted small">Event: <strong><?php echo htmlspecialchars($event_data['title']); ?></strong></p>
        </div>
        <a href="attendance.php?event_id=<?php echo $eventId; ?>" class="btn btn-outline-primary">
            View Attendance Log
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="d-flex gap-4">
    <div style="flex: 1; max-width: 500px;">
        <div class="card">
            <div class="card-header">Live Scanner</div>
            <div class="card-body p-0">
                <div id="reader"></div>
            </div>
            <div class="card-footer">
                <form id="scan-form" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($eventId); ?>">
                    <input type="hidden" name="date" value="<?php echo htmlspecialchars($event_data['date']); ?>">
                    
                    <div class="mb-3 d-flex gap-4">
                        <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                            <input type="radio" name="status" value="Present" checked> Check In
                        </label>
                        <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                            <input type="radio" name="status" value="Time Out"> Time Out
                        </label>
                    </div>
                    <div class="d-flex gap-2">
                        <input type="text" id="qr_token" name="qr_token" class="form-control" autofocus placeholder="Scan QR or type ID..." required>
                        <button class="btn btn-primary d-none" type="submit" id="submitBtn">Submit</button>
                    </div>
                </form>
                <p class="text-muted small mt-2">Physical scanners will auto-submit when the input is focused.</p>
            </div>
        </div>
    </div>
    
    <div style="flex: 1; max-width: 300px;">
        <div class="card">
            <div class="card-header">Instructions</div>
            <div class="card-body">
                <ul class="text-muted small">
                    <li class="mb-2">Ensure adequate lighting for scanning.</li>
                    <li class="mb-2">Maximize screen brightness on mobile devices.</li>
                    <li>Click the input box if using a hardware scanner.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- QR Scanner Script -->
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const qrInput = document.getElementById('qr_token');
        qrInput.focus();
        
        document.body.addEventListener('click', () => {
            if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'SELECT') {
                qrInput.focus();
            }
        });

        // Submit on enter
        qrInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('scan-form').submit();
            }
        });

        function onScanSuccess(decodedText, decodedResult) {
            qrInput.value = decodedText;
            document.getElementById('scan-form').submit();
        }

        let html5QrcodeScanner = new Html5QrcodeScanner(
            "reader",
            { 
                fps: 10, 
                qrbox: {width: 250, height: 250},
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
