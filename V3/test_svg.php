<?php
require_once 'vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;

try {
    $qrCode = new QrCode('test');
    $writer = new SvgWriter();
    $result = $writer->write($qrCode);
    echo "SVG Works: " . substr($result->getString(), 0, 50) . "...\n";
} catch (Exception $e) {
    echo "SVG Failed: " . $e->getMessage() . "\n";
}
