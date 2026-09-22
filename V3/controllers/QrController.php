<?php
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/Member.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
$autoloader = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

use Endroid\QrCode\QrCode;
use Endroid\QrCode\ErrorCorrectionLevel;

class QrController {
    private $memberModel;

    public function __construct($pdo) {
        $this->memberModel = new Member($pdo);
    }

    public function generate($memberId) {
        // Require valid login session
        requireLogin();

        // Safety check: Members can only view their own QR code
        if (isMember() && $_SESSION['member_id'] != $memberId) {
            die("Unauthorized Access: You can only view your own ID.");
        }

        $member = $this->memberModel->find($memberId);
        if (!$member || empty($member['qr_token'])) {
            header("HTTP/1.0 404 Not Found");
            echo "Member or QR Token not found.";
            exit;
        }

        try {
            // Using endroid/qr-code v3.x fluent API for maximum PHP 8.0 compatibility.
            // This version does not use the Builder pattern.
            $qrCode = new QrCode($member['qr_token']);
            $qrCode->setSize(250);
            $qrCode->setMargin(10);
            $qrCode->setWriterByName('svg');
            $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::MEDIUM());

            header('Content-Type: ' . $qrCode->getContentType());
            header('Cache-Control: no-store, no-cache, must-revalidate');
            echo $qrCode->writeString();

        } catch (\Throwable $e) {
            header("HTTP/1.0 500 Internal Server Error");
            echo "Error generating QR Code: " . $e->getMessage();
        }
        exit;
    }
}
