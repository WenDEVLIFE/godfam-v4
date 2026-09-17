<?php
/**
 * Local Algebraic CAPTCHA Generator endpoint.
 * Renders an image or returns math equation.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/utils/CaptchaService.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$phrase = CaptchaService::generateLocalCaptcha();

// Check if image rendering is requested
if (isset($_GET['img'])) {
    header('Content-Type: image/png');
    header('Cache-Control: no-cache, must-revalidate');

    $im = imagecreatetruecolor(140, 45);
    $bg = imagecolorallocate($im, 241, 245, 249); // slate light background
    $textColor = imagecolorallocate($im, 30, 41, 59); // slate dark text
    $lineColor = imagecolorallocate($im, 148, 163, 184); // noise line color

    imagefilledrectangle($im, 0, 0, 140, 45, $bg);

    // Add noise lines
    for ($i = 0; $i < 3; $i++) {
        imageline($im, rand(0, 140), rand(0, 45), rand(0, 140), rand(0, 45), $lineColor);
    }

    // Write math phrase text
    $text = $phrase . " = ?";
    imagestring($im, 5, 25, 14, $text, $textColor);

    imagepng($im);
    imagedestroy($im);
    exit;
}

// Default JSON endpoint for refreshing math question
header('Content-Type: application/json');
echo json_encode([
    'question' => $phrase . " = ?"
]);
exit;
