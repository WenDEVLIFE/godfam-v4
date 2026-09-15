<?php
/**
 * Collection/Financial Export Handler
 * Supports: csv, pdf (print dialog), excel (.xlsx via PhpSpreadsheet)
 */
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../models/Collection.php';

if (!isAdmin() && !isStaff()) {
    die("Unauthorized access.");
}

$format       = $_GET['format'] ?? 'csv';
$report_type  = $_GET['type']   ?? 'daily';
$filter_date  = $_GET['date']   ?? date('Y-m-d');
$filter_month = (int)($_GET['month'] ?? date('n'));
$filter_year  = (int)($_GET['year']  ?? date('Y'));

$collectionModel = new Collection($pdo);

$logs = [];
$reportTitle = '';

if ($report_type === 'daily') {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_date)) $filter_date = date('Y-m-d');
    $logs = $collectionModel->getByDate($filter_date);
    $reportTitle = "Daily Financial Report - " . date('F j, Y', strtotime($filter_date));
    $filename = "financial_report_daily_" . $filter_date;
} else {
    $filter_month = max(1, min(12, $filter_month));
    $filter_year  = max(2020, min((int)date('Y') + 1, $filter_year));
    $logs = $collectionModel->getByMonth($filter_year, $filter_month);
    
    $month_names = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $monthName = $month_names[$filter_month-1];
    
    $reportTitle = "Monthly Financial Report - " . $monthName . " " . $filter_year;
    $filename = "financial_report_monthly_" . $filter_year . "_" . str_pad($filter_month, 2, '0', STR_PAD_LEFT);
}

$totalAmount = array_sum(array_column($logs, 'amount'));

// ============================================================
// CSV Export
// ============================================================
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename . '.csv');

    $output = fopen('php://output', 'w');
    
    // Title row
    fputcsv($output, [$reportTitle]);
    fputcsv($output, []); // Empty row
    
    fputcsv($output, ['Date', 'Member Name', 'Type', 'Amount', 'Notes', 'Recorded By']);
    foreach ($logs as $log) {
        $memberName = $log['full_name'] ? $log['full_name'] : 'Anonymous';
        fputcsv($output, [
            $log['collection_date'],
            $memberName,
            ucfirst($log['type']),
            $log['amount'],
            $log['notes'],
            $log['recorded_by_name']
        ]);
    }
    
    fputcsv($output, []); // Empty row
    fputcsv($output, ['TOTAL:', '', '', $totalAmount]);
    
    fclose($output);
    exit;
}

// ============================================================
// Excel Export via PhpSpreadsheet
// ============================================================
if ($format === 'excel') {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    if (!class_exists(Spreadsheet::class)) {
        die("Excel export library (PhpSpreadsheet) is not available on this server. Please run 'composer install' or use CSV export.");
    }

    $spreadsheet = new Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Financial Report');

    // ---- Church letterhead (row 1-2) ----
    $sheet->mergeCells('A1:F1');
    $sheet->setCellValue('A1', "God's Family United Methodist Church – Financial Report");
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A2:F2');
    $sheet->setCellValue('A2', $reportTitle . " | Generated: " . date('F j, Y h:i A'));
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A2')->getFont()->setSize(10)->setItalic(true);

    // ---- Column headers (row 4) ----
    $headers = ['Date', 'Member Name', 'Type', 'Amount (PHP)', 'Notes', 'Recorded By'];
    $cols    = ['A','B','C','D','E','F'];
    
    foreach ($headers as $i => $header) {
        $cell = $cols[$i] . '4';
        $sheet->setCellValue($cell, $header);
    }

    // Style header row
    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2D3748']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
    ];
    $sheet->getStyle('A4:F4')->applyFromArray($headerStyle);

    // ---- Data rows ----
    $row = 5;
    foreach ($logs as $idx => $log) {
        $memberName = $log['full_name'] ? $log['full_name'] : 'Anonymous';
        
        $sheet->setCellValue('A' . $row, date('M d, Y', strtotime($log['collection_date'])));
        $sheet->setCellValue('B' . $row, $memberName);
        $sheet->setCellValue('C' . $row, ucfirst($log['type']));
        $sheet->setCellValue('D' . $row, $log['amount']);
        $sheet->setCellValue('E' . $row, $log['notes']);
        $sheet->setCellValue('F' . $row, $log['recorded_by_name']);
        
        // Format amount column
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');

        // Alternate row shading
        if ($idx % 2 === 0) {
            $sheet->getStyle("A{$row}:F{$row}")->getFill()
                  ->setFillType(Fill::FILL_SOLID)
                  ->getStartColor()->setRGB('F7FAFC');
        }
        $row++;
    }

    // ---- Total row ----
    $sheet->setCellValue('A' . $row, 'Total Collection');
    $sheet->mergeCells("A{$row}:C{$row}");
    $sheet->setCellValue('D' . $row, $totalAmount);
    $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0.00');
    
    $totalStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF8FF']],
    ];
    $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($totalStyle);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

    // Auto-size columns
    foreach ($cols as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Output
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ============================================================
// PDF Export (print-dialog HTML)
// ============================================================
if ($format === 'pdf') {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    $preparedByName = htmlspecialchars($_SESSION['name'] ?? 'System Admin');
    $logoPath = __DIR__ . '/assets/images/logo.png';
    $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

    if (class_exists(\Dompdf\Dompdf::class) && (!isset($_GET['html']) || $_GET['html'] !== '1')) {
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Helvetica');
        $dompdf = new \Dompdf\Dompdf($options);

        $tableRowsHtml = '';
        $i = 1;
        foreach ($logs as $log) {
            $cat = !empty($log['category']) ? ucfirst($log['category']) : (!empty($log['type']) ? ucfirst($log['type']) : 'Offering');
            $method = !empty($log['payment_method']) ? htmlspecialchars($log['payment_method']) : 'Cash';
            $memberName = $log['full_name'] ? htmlspecialchars($log['full_name']) : '<em>Anonymous</em>';
            $remarks = !empty($log['remarks']) ? htmlspecialchars($log['remarks']) : (!empty($log['notes']) ? htmlspecialchars($log['notes']) : '');

            $tableRowsHtml .= '<tr>';
            $tableRowsHtml .= '<td>' . $i++ . '</td>';
            $tableRowsHtml .= '<td>' . date('M d, Y', strtotime($log['collection_date'])) . '</td>';
            $tableRowsHtml .= '<td>' . $memberName . '</td>';
            $tableRowsHtml .= '<td>' . $cat . ' (' . $method . ')</td>';
            $tableRowsHtml .= '<td style="text-align: right;">₱' . number_format($log['amount'], 2) . '</td>';
            $tableRowsHtml .= '<td>' . $remarks . '</td>';
            $tableRowsHtml .= '</tr>';
        }
        if (empty($logs)) {
            $tableRowsHtml = '<tr><td colspan="6" style="text-align:center;">No financial records found for this period.</td></tr>';
        } else {
            $tableRowsHtml .= '<tr style="background-color:#ebf8ff;font-weight:bold;">';
            $tableRowsHtml .= '<td colspan="4" style="text-align:right;">TOTAL:</td>';
            $tableRowsHtml .= '<td style="text-align:right;">₱' . number_format($totalAmount, 2) . '</td>';
            $tableRowsHtml .= '<td></td>';
            $tableRowsHtml .= '</tr>';
        }

        $pdfHtml = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
                .header { text-align: center; margin-bottom: 20px; }
                .logo { width: 65px; height: 65px; border-radius: 50%; }
                .report-title { font-size: 16px; font-weight: bold; margin-top: 10px; text-transform: uppercase; letter-spacing: 1px; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th, td { border: 1px solid #333; padding: 7px 9px; text-align: left; font-size: 10px; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .sig-table { width: 100%; border: none; margin-top: 35px; }
                .sig-table td { border: none; text-align: center; vertical-align: top; }
                .sig-line { border-bottom: 1px solid #000; width: 180px; margin: 30px auto 5px; }
                .footer-bar { margin-top: 40px; text-align: center; font-size: 8.5px; color: #777; border-top: 1px solid #ccc; padding-top: 8px; }
            </style>
        </head>
        <body>
            <div class="header">
                ' . ($logoSrc ? '<img src="' . $logoSrc . '" class="logo"><br>' : '') . '
                <div style="font-size:9px;color:#555;">The United Methodist Church</div>
                <div style="font-size:8px;color:#555;">South Nueva Ecija Philippine Annual Conference</div>
                <div style="font-size:12px;font-weight:bold;margin-top:2px;">God\'s Family United Methodist Church</div>
                <hr style="border:0;border-top:1.5px solid #000;margin-top:8px;">
                <div class="report-title">' . htmlspecialchars($reportTitle) . '</div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width:30px;">#</th>
                        <th>Date</th>
                        <th>Member Name</th>
                        <th>Category</th>
                        <th style="text-align:right;">Amount (₱)</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $tableRowsHtml . '
                </tbody>
            </table>

            <table class="sig-table">
                <tr>
                    <td>
                        Prepared by:
                        <div class="sig-line"></div>
                        <strong>' . $preparedByName . '</strong><br>
                        <span style="font-size:9px;color:#666;">System Admin / Staff</span>
                    </td>
                    <td>
                        Noted by:
                        <div class="sig-line"></div>
                        <strong>Reverend / Pastor</strong><br>
                        <span style="font-size:9px;color:#666;">Church Official</span>
                    </td>
                </tr>
            </table>

            <div class="footer-bar">
                God\'s Family United Methodist Church - Official Record | Generated on ' . date('F d, Y h:i A') . '
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($pdfHtml);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream($filename . '.pdf', ['Attachment' => false]);
        exit;
    }

    // HTML Print Fallback
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title><?php echo htmlspecialchars($reportTitle); ?></title>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 30px; color: #333; line-height: 1.5; }
            .print-header { text-align: center; margin-bottom: 30px; }
            .letterhead { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 15px; }
            .logo { height: 80px; width: 80px; border-radius: 50%; object-fit: cover; }
            .church-info { text-align: center; }
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
            .total-row { background-color: #ebf8ff; font-weight: bold; }
            @page { margin: 0; }
            @media print { .no-print { display: none !important; } body { margin: 0; padding: 15mm 20mm; } }
        </style>
    </head>
    <body>
        <?php if (!isset($_GET['hidemenu']) || $_GET['hidemenu'] !== 'true'): ?>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-primary">Download / Print PDF</button>
            <button onclick="window.close();" class="btn">Close Window</button>
            <p style="font-size:0.8rem;margin-top:10px;color:#666;">Tip: Uncheck "Headers and footers" in your browser print settings to hide default page URLs.</p>
        </div>
        <?php endif; ?>

        <div class="print-header">
            <div class="letterhead">
                <img src="assets/images/logo.png" alt="Logo" class="logo">
                <div class="church-info">
                    <div style="font-size:9pt;color:#555;">The United Methodist Church</div>
                    <div style="font-size:8pt;color:#555;">South Nueva Ecija Philippine Annual Conference</div>
                    <div style="font-size:12pt;font-weight:800;color:#000;margin-top:2px;">God's Family United Methodist Church</div>
                </div>
            </div>
            <hr style="border:0;border-top:2px solid #000;">
            <div class="report-title"><?php echo htmlspecialchars($reportTitle); ?></div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Date</th>
                    <th>Member Name</th>
                    <th>Category</th>
                    <th style="text-align: right;">Amount (₱)</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo date('M d, Y', strtotime($log['collection_date'])); ?></td>
                    <td><?php echo $log['full_name'] ? htmlspecialchars($log['full_name']) : '<em>Anonymous</em>'; ?></td>
                    <td><?php echo !empty($log['category']) ? ucfirst($log['category']) : (!empty($log['type']) ? ucfirst($log['type']) : 'Offering'); ?></td>
                    <td style="text-align: right;"><?php echo number_format($log['amount'], 2); ?></td>
                    <td><?php echo !empty($log['remarks']) ? htmlspecialchars($log['remarks']) : (!empty($log['notes']) ? htmlspecialchars($log['notes']) : ''); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" style="text-align:center;">No financial records found for this period.</td></tr>
                <?php else: ?>
                    <tr class="total-row">
                        <td colspan="4" style="text-align:right;"><strong>TOTAL:</strong></td>
                        <td style="text-align: right;"><strong>₱<?php echo number_format($totalAmount, 2); ?></strong></td>
                        <td></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="print-footer">
            <div class="signature-section">
                <div class="signature-box">
                    <p>Prepared by:</p>
                    <div class="sig-line"></div>
                    <p class="sig-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'System Admin'); ?></p>
                    <p class="sig-title">System Admin / Staff</p>
                </div>
                <div class="signature-box">
                    <p>Noted by:</p>
                    <div class="sig-line"></div>
                    <p class="sig-name">Reverend / Pastor</p>
                    <p class="sig-title">Church Official</p>
                </div>
            </div>
            <div style="margin-top:60px;text-align:center;font-size:0.8rem;color:#777;border-top:1px solid #eee;padding-top:10px;">
                God's Family United Methodist Church - Official Record | Generated on <?php echo date('F d, Y h:i A'); ?>
            </div>
        </div>
        <script>
            window.onload = function() { setTimeout(function() { window.print(); }, 500); };
        </script>
    </body>
    </html>
    <?php
    exit;
}
