<?php
/**
 * Audit Logs & Security Reports View
 * Displays administrative activity, authentication logs, IP addresses, and user actions.
 * Admin / Staff access only.
 */

$page_title = 'Audit Logs & Login Reports';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/AuditLog.php';

if (!isAdmin() && !isStaff()) {
    header("Location: dashboard.php");
    exit;
}

$auditModel = new AuditLog($pdo);

// Filters
$filters = [
    'action'     => $_GET['action']     ?? null,
    'search'     => trim($_GET['search'] ?? ''),
    'start_date' => $_GET['start_date'] ?? null,
    'end_date'   => $_GET['end_date']   ?? null,
];

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportLogs = $auditModel->getLogs($filters, 5000, 0);
    $filename   = "audit_logs_" . date('Y-m-d_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Date & Time', 'User', 'Email', 'Role', 'Action', 'Details', 'IP Address', 'User Agent']);

    foreach ($exportLogs as $log) {
        fputcsv($out, [
            $log['log_id'],
            $log['created_at'],
            $log['user_name'] ?? 'System / Guest',
            $log['user_email'] ?? '—',
            $log['role_name'] ?? '—',
            $log['action'],
            $log['details'],
            $log['ip_address'],
            $log['user_agent']
        ]);
    }
    fclose($out);
    exit;
}

// Pagination
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 25;
$offset   = ($page - 1) * $perPage;

$logs            = $auditModel->getLogs($filters, $perPage, $offset);
$totalLogs       = $auditModel->countLogs($filters);
$totalPages      = max(1, ceil($totalLogs / $perPage));
$distinctActions = $auditModel->getDistinctActions();

include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="flat-dashboard mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <h1 class="page-title" style="margin:0;">Audit Logs &amp; Security Reports</h1>
            <p class="text-muted small">System activity monitoring, authentication logs &amp; user actions</p>
        </div>
        <div>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-outline-primary font-weight-600">
                <i class='bx bx-download'></i> Export CSV Report
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small font-weight-600 text-muted">Search Query</label>
                    <input type="text" name="search" class="form-control" placeholder="Search user, IP, or details..." value="<?php echo htmlspecialchars($filters['search']); ?>">
                </div>
                <div class="col-md-2 col-sm-6">
                    <label class="form-label small font-weight-600 text-muted">Action Filter</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        <?php foreach ($distinctActions as $act): ?>
                            <option value="<?php echo htmlspecialchars($act); ?>" <?php echo strtoupper($filters['action'] ?? '') === strtoupper($act) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($act); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small font-weight-600 text-muted">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label class="form-label small font-weight-600 text-muted">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>">
                </div>
                <div class="col-md-1 col-sm-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100"><i class='bx bx-search'></i></button>
                    <?php if (!empty($filters['search']) || !empty($filters['action']) || !empty($filters['start_date']) || !empty($filters['end_date'])): ?>
                        <a href="audit_logs.php" class="btn btn-outline-secondary" title="Clear Filters"><i class='bx bx-x'></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class='bx bx-shield-quarter' style="margin-right:6px; color:var(--cms-blue);"></i> Recorded Audit Logs</span>
            <span class="badge badge-info"><?php echo number_format($totalLogs); ?> Total Entries</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="p-5 text-center text-muted">
                    <i class='bx bx-search-alt' style="font-size:2.5rem; opacity:0.5;"></i>
                    <p class="mt-2 mb-0">No audit log records match the selected criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 160px;">Date &amp; Time</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th style="width: 130px;">IP Address</th>
                                <th>Device / User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="small text-muted" style="white-space: nowrap;">
                                    <?php echo date('M d, Y', strtotime($log['created_at'])); ?><br>
                                    <strong style="color:var(--dark-text);"><?php echo date('h:i:s A', strtotime($log['created_at'])); ?></strong>
                                </td>
                                <td>
                                    <?php if (!empty($log['user_name'])): ?>
                                        <div class="font-weight-600 text-dark"><?php echo htmlspecialchars($log['user_name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($log['user_email']); ?> &bull; <span class="badge badge-secondary"><?php echo htmlspecialchars($log['role_name'] ?? 'User'); ?></span></div>
                                    <?php else: ?>
                                        <em class="text-muted">System / Guest</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $act = strtoupper($log['action']);
                                    $badge = 'badge-secondary';
                                    if (str_contains($act, 'LOGIN')) $badge = 'badge-success';
                                    if (str_contains($act, 'FAILED') || str_contains($act, 'DELETE')) $badge = 'badge-danger';
                                    if (str_contains($act, 'CREATE') || str_contains($act, 'RECORD')) $badge = 'badge-info';
                                    if (str_contains($act, 'LOGOUT')) $badge = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $badge; ?>" style="font-size:11px; padding:4px 8px;"><?php echo htmlspecialchars($log['action']); ?></span>
                                </td>
                                <td class="small" style="max-width: 280px; word-break: break-word;">
                                    <?php echo htmlspecialchars($log['details']); ?>
                                </td>
                                <td class="small font-weight-600 text-primary">
                                    <code><?php echo htmlspecialchars($log['ip_address'] ?? '127.0.0.1'); ?></code>
                                </td>
                                <td class="small text-muted" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                    <?php echo htmlspecialchars($log['user_agent']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                    <span class="small text-muted">Showing <?php echo $offset + 1; ?> to <?php echo min($totalLogs, $offset + $perPage); ?> of <?php echo $totalLogs; ?> records</span>
                    <ul class="pagination mb-0" style="gap:4px;">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
