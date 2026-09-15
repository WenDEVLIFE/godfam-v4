<?php
/**
 * Reset Password View
 * Step 3: Enter new password.
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
$otp = $_SESSION['reset_otp'] ?? '';
$verified = $_SESSION['otp_verified'] ?? false;

if (!$verified || empty($email) || empty($otp)) {
    header("Location: forgot_password.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateCsrfToken($_POST['csrf_token'])) {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        if (strlen($password) < 6) {
            $error = "Password must be at least 6 characters.";
        } elseif ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else {
            $result = updatePassword($email, $otp, $password);
            if ($result === true) {
                // Clear reset session
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_otp']);
                unset($_SESSION['otp_verified']);
                
                $_SESSION['flash_success'] = "Password changed! Please login with your new password.";
                header("Location: login.php");
                exit;
            } else {
                $error = $result;
            }
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
    <title>Reset Password - God's Family United Methodist Church</title>
    <!-- Google Fonts (Inter) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/theme.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="login-container">
    <div class="login-card fade-in">
        <div class="login-header">
            <img src="assets/images/logo.png" alt="Logo">
            <h2>RESET PASSWORD</h2>
            <p>God's Family United Methodist Church</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="mb-4">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required autofocus>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-3 mt-2">
                    Update Password <i class='bx bx-check-double'></i>
                </button>
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
