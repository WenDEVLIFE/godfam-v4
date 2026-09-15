<?php
/**
 * Attendance Reports Page
 * Daily and Monthly attendance reports with PDF/CSV export.
 */

$page_title = 'Attendance Reports';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../models/Event.php';

if (!isAdmin() && !isStaff()) {
    header("Location: dashboard.php");
    exit;
}

$attendanceModel = new Attendance($pdo);
$eventModel      = new Event($pdo);

// Filter inputs (sanitised)
$report_type = in_array($_GET['type'] ?? 'daily', ['daily','monthly']) ? ($_GET['type'] ?? 'daily') : 'daily';
$filter_date  = $_GET['date']  ?? date('Y-m-d');
$filter_month = (int) ($_GET['month'] ?? date('n'));
$filter_year  = (int) ($_GET['year']  ?? date('Y'));

// Validate date
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date)) {
    $filter_date = date('Y-m-d');
}
$filter_month = max(1, min(12, $filter_month));
$filter_year  = max(2020, min((int)date('Y') + 1, $filter_year));

$logs = [];
$summary = [];

if ($report_type === 'daily') {
    $logs = $attendanceModel->getLog(['start_date' => $filter_date, 'end_date' => $filter_date]);
} else {
    // Monthly: get all attendance for the selected month
    $start = sprintf('%04d-%02d-01', $filter_year, $filter_month);
    $end   = date('Y-m-t', strtotime($start));
    $logs  = $attendanceModel->getLog(['start_date' => $start, 'end_date' => $end]);

    // Group by event for summary
    foreach ($logs as $log) {
        $key = $log['event_id'];
        if (!isset($summary[$key])) {
            $summary[$key] = ['title' => $log['event_title'], 'date' => $log['date'], 'count' => 0];
        }
        $summary[$key]['count']++;
    }
}

// Month names for selector
$month_names = ['January','February','March','April','May','June',
                'July','August','September','October','November','December'];

// Export links
$export_base  = 'attendance_export.php?';
if ($report_type === 'daily') {
    $export_params = 'start_date=' . $filter_date . '&end_date=' . $filter_date;
} else {
    $m_start = sprintf('%04d-%02d-01', $filter_year, $filter_month);
    $m_end   = date('Y-m-t', strtotime($m_start));
    $export_params = 'start_date=' . $m_start . '&end_date=' . $m_end;
}

include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h2 style="font-weight: 800; font-size: 1.75rem; color: var(--primary-color); margin: 0 0 4px 0;">Attendance Reports</h2>
        <p style="color: var(--muted-text); font-size: 0.95rem; margin: 0;">Daily and monthly attendance summaries and exports</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?php echo $export_base . $export_params . '&format=pdf'; ?>" target="_blank" class="btn btn-sm btn-outline-danger" style="font-weight: 600; padding: 7px 14px; border-radius: 6px;">
            <i class='bx bxs-file-pdf'></i> Export PDF
        </a>
        <a href="<?php echo $export_base . $export_params . '&format=excel'; ?>" class="btn btn-sm btn-outline-success" style="font-weight: 600; padding: 7px 14px; border-radius: 6px;">
            <i class='bx bxs-file'></i> Export Excel
        </a>
        <a href="<?php echo $export_base . $export_params . '&format=csv'; ?>" class="btn btn-sm btn-outline-secondary" style="font-weight: 600; padding: 7px 14px; border-radius: 6px;">
            <i class='bx bx-download'></i> Export CSV
        </a>
    </div>
</div>

<!-- Filters -->
<div class="report-filter-card">
    <form method="GET" class="report-filter-bar">
        <div class="filter-group">
            <label class="form-label">Report Type</label>
            <select name="type" class="form-select" onchange="toggleFilters(this.value)" style="min-width: 140px;">
                <option value="daily"   <?php echo $report_type === 'daily'   ? 'selected' : ''; ?>>Daily</option>
                <option value="monthly" <?php echo $report_type === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
            </select>
        </div>

        <div id="daily-filter" <?php echo $report_type !== 'daily' ? 'style="display:none;"' : ''; ?>>
            <div class="filter-group">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>" style="min-width: 170px;">
            </div>
        </div>

        <div id="monthly-filter" class="d-flex gap-3" <?php echo $report_type !== 'monthly' ? 'style="display:none;"' : ''; ?>>
            <div class="filter-group">
                <label class="form-label">Month</label>
                <select name="month" class="form-select" style="min-width: 140px;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?php echo $m; ?>" <?php echo $m === $filter_month ? 'selected' : ''; ?>><?php echo $month_names[$m-1]; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="form-label">Year</label>
                <select name="year" class="form-select" style="min-width: 100px;">
                    <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                    <option value="<?php echo $y; ?>" <?php echo $y === $filter_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <div class="filter-group">
            <button type="submit" class="btn btn-primary btn-generate">
                <i class='bx bx-search'></i> Generate
            </button>
        </div>
    </form>
</div>

<!-- Results -->
<?php if ($report_type === 'daily'): ?>
<div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-transparent py-3 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
        <span style="font-weight: 700; color: var(--dark-text);">Daily Report &mdash; <?php echo date('F j, Y', strtotime($filter_date)); ?></span>
        <span class="badge badge-info" style="font-size: 11px; padding: 6px 12px; border-radius: 20px;"><?php echo count($logs); ?> records</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <div class="text-center py-5 text-muted">
                <i class='bx bx-calendar-x' style="font-size: 2.5rem; opacity: 0.5; display: block; margin-bottom: 8px;"></i>
                <p class="mb-0" style="font-weight: 500;">No attendance records found for this date.</p>
            </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Member Name</th>
                        <th>Event</th>
                        <th>Status</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($logs as $log): ?>
                    <tr>
                        <td class="small"><?php echo $i++; ?></td>
                        <td><strong><?php echo htmlspecialchars($log['full_name']); ?></strong></td>
                        <td class="small"><?php echo htmlspecialchars($log['event_title']); ?></td>
                        <td><span class="badge badge-success"><?php echo htmlspecialchars($log['status']); ?></span></td>
                        <td class="small"><?php echo date('h:i A', strtotime($log['created_at'])); ?></td>
                        <td class="small"><?php echo !empty($log['time_out']) ? date('h:i A', strtotime($log['time_out'])) : '&mdash;'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- Monthly Summary -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
    <div class="stat-card">
        <div class="stat-icon"><i class='bx bxs-check-square'></i></div>
        <div>
            <div class="stat-label">Total Attendances</div>
            <div class="stat-number"><?php echo count($logs); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f0fff4;color:#38a169;"><i class='bx bxs-calendar-check'></i></div>
        <div>
            <div class="stat-label">Events Covered</div>
            <div class="stat-number"><?php echo count($summary); ?></div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        Monthly Summary &mdash; <?php echo $month_names[$filter_month-1] . ' ' . $filter_year; ?>
    </div>
    <div class="card-body p-0">
        <?php if (empty($summary)): ?>
            <p class="p-4 text-center text-muted">No records for this month.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>Event</th><th>Date</th><th>Attendees</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($summary as $row): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                        <td class="small"><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                        <td><span class="badge badge-info"><?php echo $row['count']; ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header">Full Attendance Log &mdash; <?php echo $month_names[$filter_month-1] . ' ' . $filter_year; ?></div>
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <p class="p-4 text-center text-muted">No records.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr><th>#</th><th>Member</th><th>Event</th><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($logs as $log): ?>
                    <tr>
                        <td class="small"><?php echo $i++; ?></td>
                        <td><strong><?php echo htmlspecialchars($log['full_name']); ?></strong></td>
                        <td class="small"><?php echo htmlspecialchars($log['event_title']); ?></td>
                        <td class="small"><?php echo date('M d, Y', strtotime($log['date'])); ?></td>
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

<script>
function toggleFilters(type) {
    document.getElementById('daily-filter').style.display   = type === 'daily'   ? '' : 'none';
    document.getElementById('monthly-filter').style.display = type === 'monthly' ? '' : 'none';
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
