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

/**
 * Define navigation items based on role.
 * Mapping provided roles to the 3 main permission tiers.
 */
$is_admin = hasRole('Administrator');
$is_staff = hasRole(['Staff', 'Pastor', 'Secretary', 'Committee Head']);
$is_member = hasRole('Member');

?>

<div class="sidebar">
    <div class="sidebar-header" style="height: auto; min-height: 130px; padding: 20px 10px; display: flex; align-items: center; justify-content: center;">
        <div class="sidebar-logo d-flex flex-column align-items-center text-center">
            <img src="assets/images/logo.png" alt="Logo" style="height: 70px; width: 70px; border-radius: 50%; object-fit: cover; margin-bottom: 12px; border: 2px solid rgba(255,255,255,0.15);">
            <h2 style="margin: 0; font-size: 0.85rem; letter-spacing: 1.5px; font-weight: 800;">SYSTEM CMS</h2>
        </div>
    </div>

    <!-- User Information -->
    <div class="user-info">
        <span class="user-name"><?php echo htmlspecialchars($user_name); ?></span>
        <span class="user-role"><?php echo htmlspecialchars($role); ?></span>
    </div>

    <div class="sidebar-nav">
        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class='bx bxs-dashboard'></i>
            <span>Dashboard</span>
        </a>

        <?php if ($is_admin): ?>
            <a href="users.php" class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                <i class='bx bxs-group'></i>
                <span>User Accounts</span>
            </a>
        <?php endif; ?>

        <?php if ($is_admin || $is_staff): ?>
            <a href="members.php" class="nav-link <?php echo $current_page === 'members.php' ? 'active' : ''; ?>">
                <i class='bx bxs-user-detail'></i>
                <span>Member List</span>
            </a>
            <a href="events.php" class="nav-link <?php echo $current_page === 'events.php' ? 'active' : ''; ?>">
                <i class='bx bxs-calendar'></i>
                <span>Events</span>
            </a>
        <?php endif; ?>

        <?php if ($is_member): ?>
            <a href="profile.php" class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                <i class='bx bxs-id-card'></i>
                <span>ID Card</span>
            </a>
        <?php endif; ?>

        <a href="announcements.php" class="nav-link <?php echo $current_page === 'announcements.php' ? 'active' : ''; ?>">
            <i class='bx bxs-megaphone'></i>
            <span>Announcements</span>
        </a>

        <a href="#" class="nav-link" id="logoutTrigger" onclick="openLogoutModal(event)" style="margin-top: auto;">
            <i class='bx bxs-log-out'></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<div class="main-content">
    <div class="top-header">
        <h2 class="page-title"><?php echo isset($page_title) ? $page_title : 'Dashboard'; ?></h2>
        <div class="header-actions">
            <span id="currentTime" class="small text-muted"></span>
        </div>
    </div>
    
    <div class="content-area">

<!-- ===== LOGOUT CONFIRMATION MODAL ===== -->
<div class="modal-overlay" id="logoutModal" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle">
    <div class="modal-content logout-modal-content">
        <div class="logout-modal-icon">
            <i class='bx bxs-log-out'></i>
        </div>
        <h3 id="logoutModalTitle" class="logout-modal-title">Sign Out?</h3>
        <p class="logout-modal-message">You are about to sign out of the system. Any unsaved changes will be lost.</p>
        <div class="logout-modal-actions">
            <button class="btn btn-secondary" id="logoutCancelBtn" onclick="closeLogoutModal()">Cancel</button>
            <form method="POST" action="logout.php" style="margin:0;">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <button type="submit" class="btn btn-danger" id="logoutConfirmBtn">
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
        // Trap focus on cancel button for accessibility
        document.getElementById('logoutCancelBtn').focus();
    }

    function closeLogoutModal() {
        const modal = document.getElementById('logoutModal');
        modal.classList.remove('active');
        // Return focus to the trigger
        const trigger = document.getElementById('logoutTrigger');
        if (trigger) trigger.focus();
    }

    // Close modal when clicking the dark overlay (outside the card)
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('logoutModal');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeLogoutModal();
            });
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('logoutModal');
            if (modal && modal.classList.contains('active')) closeLogoutModal();
        }
    });
</script>
