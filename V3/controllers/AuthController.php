<?php
/**
 * AuthController
 * Handles login and logout operations.
 */

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/middleware/CSRF.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';

require_once BASE_PATH . '/utils/MailService.php';

/**
 * Handle user login.
 */
function login($email, $password) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT u.user_id, u.member_id, u.name, u.email, u.password, r.role_name FROM users u 
                               JOIN roles r ON u.role_id = r.role_id 
                               WHERE u.email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Password matches, create session
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['member_id'] = $user['member_id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_name'] = $user['role_name'];
            return true;
        }
        return false;
    } catch (\PDOException $e) {
        // Log the error
        return false;
    }
}

/**
 * Handle user logout.
 */
function logout() {
    $_SESSION = [];
    session_destroy();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    header("Location: login.php");
    exit;
}

/**
 * Request Password Reset OTP
 */
function requestOTP($email, $name) {
    global $pdo;

    // Check if user exists and name matches
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND name = ?");
    $stmt->execute([$email, $name]);
    if (!$stmt->fetch()) {
        return "No account found with this email and name.";
    }

    // Generate 6-digit OTP using cryptographically secure random_int
    $otp = sprintf("%06d", random_int(100000, 999999));
    $hashed_otp = password_hash($otp, PASSWORD_DEFAULT);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    try {
        // Clear any existing OTPs for this email
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

        // Store new hashed OTP and reset attempts
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, otp, expires_at, attempts) VALUES (?, ?, ?, 0)");
        $stmt->execute([$email, $hashed_otp, $expires]);

        // Send Email
        $mailService = new MailService();
        if ($mailService->sendOTPMail($email, $otp)) {
            return true;
        }
        return "Failed to send OTP email.";
    } catch (\PDOException $e) {
        return "Database error: " . $e->getMessage();
    }
}

/**
 * Verify OTP
 */
function verifyOTP($email, $otp) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT otp, attempts, expires_at FROM password_resets WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();

    if (!$row) return false;

    // Check if already reached max attempts
    if ($row['attempts'] >= 3) {
        return "You have exceeded the maximum number of attempts. Please request a new OTP.";
    }

    // Check expiration
    if (strtotime($row['expires_at']) < time()) {
        return "The OTP has expired. Please request a new one.";
    }

    // Verify hashed OTP
    if (password_verify($otp, $row['otp'])) {
        return true;
    } else {
        // Increment attempts on failure
        $pdo->prepare("UPDATE password_resets SET attempts = attempts + 1 WHERE email = ?")->execute([$email]);
        $remaining = 3 - ($row['attempts'] + 1);
        if ($remaining <= 0) {
            return "Too many incorrect attempts. This OTP is now invalid.";
        }
        return "Incorrect OTP. You have $remaining attempts left.";
    }
}

/**
 * Update Password
 */
function updatePassword($email, $otp, $new_password) {
    global $pdo;

    // First verify again for security
    $verifyResult = verifyOTP($email, $otp);
    if ($verifyResult !== true) {
        return $verifyResult ?: "Invalid or expired OTP.";
    }

    try {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $result = $stmt->execute([$hashed_password, $email]);

        if ($result) {
            // Delete the OTP after successful reset
            $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);
            return true;
        }
        return "Failed to update password.";
    } catch (\PDOException $e) {
        return "Error updating password: " . $e->getMessage();
    }
}
