<?php
/**
 * Attendance Export Handler
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Attendance.php';

// Ensure user is authorized
if (!isAdmin() && !isStaff()) {
    die("Unauthorized access.");
}

$format = $_GET['format'] ?? 'csv';
$filters = [
    'event_id'   => $_GET['event_id'] ?? null,
    'member_id'  => $_GET['member_id'] ?? null,
    'status'     => $_GET['status'] ?? null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date'   => $_GET['end_date'] ?? null,
];

$attendanceModel = new Attendance($pdo);
$logs = $attendanceModel->getLog($filters);

$filename = "attendance_report_" . date('Y-m-d_His');

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');
    
    $output = fopen('php://output', 'w');
    // Header row
    fputcsv($output, ['Event', 'Member Name', 'Date', 'Status', 'Time Logged']);
    
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['event_title'],
            $log['full_name'],
            $log['date'],
            $log['status'],
            date('h:i A', strtotime($log['created_at']))
        ]);
    }
    fclose($output);
    exit;
} elseif ($format === 'pdf') {
    // Professional PDF/Print view with requested header and footer
    $eventTitle = $logs[0]['event_title'] ?? 'Church Event';
    $eventDate = $logs[0]['date'] ?? date('Y-m-d');
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Attendance Report - <?php echo htmlspecialchars($eventTitle); ?></title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; color: #333; line-height: 1.5; }
            .print-header { text-align: center; margin-bottom: 30px; }
            .letterhead { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 15px; }
            .logo { height: 80px; width: 80px; border-radius: 50%; object-fit: cover; }
            .church-info { text-align: center; }
            .church-info div { text-transform: uppercase; }
            .report-title { font-size: 1.5rem; font-weight: bold; margin: 20px 0 5px; text-transform: uppercase; letter-spacing: 2px; }
            table { width: 100%; border-collapse: collapse; margin-top: 25px; }
            th, td { border: 1px solid #000; padding: 10px; text-align: left; font-size: 11pt; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .no-print { margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; }
            .btn { padding: 8px 20px; cursor: pointer; border-radius: 5px; border: 1px solid #cbd5e0; background: white; font-weight: 600; }
            .btn-primary { background: #3182ce; color: white; border-color: #2b6cb0; }
            
            .print-footer { margin-top: 60px; }
            .signature-section { display: flex; justify-content: space-around; margin-top: 40px; }
            .signature-box { text-align: left; min-width: 250px; }
            .sig-line { border-bottom: 1px solid #000; width: 220px; margin: 40px 0 5px; }
            .sig-name { font-weight: bold; margin: 0; }
            .sig-title { font-size: 0.9rem; color: #666; margin: 0; }
            
            @media print {
                .no-print { display: none !important; }
                body { padding: 0; }
            }
        </style>
    </head>
    <body>
        <?php if (!isset($_GET['hidemenu']) || $_GET['hidemenu'] !== 'true'): ?>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-primary">Download / Print PDF</button>
            <button onclick="window.close();" class="btn">Close Window</button>
            <p style="font-size: 0.8rem; margin-top: 10px; color: #666;">Tip: Choose "Save as PDF" in the destination to export as a file.</p>
        </div>
        <?php endif; ?>
        
        <div class="print-header">
            <div class="letterhead">
                <img src="assets/images/logo.png" alt="Logo" class="logo">
                <div class="church-info">
                    <div style="font-size: 9pt; color: #555;">The United Methodist Church</div>
                    <div style="font-size: 8pt; color: #555;">South Nueva Ecija Philippine Annual Conference</div>
                    <div style="font-size: 12pt; font-weight: 800; color: #000; margin-top: 2px;">God's Family United Methodist Church</div>
                </div>
            </div>
            <hr style="border: 0; border-top: 2px solid #000;">
            <div class="report-title">Attendance Report</div>
            <p><strong>Event:</strong> <?php echo htmlspecialchars($eventTitle); ?> | <strong>Date:</strong> <?php echo date('F d, Y', strtotime($eventDate)); ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th>Member Name</th>
                    <th>Status</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($log['full_name']); ?></strong></td>
                    <td>Present</td>
                    <td><?php echo date('h:i A', strtotime($log['created_at'])); ?></td>
                    <td><?php echo !empty($log['time_out']) ? date('h:i A', strtotime($log['time_out'])) : '---'; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" style="text-align:center;">No attendance records found for this event.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="print-footer">
            <div class="signature-section">
                <div class="signature-box">
                    <p>Prepared by:</p>
                    <div class="sig-line"></div>
                    <p class="sig-name"><?php echo htmlspecialchars($_SESSION['name']); ?></p>
                    <p class="sig-title">System Admin</p>
                </div>
                <div class="signature-box">
                    <p>Noted by:</p>
                    <div class="sig-line"></div>
                    <p class="sig-name">Reverend / Pastor</p>
                    <p class="sig-title">Church Official</p>
                </div>
            </div>
            <div style="margin-top: 60px; text-align: center; font-size: 0.8rem; color: #777; border-top: 1px solid #eee; padding-top: 10px;">
                God's Family United Methodist Church - Official Record | Generated on <?php echo date('F d, Y h:i A'); ?>
            </div>
        </div>
        
        <script>
            window.onload = function() {
                // Auto open print dialog
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}
