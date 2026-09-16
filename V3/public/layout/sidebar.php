<?php
/**
 * Sidebar Navigation Layout
 * Adapts to user roles (Admin, Staff, Member).
 */

require_once __DIR__ . '/../../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../../middleware/CSRF.php';

$role = $_SESSION['role_name'] ?? 'Member';
$user_name = $_SESSION['name'] ?? 'Guest';
$current_page = basename($_SERVER['PHP_SELF']);
$csrf_token = generateCsrfToken();

$is_admin = hasRole('Administrator');
$is_staff = hasRole(['Staff', 'Pastor', 'Secretary', 'Committee Head']);
$is_member = hasRole('Member');

// Initial for user avatar
$user_initial = !empty($user_name) ? strtoupper(substr($user_name, 0, 1)) : 'U';
?>

<!-- Mobile Sidebar Overlay Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="assets/images/logo.png" alt="Church Logo">
            <h2></h2>
            <span>GOD'S FAMILY UNITED METHODIST CHURCH</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section-title">Main Navigation</div>
        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class='bx bxs-dashboard'></i>
            <span>Dashboard</span>
        </a>

        <?php if ($is_admin): ?>
            <a href="users.php" class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                <i class='bx bxs-user-account'></i>
                <span>User Accounts</span>
            </a>
        <?php endif; ?>

        <?php if ($is_admin || $is_staff): ?>
            <div class="nav-section-title">Management</div>
            <a href="events.php" class="nav-link <?php echo $current_page === 'events.php' ? 'active' : ''; ?>">
                <i class='bx bxs-calendar-event'></i>
                <span>Church Events</span>
            </a>
        <?php endif; ?>

        <?php if ($is_member): ?>
            <div class="nav-section-title">Member Portal</div>
            <a href="profile.php" class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                <i class='bx bxs-id-card'></i>
                <span>Digital ID Card</span>
            </a>
        <?php endif; ?>

        <div class="nav-section-title">Community</div>
        <a href="announcements.php" class="nav-link <?php echo $current_page === 'announcements.php' ? 'active' : ''; ?>">
            <i class='bx bxs-megaphone'></i>
            <span>Announcements</span>
        </a>

        <?php if ($is_admin || $is_staff): ?>
            <div class="nav-section-title">Analytics & Reports</div>
            <a href="reports.php" class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                <i class='bx bxs-bar-chart-alt-2'></i>
                <span>Attendance Reports</span>
            </a>
            <a href="financial_reports.php" class="nav-link <?php echo $current_page === 'financial_reports.php' ? 'active' : ''; ?>">
                <i class='bx bxs-bank'></i>
                <span>Financial Reports</span>
            </a>
            <a href="audit_logs.php" class="nav-link <?php echo $current_page === 'audit_logs.php' ? 'active' : ''; ?>">
                <i class='bx bxs-shield'></i>
                <span>Audit &amp; Login Logs</span>
            </a>
        <?php endif; ?>

        <a href="#" class="nav-link" id="logoutTrigger" onclick="openLogoutModal(event)" style="margin-top: auto;">
            <i class='bx bxs-log-out-circle'></i>
            <span>Sign Out</span>
        </a>
    </nav>

    <!-- User Profile Footer -->
    <div class="sidebar-user-footer">
        <div class="user-avatar-circle"><?php echo $user_initial; ?></div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
            <span class="user-role"><?php echo htmlspecialchars($role); ?></span>
        </div>
    </div>
</aside>

<div class="main-content">
    <div class="top-header">
        <div class="d-flex align-items-center">
            <button class="mobile-toggle-btn" id="mobileToggle" aria-label="Toggle Sidebar">
                <i class='bx bx-menu'></i>
            </button>
            <h2 class="page-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h2>
        </div>
        <div class="header-actions">
            <div class="clock-badge">
                <i class='bx bx-time-five'></i>
                <span id="currentTime"></span>
            </div>
        </div>
    </div>
    
    <div class="content-area">

<!-- ===== LOGOUT CONFIRMATION MODAL ===== -->
<div class="modal-overlay" id="logoutModal" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle" style="z-index: 9999 !important; backdrop-filter: blur(8px);">
    <div class="modal-content" style="max-width: 440px !important; text-align: center !important; padding: 2.25rem !important; border-radius: 16px !important; border: 1px solid rgba(0,0,0,0.06) !important; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.35) !important; margin: auto !important; background: #ffffff !important;">
        <div style="width: 64px; height: 64px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.25rem auto;">
            <i class='bx bxs-log-out'></i>
        </div>
        <h4 id="logoutModalTitle" style="font-weight: 800; font-size: 1.35rem; color: #1e293b; margin-bottom: 0.5rem; margin-top: 0;">Sign Out?</h4>
        <p style="color: #64748b; font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.5;">You are about to sign out of the system. Any unsaved changes will be lost.</p>
        <div style="display: flex !important; justify-content: center !important; align-items: center !important; gap: 12px !important;">
            <button type="button" class="btn btn-outline-secondary px-4 py-2" id="logoutCancelBtn" style="font-weight: 600; border-radius: 8px; cursor: pointer;" onclick="closeLogoutModal()">Cancel</button>
            <form method="POST" action="logout.php" style="margin: 0 !important; display: inline-block !important;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="btn btn-danger px-4 py-2 shadow-sm" id="logoutConfirmBtn" style="font-weight: 600; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                    <i class='bx bxs-log-out'></i> Yes, Sign Out
                </button>
            </form>
        </div>
    </div>
</div>
<!-- ===== END LOGOUT MODAL ===== -->

<script>
    function openLogoutModal(e) {
        e.preventDefault();
        const modal = document.getElementById('logoutModal');
        modal.classList.add('active');
        document.getElementById('logoutCancelBtn').focus();
    }

    function closeLogoutModal() {
        const modal = document.getElementById('logoutModal');
        modal.classList.remove('active');
        const trigger = document.getElementById('logoutTrigger');
        if (trigger) trigger.focus();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeLogoutModal();
            });
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('logoutModal');
            if (modal && modal.classList.contains('active')) closeLogoutModal();
        }
    });
</script>
