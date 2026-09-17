<?php
/**
 * Forgot Password View
 * Step 1: Request OTP.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/middleware/CSRF.php';
require_once BASE_PATH . '/controllers/AuthController.php';

redirectIfLogged();

$error = '';
$prefill_email = trim($_GET['email'] ?? '');
$prefill_name  = trim($_GET['name'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateCsrfToken($_POST['csrf_token'])) {
        $email = $_POST['email'] ?? '';
        $name = $_POST['name'] ?? '';
        $result = requestOTP($email, $name);
        
        if ($result === true) {
            $_SESSION['reset_email'] = $email;
            header("Location: verify_otp.php");
            exit;
        } else {
            $error = $result;
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
    <title>Forgot Password - God's Family United Methodist Church</title>
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/enhanced.css">
</head>
<body class="login-container">
    <div class="login-card fade-in">
        <div class="login-header">
            <img src="assets/images/logo.png" alt="Logo">
            <h2>FORGOT PASSWORD</h2>
            <p>God's Family United Methodist Church</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <p class="text-muted text-center mb-4" style="font-size: 0.9rem;">
                Enter your registered name and email address to receive a 6-digit verification code.
            </p>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="mb-4">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter your full name" value="<?php echo htmlspecialchars($prefill_name); ?>" required autofocus>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label mb-0">Email Address</label>
                        <a href="forgot_email.php" class="text-muted small" style="text-decoration: underline;">Forgot email?</a>
                    </div>
                    <input type="email" name="email" class="form-control" placeholder="Enter your registered email" value="<?php echo htmlspecialchars($prefill_email); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-3 mt-2">
                    Send OTP <i class='bx bx-paper-plane'></i>
                </button>
                
                <div class="text-center mt-3 d-flex justify-content-between align-items-center">
                    <a href="forgot_email.php" class="text-muted small" style="text-decoration: none;">
                        <i class='bx bx-search'></i> Find Email
                    </a>
                    <a href="login.php" class="text-muted small" style="text-decoration: none;">
                        <i class='bx bx-left-arrow-alt'></i> Back to Login
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
