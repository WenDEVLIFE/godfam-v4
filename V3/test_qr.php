<?php
require_once 'vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

try {
    if (method_exists(Builder::class, 'create')) {
        echo "Builder::create exists\n";
    }
} catch (Exception $e) {}

try {
    $qr = new QrCode('test');
    echo "new QrCode('test') works\n";
} catch (Exception $e) {}

try {
    $qr = QrCode::create('test');
    echo "QrCode::create works\n";
} catch (Exception $e) {}
