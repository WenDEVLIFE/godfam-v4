<?php
/**
 * OTP Verification View
 * Step 2: Enter 6rd-digit code.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/middleware/CSRF.php';
require_once BASE_PATH . '/controllers/AuthController.php';

redirectIfLogged();

$email = $_SESSION['reset_email'] ?? '';

if (empty($email)) {
    header("Location: forgot_password.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateCsrfToken($_POST['csrf_token'])) {
        $otp = trim($_POST['otp'] ?? '');
        
        $verifyResult = verifyOTP($email, $otp);
        
        if ($verifyResult === true) {
            $_SESSION['otp_verified'] = true;
            $_SESSION['reset_otp'] = $otp;
            header("Location: reset_password.php");
            exit;
        } else {
            // verifyOTP returns descriptive error strings with attempt count
            $error = is_string($verifyResult) ? $verifyResult : "Invalid or expired OTP code.";
        }
    }
}

$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - God's Family United Methodist Church</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .otp-input {
            letter-spacing: 12px;
            text-align: center;
            font-size: 1.8rem;
            font-weight: 700;
        }
    </style>
</head>
<body class="login-container">
    <div class="login-card fade-in">
        <div class="login-header">
            <h2 class="mt-0">VERIFY CODE</h2>
            <p class="login-subtitle">Enter the 6-digit OTP sent to your email</p>
        </div>
        
        <div class="login-body">
            <div class="alert alert-info py-2" style="font-size: 0.85rem; text-align: center;">
                <i class='bx bx-envelope'></i> Code sent to: <strong><?php echo htmlspecialchars($email); ?></strong>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="mb-4">
                    <label class="form-label">6-Digit OTP</label>
                    <input type="text" name="otp" class="form-control otp-input" maxlength="6" pattern="\d{6}" placeholder="000000" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-3 mt-2">
                    Verify Code <i class='bx bx-shield-check'></i>
                </button>
                
                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="text-muted small" style="text-decoration: none;">
                        <i class='bx bx-refresh'></i> Resend OTP
                    </a>
                </div>
            </form>
        </div>
        
        <div class="login-footer">
            <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                &copy; <?php echo date('Y'); ?> God's Family United Methodist Church. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
