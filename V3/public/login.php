<?php
/**
 * Login view.
 */

// Define project base path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/middleware/CSRF.php';
require_once BASE_PATH . '/controllers/AuthController.php';

// Redirect if already logged in
redirectIfLogged();

$error = '';

/**
 * Handle form submission.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (validateCsrfToken($_POST['csrf_token'])) {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (login($email, $password)) {
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
}

// Generate new CSRF token
$csrf_token = generateCsrfToken();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body class="login-container">
    <div class="login-card">
        <div class="login-header">
            <img src="assets/images/logo.png" alt="Logo" style="height: 80px; width: 80px; border-radius: 50%; margin-bottom: 15px; border: 2px solid var(--gray-100);">
            <h2>GFUMC</h2>
            <p class="text-muted">God's Family United Methodist Church</p>
        </div>
        
        <div class="login-body">
            <?php 
            $success = $_SESSION['flash_success'] ?? '';
            unset($_SESSION['flash_success']);
            ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="admin@example.com" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <div class="d-flex align-items-start gap-2">
                        <input type="checkbox" id="agree_terms" name="agree_terms" required style="margin-top: 4px;">
                        <label for="agree_terms" class="small text-muted" style="cursor: pointer;">
                            I understand and agree to the <a href="javascript:void(0)" onclick="document.getElementById('policyModal').classList.add('active')">Church Privacy Policy & Terms of Service</a>.
                        </label>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    Sign In
                </button>
                
                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="text-muted small">
                        Forgot Password?
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Policy Modal -->
    <div class="modal-overlay" id="policyModal">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="mb-0">Privacy Policy & Terms</h3>
                <button class="btn btn-secondary btn-sm" onclick="document.getElementById('policyModal').classList.remove('active')">&times;</button>
            </div>
            <div class="modal-body" style="max-height: 400px; overflow-y: auto; font-size: 0.9rem; line-height: 1.6;">
                <p><strong>1. Data Collection:</strong> We collect and store member information (name, contact, attendance) solely for church administration and communication purposes.</p>
                <p><strong>2. Privacy:</strong> Your data is confidential and only accessible by authorized church staff/administrators. We do not sell or share your data with external parties.</p>
                <p><strong>3. Usage:</strong> By using this system, you agree to provide accurate information and use the system in a respectful manner consistent with church values.</p>
                <p><strong>4. Security:</strong> We implement security measures to protect your account. You are responsible for maintaining the confidentiality of your login credentials.</p>
                <p><strong>5. Acceptance:</strong> Checking the agreement box and signing in constitutes your acceptance of these terms.</p>
            </div>
            <div class="modal-footer" style="text-align: right; padding: 15px;">
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('policyModal').classList.remove('active')">I Understand</button>
            </div>
        </div>
    </div>
</body>
</html>
