<?php
/**
 * Dashboard Stats API Endpoint
 * Returns JSON data for Chart.js charts on the dashboard.
 * Admin/Staff access only.
 */
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../models/Attendance.php';
require_once __DIR__ . '/../../models/Member.php';

if (!isAdmin() && !isStaff()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$attendanceModel = new Attendance($pdo);
$memberModel     = new Member($pdo);

// --- Weekly attendance (last 7 days) ---
$weeklyRaw = $attendanceModel->getWeeklyStats();

// Build a full 7-day array so gaps (days with 0 attendance) are included
$weeklyData   = [];
$weeklyLabels = [];
for ($i = 6; $i >= 0; $i--) {
    $date  = date('Y-m-d', strtotime("-{$i} days"));
    $label = date('D M d', strtotime($date));
    $weeklyLabels[] = $label;
    $weeklyData[]   = 0; // default zero
}
foreach ($weeklyRaw as $row) {
    $diffDays = (int) round((strtotime(date('Y-m-d')) - strtotime($row['date'])) / 86400);
    $idx = 6 - $diffDays;
    if (isset($weeklyData[$idx])) {
        $weeklyData[$idx] = (int) $row['count'];
    }
}

// --- Monthly attendance (current year) ---
$currentYear  = (int) date('Y');
$monthlyRaw   = $attendanceModel->getMonthlyStats($currentYear);
$monthlyData  = array_fill(0, 12, 0);
$monthNames   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
foreach ($monthlyRaw as $row) {
    $monthlyData[(int)$row['month_num'] - 1] = (int) $row['count'];
}

// --- Members growth (last 12 months) ---
$growthRaw    = $memberModel->getMembersGrowthByMonth(12);
$growthLabels = array_column($growthRaw, 'month_label');
$growthData   = array_map('intval', array_column($growthRaw, 'count'));

echo json_encode([
    'weekly' => [
        'labels' => $weeklyLabels,
        'data'   => $weeklyData,
    ],
    'monthly' => [
        'labels' => $monthNames,
        'data'   => $monthlyData,
        'year'   => $currentYear,
    ],
    'growth' => [
        'labels' => $growthLabels,
        'data'   => $growthData,
    ],
], JSON_THROW_ON_ERROR);
