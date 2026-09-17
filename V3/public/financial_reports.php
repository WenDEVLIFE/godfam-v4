<?php
/**
 * Financial Reports Page
 * Collection records, daily & monthly summaries with pie chart.
 */

$page_title = 'Financial Reports';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../models/Collection.php';
require_once __DIR__ . '/../models/Member.php';

if (!isAdmin() && !isStaff()) {
    header("Location: dashboard.php");
    exit;
}

$collectionModel = new Collection($pdo);
$memberModel     = new Member($pdo);
$members         = $memberModel->all();

$error   = '';
$success = '';

// --- Handle Add / Delete ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF Token Validation Failed.");
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $amount = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
        $type   = in_array($_POST['type'] ?? '', ['Tithe','Offering','Donation']) ? $_POST['type'] : 'Offering';
        $date   = $_POST['collection_date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

        if ($amount === false || $amount <= 0) {
            $error = "Please enter a valid amount greater than zero.";
        } else {
            $result = $collectionModel->create([
                'member_id'       => !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null,
                'amount'          => $amount,
                'category'        => $type,
                'collection_date' => $date,
                'remarks'         => htmlspecialchars(trim($_POST['notes'] ?? '')),
                'recorded_by'     => $_SESSION['user_id'],
            ]);
            if ($result) $success = "Collection record added successfully.";
            else $error = "Failed to save record. Please try again.";
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            if ($collectionModel->delete($id)) $success = "Record deleted.";
            else $error = "Could not delete record.";
        }
    }
}

$csrf_token = generateCsrfToken();

// Filters
$report_type  = in_array($_GET['type'] ?? 'daily', ['daily','monthly']) ? ($_GET['type'] ?? 'daily') : 'daily';
$filter_date  = $_GET['date'] ?? date('Y-m-d');
$filter_month = (int)($_GET['month'] ?? date('n'));
$filter_year  = (int)($_GET['year']  ?? date('Y'));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date)) $filter_date = date('Y-m-d');
$filter_month = max(1, min(12, $filter_month));
$filter_year  = max(2020, min((int)date('Y') + 1, $filter_year));

$logs           = [];
$type_breakdown = [];

if ($report_type === 'daily') {
    $logs = $collectionModel->getByDate($filter_date);
    $type_breakdown = $collectionModel->getTypeBreakdown($filter_date, $filter_date);
} else {
    $m_start = sprintf('%04d-%02d-01', $filter_year, $filter_month);
    $m_end   = date('Y-m-t', strtotime($m_start));
    $logs    = $collectionModel->getByMonth($filter_year, $filter_month);
    $type_breakdown = $collectionModel->getTypeBreakdown($m_start, $m_end);
}

$total = array_sum(array_column($logs, 'amount'));

// Breakdown for chart
$breakdown_map = ['Tithe' => 0, 'Offering' => 0, 'Donation' => 0];
foreach ($type_breakdown as $row) {
    $breakdown_map[$row['category']] = (float)$row['total'];
}

$month_names = ['January','February','March','April','May','June',
                'July','August','September','October','November','December'];

// Export links
$export_base  = 'collection_export.php?type=' . $report_type;
if ($report_type === 'daily') {
    $export_base .= '&date=' . $filter_date;
} else {
    $export_base .= '&month=' . $filter_month . '&year=' . $filter_year;
}

include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h2 style="font-weight: 800; font-size: 1.75rem; color: var(--primary-color); margin: 0 0 4px 0;">Financial Reports</h2>
        <p style="color: var(--muted-text); font-size: 0.95rem; margin: 0;">Collection records &mdash; tithes, offerings &amp; special giving</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?php echo $export_base . '&format=pdf'; ?>" target="_blank" class="btn btn-sm btn-outline-danger" style="font-weight: 600; padding: 7px 14px; border-radius: 6px;">
            <i class='bx bxs-file-pdf'></i> Export PDF
        </a>
        <a href="<?php echo $export_base . '&format=excel'; ?>" class="btn btn-sm btn-outline-success" style="font-weight: 600; padding: 7px 14px; border-radius: 6px;">
            <i class='bx bxs-file'></i> Export Excel
        </a>
        <a href="<?php echo $export_base . '&format=csv'; ?>" class="btn btn-sm btn-outline-secondary" style="font-weight: 600; padding: 7px 14px; border-radius: 6px;">
            <i class='bx bx-download'></i> Export CSV
        </a>
        <button onclick="document.getElementById('addCollectionModal').classList.add('active')" class="btn btn-sm btn-primary" style="font-weight: 600; padding: 7px 16px; border-radius: 6px;">
            <i class='bx bx-plus'></i> Record Collection
        </button>
    </div>
</div>

<?php if ($error): ?><div class="alert alert-danger mb-4"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success mb-4"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

<!-- Summary Widgets -->
<div class="row g-3 mb-4" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
    <div class="stat-card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #059669 !important;">
        <div class="stat-icon" style="background:#d1fae5;color:#059669;font-weight:800;font-size:1.5rem;">₱</div>
        <div>
            <div class="stat-label">Today's Total</div>
            <div class="stat-number" style="color:#059669;">₱<?php echo number_format($collectionModel->getTodayTotal(),2); ?></div>
        </div>
    </div>
    <div class="stat-card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid #3182ce !important;">
        <div class="stat-icon" style="background:#ebf8ff;color:#3182ce;"><i class='bx bx-calendar'></i></div>
        <div>
            <div class="stat-label">This Month</div>
            <div class="stat-number" style="color:#3182ce;">₱<?php echo number_format($collectionModel->getMonthTotal(),2); ?></div>
        </div>
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

        <div id="daily-filter" style="<?php echo $report_type !== 'daily' ? 'display:none;' : ''; ?>">
            <div class="filter-group">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>" style="min-width: 170px;">
            </div>
        </div>

        <div id="monthly-filter" style="<?php echo $report_type !== 'monthly' ? 'display:none;' : ''; ?>">
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

<!-- Breakdown Chart + Table -->
<div style="display:grid;grid-template-columns:300px 1fr;gap:24px;margin-bottom:24px;" class="financial-grid">
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-transparent py-3 px-4" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
            <span style="font-weight: 700; color: var(--dark-text);">Breakdown</span>
        </div>
        <div class="card-body p-4 d-flex flex-column align-items-center gap-3">
            <div style="width:170px;height:170px;position:relative;">
                <canvas id="breakdownChart"></canvas>
            </div>
            <div style="width:100%;" class="mt-2">
                <div class="breakdown-row"><span><span class="dot" style="background:#38a169;"></span> Tithe</span> <strong>₱<?php echo number_format($breakdown_map['Tithe'],2); ?></strong></div>
                <div class="breakdown-row"><span><span class="dot" style="background:#3182ce;"></span> Offering</span> <strong>₱<?php echo number_format($breakdown_map['Offering'],2); ?></strong></div>
                <div class="breakdown-row"><span><span class="dot" style="background:#d69e2e;"></span> Donation</span> <strong>₱<?php echo number_format($breakdown_map['Donation'],2); ?></strong></div>
                <div class="breakdown-row" style="border-top:1px solid #e2e8f0;margin-top:10px;padding-top:10px;"><strong>TOTAL</strong> <strong style="color:#38a169; font-size: 1.05rem;">₱<?php echo number_format($total,2); ?></strong></div>
            </div>
        </div>
    </div>
    <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-transparent py-3 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
            <span style="font-weight: 700; color: var(--dark-text);">Collection Records</span>
            <span class="badge badge-info" style="font-size: 11px; padding: 6px 12px; border-radius: 20px;"><?php echo count($logs); ?> entries</span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5 text-muted">
                    <i class='bx bx-receipt' style="font-size: 2.5rem; opacity: 0.5; display: block; margin-bottom: 8px;"></i>
                    <p class="mb-0" style="font-weight: 500;">No collection records found for this period.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Member</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Notes</th>
                            <th>Recorded By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="small"><?php echo date('M d, Y', strtotime($log['collection_date'])); ?></td>
                            <td><?php echo $log['full_name'] ? htmlspecialchars($log['full_name']) : '<em class="text-muted">Anonymous</em>'; ?></td>
                            <td>
                                <?php
                                $type_badges = ['Tithe'=>'badge-success','Offering'=>'badge-info','Donation'=>'badge-warning'];
                                $tb = $type_badges[$log['category']] ?? 'badge-info';
                                ?>
                                <span class="badge <?php echo $tb; ?>"><?php echo ucfirst($log['category']); ?></span>
                            </td>
                            <td><strong>₱<?php echo number_format($log['amount'],2); ?></strong></td>
                            <td class="small"><?php echo htmlspecialchars($log['remarks'] ?? ''); ?></td>
                            <td class="small"><?php echo htmlspecialchars($log['recorded_by_name']); ?></td>
                            <td>
                                <form method="POST" style="margin:0;" onsubmit="return confirm('Delete this record?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo (int)$log['collection_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm"><i class='bx bx-trash'></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Collection Modal -->
<div class="modal-overlay" id="addCollectionModal">
    <div class="modal-content" style="max-width:500px;">
        <div class="modal-header">
            <h4 style="margin:0;font-size:16px;">Record New Collection</h4>
            <button onclick="document.getElementById('addCollectionModal').classList.remove('active')" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label class="form-label">Member (optional — leave blank for anonymous)</label>
                    <select name="member_id" class="form-control">
                        <option value="">-- Anonymous / Walk-in --</option>
                        <?php foreach ($members as $m): ?>
                        <option value="<?php echo (int)$m['member_id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Type *</label>
                    <select name="type" class="form-control" required>
                        <option value="Tithe">Tithe</option>
                        <option value="Offering" selected>Offering</option>
                        <option value="Donation">Special Giving / Donation</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount (₱) *</label>
                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Date *</label>
                    <input type="date" name="collection_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" placeholder="Optional note..." maxlength="255">
                </div>
                <div class="d-flex gap-2 justify-content-end" style="margin-top:16px;">
                    <button type="button" onclick="document.getElementById('addCollectionModal').classList.remove('active')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('breakdownChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Tithe', 'Offering', 'Special'],
        datasets: [{
            data: [
                <?php echo $breakdown_map['Tithe']; ?>,
                <?php echo $breakdown_map['Offering']; ?>,
                <?php echo $breakdown_map['Donation']; ?>
            ],
            backgroundColor: ['#38a169','#3182ce','#d69e2e'],
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
        },
        cutout: '60%',
    }
});

function toggleFilters(type) {
    document.getElementById('daily-filter').style.display   = type === 'daily'   ? 'flex' : 'none';
    document.getElementById('monthly-filter').style.display = type === 'monthly' ? 'flex' : 'none';
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
