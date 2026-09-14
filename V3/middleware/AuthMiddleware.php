<?php
/**
 * Authentication and Role-Based Access Control (RBAC) Middleware.
 */

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    $envSecure = getenv('SESSION_COOKIE_SECURE');
    $cookieSecure = ($envSecure === '1' || strtolower((string)$envSecure) === 'true') ? true : $isHttps;

    session_start([
        'cookie_httponly' => true,
        'cookie_secure'   => $cookieSecure,
        'cookie_samesite' => 'Lax',
    ]);
}

/**
 * Check if the user is authenticated.
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login if user is not authenticated.
 */
function requireLogin() {
    if (!isAuthenticated()) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Redirect to dashboard if user is already authenticated.
 */
function redirectIfLogged() {
    if (isAuthenticated()) {
        header("Location: dashboard.php");
        exit;
    }
}

/**
 * Check if user has specific roles.
 */
function hasRole($roles) {
    if (!isset($_SESSION['role_name'])) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['role_name'], $roles);
    }
    
    return $_SESSION['role_name'] === $roles;
}

/**
 * Enforce role mapping.
 */
function authorizeRoles($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        header("HTTP/1.1 403 Forbidden");
        die("Unauthorized Access: You do not have the required permissions.");
    }
}

/**
 * Helper for Admin role.
 */
function isAdmin() {
    return hasRole('Administrator');
}

/**
 * Helper for Staff roles (inclusive of Admin/Pastor/Secretary/Committee Head).
 */
function isStaff() {
    return hasRole(['Administrator', 'Staff', 'Pastor', 'Secretary', 'Committee Head']);
}

/**
 * Helper for Member role.
 */
function isMember() {
    return hasRole('Member');
}
