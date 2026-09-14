<?php
/**
 * Users View - System Account Management.
 * Accessible by Admin only.
 */

$page_title = 'System Users';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/UserController.php';

// Ensure user is authorized
if (!isAdmin()) {
    header("Location: dashboard.php");
    exit;
}

$controller = new UserController($pdo);
$error = '';
$success = '';

// Handle Actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');

    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $result = $controller->add($_POST);
        if ($result === true) $success = "User account created successfully.";
        else $error = $result;
    } elseif ($action === 'delete') {
        $result = $controller->delete($_POST['id'] ?? 0);
        if ($result === true) $success = "User account deleted successfully.";
        else $error = $result;
    }
}

$users = $controller->index();
$roles = $controller->roleList();
$csrf_token = generateCsrfToken();

function userAvatarPath($photoPath) {
    $fallback = 'assets/images/default_avatar.png';
    $photoPath = trim((string)$photoPath);

    if ($photoPath === '' || strpos($photoPath, '..') !== false || preg_match('/^(?:[a-z]+:)?\/\//i', $photoPath)) {
        return $fallback;
    }

    $normalizedPath = ltrim(str_replace('\\', '/', $photoPath), '/');
    if (strpos($normalizedPath, 'assets/uploads/members/') !== 0) {
        return $fallback;
    }

    $fullPath = realpath(__DIR__ . '/' . $normalizedPath);
    $publicRoot = realpath(__DIR__);

    if (!$fullPath || !$publicRoot || strpos($fullPath, $publicRoot . DIRECTORY_SEPARATOR) !== 0 || !is_file($fullPath)) {
        return $fallback;
    }

    return $normalizedPath;
}

// Include Layout Header
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="fade-in">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 style="font-weight: 800; font-size: 1.8rem; color: var(--primary-color);">System Administration</h2>
            <p style="color: var(--muted-text); font-size: 1rem;">Manage privileged access and ministry staff accounts.</p>
        </div>
        <button onclick="toggleModal('addUserModal')" class="btn btn-primary shadow-sm" style="padding: 14px 32px;">
            <i class='bx bx-user-plus' style="font-size: 1.2rem;"></i> Create Account
        </button>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger shadow-sm"><i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success shadow-sm"><i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent py-4 px-4" style="border-bottom: 1px solid rgba(0,0,0,0.03);">
            <h5 style="margin: 0; font-weight: 800; color: var(--dark-text); opacity: 0.8;">PRIVILEGED ACCOUNTS</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="padding-left: 32px;">User</th>
                            <th>Email Address</th>
                            <th>System Role</th>
                            <th class="text-right" style="padding-right: 32px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <?php $isProtectedAccount = in_array($u['role_name'], ['Administrator', 'Secretary'], true); ?>
                        <?php $avatar = userAvatarPath($u['member_photo'] ?? ''); ?>
                        <tr style="border-bottom: 1px solid rgba(0,0,0,0.02);">
                            <td style="padding-left: 32px;">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($avatar); ?>" class="user-avatar" alt="Profile">
                                    <div>
                                        <div style="font-weight: 700; color: var(--dark-text);"><?php echo htmlspecialchars($u['name']); ?></div>
                                        <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
                                            <span style="font-size: 0.6rem; color: var(--primary-color); font-weight: 800; text-transform: uppercase;">Current Session</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td style="color: var(--muted-text); font-weight: 500;"><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="badge badge-secondary" style="background: rgba(30, 58, 138, 0.05); color: var(--navy-blue); border: none;">
                                    <i class='bx bx-lock-alt'></i> <?php echo htmlspecialchars($u['role_name']); ?>
                                </span>
                            </td>
                            <td class="text-right" style="padding-right: 32px;">
                                <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
                                    <span class="badge badge-info shadow-none" style="opacity: 0.7;">ACTIVE</span>
                                <?php elseif ($isProtectedAccount): ?>
                                    <span class="badge badge-secondary shadow-none" style="opacity: 0.75;" title="Protected account - cannot be deleted">
                                        Protected
                                    </span>
                                <?php else: ?>
                                    <form method="POST" style="display:inline;" onsubmit="event.preventDefault(); var form = this; confirmAction('Are you sure you want to delete this system user? This cannot be undone.', function() { form.submit(); });">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$u['user_id']; ?>">
                                        <button type="submit" class="btn btn-sm" style="background: rgba(210, 38, 48, 0.05); color: var(--church-red); padding: 8px; border-radius: 10px;" title="Delete User">
                                            <i class='bx bx-trash' style="font-size: 1.1rem;"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal-overlay" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class='bx bx-plus-circle'></i> Create New System User</h3>
                <button class="modal-close" onclick="toggleModal('addUserModal')"><i class='bx bx-x'></i></button>
            </div>
            <div class="modal-body">
                <form method="POST" class="row">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" placeholder="The name used in system" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="login@church.com" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">System Role</label>
                        <select name="role_id" class="form-select" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Initial Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>

                    <div class="col-12 mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <input type="checkbox" id="user_agree" required style="width: 16px; height: 16px;">
                            <label for="user_agree" class="small text-muted mb-0">I confirm the user has been notified of and agrees to the Church Privacy Policy.</label>
                        </div>
                    </div>
                    
                    <div class="col-12 text-right">
                        <p class="text-muted small mb-3">Note: System users can be staff or administrators who manage the church data.</p>
                        <button type="button" class="btn btn-light mr-2" onclick="toggleModal('addUserModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary px-5">Create Account <i class='bx bx-check'></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 1px solid #ddd;
        margin-right: 14px;
        display: inline-block;
        flex: 0 0 40px;
        background: var(--ivory-bg);
    }
    </style>

    <script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        
        if (modal.classList.contains('active')) {
            modal.classList.remove('active');
        } else {
            modal.classList.add('active');
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.classList.remove('active');
        }
    }
    </script>
</div>

<?php 
// Include Layout Footer
include __DIR__ . '/layout/footer.php';
?>
