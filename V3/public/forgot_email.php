<?php
/**
 * Forgot Email Recovery View
 * Step 0: Look up email address by registered Full Name or Phone Number.
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
$results = [];
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (validateCsrfToken($_POST['csrf_token'])) {
        $searchTerm = trim($_POST['search_term'] ?? '');
        $searched = true;

        if (!empty($searchTerm)) {
            $results = findEmailByNameOrPhone($searchTerm);
            if (empty($results)) {
                $error = "No matching account found for '{$searchTerm}'. Please check your spelling or contact your church administrator.";
            }
        } else {
            $error = "Please enter your full name or phone number.";
        }
    }
}

/**
 * Mask an email address for privacy: user@domain.com -> u***r@domain.com
 */
function maskEmail($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return $email;
    $parts = explode('@', $email);
    $name  = $parts[0];
    $len   = strlen($name);

    if ($len <= 2) {
        $maskedName = substr($name, 0, 1) . '*';
    } else {
        $maskedName = substr($name, 0, 1) . str_repeat('*', max(2, $len - 2)) . substr($name, -1);
    }
    return $maskedName . '@' . $parts[1];
}

$csrf_token = generateCsrfToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Email Address - God's Family United Methodist Church</title>
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
    <div class="login-card fade-in" style="max-width: 480px;">
        <div class="login-header">
            <img src="assets/images/logo.png" alt="Logo">
            <h2>FIND YOUR EMAIL</h2>
            <p>God's Family United Methodist Church</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <p class="text-muted text-center mb-4" style="font-size: 0.9rem;">
                Enter your registered <strong>Full Name</strong> or <strong>Phone Number</strong> to look up your account email.
            </p>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="mb-4">
                    <label class="form-label">Full Name or Phone Number</label>
                    <div class="search-input-wrapper">
                        <i class='bx bx-search'></i>
                        <input type="text" name="search_term" class="form-control" placeholder="e.g. John Doe or +639171234567" value="<?php echo htmlspecialchars($_POST['search_term'] ?? ''); ?>" required autofocus>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-3 mt-1">
                    <i class='bx bx-search-alt'></i> Look Up Account Email
                </button>
            </form>

            <?php if ($searched && !empty($results)): ?>
                <div class="mt-4 pt-3 border-top">
                    <h5 class="small font-weight-700 text-muted uppercase mb-3">Matching Accounts Found:</h5>
                    <div class="list-group">
                        <?php foreach ($results as $account): ?>
                            <div class="card mb-2 p-3 border shadow-none" style="background: #f8fafc; border-radius: 8px;">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <div class="font-weight-700 text-dark"><?php echo htmlspecialchars($account['name']); ?></div>
                                        <div class="small text-muted mb-1"><i class='bx bx-envelope'></i> <?php echo htmlspecialchars(maskEmail($account['email'])); ?></div>
                                        <?php if (!empty($account['phone'])): ?>
                                            <div class="small text-muted"><i class='bx bx-phone'></i> <?php echo htmlspecialchars($account['phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <a href="forgot_password.php?email=<?php echo urlencode($account['email']); ?>&name=<?php echo urlencode($account['name']); ?>" class="btn btn-outline-primary btn-sm px-3">
                                        Use This Email <i class='bx bx-right-arrow-alt'></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="forgot_password.php" class="text-muted small d-block mb-2">
                    <i class='bx bx-key'></i> Already know your email? Request OTP Password Reset
                </a>
                <a href="login.php" class="text-muted small">
                    <i class='bx bx-left-arrow-alt'></i> Back to Login
                </a>
            </div>
        </div>
        
        <div class="login-footer">
            <p class="mb-0 text-muted" style="font-size: 0.85rem;">
                &copy; <?php echo date('Y'); ?> God's Family United Methodist Church. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
