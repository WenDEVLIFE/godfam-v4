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
    <div class="login-card">
        <div class="login-header">
            <img src="assets/images/logo.png" alt="Logo">
            <h2></h2>
            <p>God's Family United Methodist Church</p>
        </div>
        
        <div class="login-body">
            <?php 
            $success = $_SESSION['flash_success'] ?? '';
            unset($_SESSION['flash_success']);
            ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class='bx bx-check-circle'></i>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class='bx bx-error-circle'></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="search-input-wrapper">
                        <i class='bx bx-envelope'></i>
                        <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email address" required autofocus>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="search-input-wrapper">
                        <i class='bx bx-lock-alt'></i>
                        <input type="password" id="password" name="password" class="form-control has-toggle" placeholder="••••••••" required>
                        <button type="button" id="togglePassword" class="password-toggle-btn" aria-label="Toggle password visibility" title="Show/Hide Password">
                            <i class='bx bx-hide' id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 15px;">
                    <div class="d-flex align-items-start gap-2">
                        <input type="checkbox" id="agree_terms" name="agree_terms" required style="margin-top: 4px;">
                        <label for="agree_terms" class="small text-muted" style="cursor: pointer;">
                            I understand and agree to the <a href="javascript:void(0)" onclick="openPrivacyModal('agree_terms')">Church Privacy Policy &amp; Data Protection Consent</a>.
                        </label>
                    </div>
                </div>
                
                <button type="submit" id="loginSubmitBtn" class="btn btn-primary w-100" style="padding: 12px; font-size: 15px;">
                    <i class='bx bx-log-in-circle'></i> Sign In
                </button>
                
                <div class="text-center mt-3 d-flex justify-content-between align-items-center">
                    <a href="forgot_email.php" class="text-muted small">
                        Forgot Email?
                    </a>
                    <a href="forgot_password.php" class="text-muted small">
                        Forgot Password?
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Mandatory Privacy Statement & Data Protection Consent Modal -->
    <div class="modal-overlay" id="privacyConsentModal" role="dialog" aria-modal="true" aria-labelledby="privacyConsentTitle" style="z-index: 9999 !important; backdrop-filter: blur(8px);">
        <div class="modal-content" style="max-width: 540px !important; margin: auto !important; border-radius: 16px !important; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35) !important;">
            <div class="modal-header" style="padding: 20px 24px; border-bottom: 1px solid #e2e8f0;">
                <h4 id="privacyConsentTitle" style="font-weight: 800; color: #1e293b; margin: 0; font-size: 1.25rem;">
                    <i class='bx bxs-shield-alt-2' style="color:#1565C0; margin-right: 8px;"></i> Data Privacy &amp; Protection Statement
                </h4>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="closePrivacyModal()" style="border:none; font-size: 1.25rem;">&times;</button>
            </div>
            <div class="modal-body" style="max-height: 420px; overflow-y: auto; font-size: 0.92rem; line-height: 1.65; color: #334155; padding: 24px;">
                <div style="background: rgba(21, 101, 192, 0.06); border-left: 4px solid #1565C0; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-weight: 600; color: #1e293b;">
                    God's Family United Methodist Church is committed to respecting and protecting your personal data privacy in compliance with Republic Act No. 10173 (Data Privacy Act of 2012).
                </div>
                
                <h5 style="font-weight: 700; color: #0f172a; margin-top: 14px; margin-bottom: 6px;">1. Collection &amp; Use of Personal Information</h5>
                <p style="margin-bottom: 12px;">We process your personal information (name, contact details, email address, attendance records, and church affiliation) exclusively for church administration, pastoral care, digital ID generation, notification of upcoming events, and financial collection record-keeping.</p>
                
                <h5 style="font-weight: 700; color: #0f172a; margin-top: 14px; margin-bottom: 6px;">2. Confidentiality &amp; Security</h5>
                <p style="margin-bottom: 12px;">Your information is kept strictly confidential and stored securely in encrypted databases. Only authorized pastors, church staff, and system administrators have access to your records. We will never sell, rent, or share your data with third parties without your consent.</p>

                <h5 style="font-weight: 700; color: #0f172a; margin-top: 14px; margin-bottom: 6px;">3. Data Subject Rights</h5>
                <p style="margin-bottom: 12px;">As a data subject, you maintain the right to view, update, correct, or request deletion of your personal records in accordance with church record retention policies.</p>

                <h5 style="font-weight: 700; color: #0f172a; margin-top: 14px; margin-bottom: 6px;">4. Declaration of Consent</h5>
                <p style="margin-bottom: 0;">By checking the agreement box or clicking "I Accept &amp; Give Consent" below, you grant explicit consent for God's Family UM Church to collect, process, and retain your data as specified above.</p>
            </div>
            <div class="modal-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; background: #f8fafc;">
                <button type="button" class="btn btn-outline-secondary" onclick="closePrivacyModal()" style="font-weight: 600;">Decline</button>
                <button type="button" class="btn btn-primary" id="privacyAcceptBtn" onclick="openPrivacyModal('agree_terms')" style="font-weight: 600; background: #1565C0; border-color: #1565C0;">
                    <i class='bx bx-check-shield'></i> I Accept &amp; Give Consent
                </button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js?v=1.0"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const togglePasswordBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');

        if (togglePasswordBtn && passwordInput && toggleIcon) {
            togglePasswordBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const isPassword = passwordInput.getAttribute('type') === 'password';
                passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                toggleIcon.className = isPassword ? 'bx bx-show' : 'bx bx-hide';
                togglePasswordBtn.setAttribute('title', isPassword ? 'Hide Password' : 'Show Password');
            });
        }

        const loginForm = document.querySelector('form[action="login.php"]');
        if (loginForm) {
            loginForm.addEventListener('submit', function(e) {
                const agreeChk = document.getElementById('agree_terms');
                if (agreeChk && !agreeChk.checked) {
                    e.preventDefault();
                    openPrivacyModal('agree_terms');
                }
            });
        }
    });
    </script>
</body>
</html>
