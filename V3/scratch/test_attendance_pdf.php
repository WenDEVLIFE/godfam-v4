<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$logoPath = __DIR__ . '/../public/assets/images/logo.png';
$logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';

$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 70px; height: 70px; border-radius: 50%; }
        .title { font-size: 18px; font-weight: bold; margin-top: 10px; text-transform: uppercase; letter-spacing: 1px; }
        .subtitle { font-size: 11px; color: #555; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; font-size: 11px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .footer { margin-top: 40px; }
        .sig-table { width: 100%; border: none; margin-top: 30px; }
        .sig-table td { border: none; text-align: center; }
        .sig-line { border-bottom: 1px solid #000; width: 180px; margin: 30px auto 5px; }
    </style>
</head>
<body>
    <div class="header">
        ' . ($logoSrc ? '<img src="' . $logoSrc . '" class="logo"><br>' : '') . '
        <div style="font-size:10px;color:#555;">The United Methodist Church</div>
        <div style="font-size:9px;color:#555;">South Nueva Ecija Philippine Annual Conference</div>
        <div style="font-size:13px;font-weight:bold;margin-top:2px;">God\'s Family United Methodist Church</div>
        <hr style="border:0;border-top:1.5px solid #000;margin-top:10px;">
        <div class="title">Attendance Report</div>
        <div class="subtitle"><strong>Scope:</strong> All Events &nbsp;|&nbsp; <strong>Date:</strong> July 29, 2026</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:30px;">#</th>
                <th>Member Name</th>
                <th>Event</th>
                <th>Status</th>
                <th>Time In</th>
                <th>Time Out</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td><strong>John Doe</strong></td>
                <td>Sunday Worship Service</td>
                <td>Present</td>
                <td>09:00 AM</td>
                <td>11:30 AM</td>
            </tr>
        </tbody>
    </table>

    <table class="sig-table">
        <tr>
            <td>
                Prepared by:
                <div class="sig-line"></div>
                <strong>System Admin</strong><br>
                <span style="font-size:10px;color:#666;">System Admin</span>
            </td>
            <td>
                Noted by:
                <div class="sig-line"></div>
                <strong>Reverend / Pastor</strong><br>
                <span style="font-size:10px;color:#666;">Church Official</span>
            </td>
        </tr>
    </table>
    
    <div style="margin-top:40px;text-align:center;font-size:9px;color:#777;border-top:1px solid #ccc;padding-top:8px;">
        God\'s Family United Methodist Church - Official Record | Generated on ' . date('F d, Y h:i A') . '
    </div>
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$output = $dompdf->output();
file_put_contents(__DIR__ . '/test_attendance.pdf', $output);
echo "Dompdf Attendance Report generated: " . strlen($output) . " bytes\n";
