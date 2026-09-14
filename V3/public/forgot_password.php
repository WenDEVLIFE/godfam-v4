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
$success = '';

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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="login-container">
    <div class="login-card fade-in">
        <div class="login-header">
            <h2 class="mt-0">FORGOT PASSWORD</h2>
            <p class="login-subtitle">We'll send an OTP to your email</p>
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
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Enter your full name" required autofocus>
                </div>

                <div class="mb-4">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your registered email" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-3 mt-2">
                    Send OTP <i class='bx bx-paper-plane'></i>
                </button>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="text-muted small" style="text-decoration: none;">
                        <i class='bx bx-left-arrow-alt'></i> Back to Login
                    </a>
                </div>
            </form>
        </div>
        
    
    </div>
</body>
</html>
