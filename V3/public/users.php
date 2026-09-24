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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');

    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $result = $controller->add($_POST);
        if ($result === true) $success = "User account created successfully.";
        else $error = $result;
    } elseif ($action === 'edit') {
        $result = $controller->edit($_POST['id'] ?? 0, $_POST);
        if ($result === true) $success = "User account updated successfully.";
        else $error = $result;
    } elseif ($action === 'delete') {
        $result = $controller->delete($_POST['id'] ?? 0);
        if ($result === true) $success = "User account deleted successfully.";
        else $error = $result;
    }
}

$allUsers = $controller->index(true);
$filterRole = $_GET['filter'] ?? 'member';

$users = array_filter($allUsers, function($u) use ($filterRole) {
    if (strtolower($u['role_name'] ?? '') === 'administrator') {
        return false;
    }
    if ($filterRole === 'all') {
        return true;
    }
    return strtolower($u['role_name'] ?? '') === strtolower($filterRole);
});

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

<div class="fade-in py-2">
    <!-- Top Header -->
    <div class="mb-4">
        <h2 style="font-weight: 800; font-size: 1.85rem; color: var(--primary-color); margin-bottom: 6px;">System Users</h2>
        <p style="color: var(--muted-text); font-size: 1.02rem; margin-bottom: 0;">Manage member user accounts, system access, and credentials.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger shadow-sm mb-4" style="border-radius: 8px; padding: 14px 18px;"><i class='bx bx-error-circle'></i> <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success shadow-sm mb-4" style="border-radius: 8px; padding: 14px 18px;"><i class='bx bx-check-circle'></i> <?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-5" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-transparent py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
            <div class="btn-group" role="group" aria-label="Account Filter">
                <a href="users.php?filter=member" class="btn btn-sm <?php echo $filterRole !== 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>" style="font-weight: 600; padding: 8px 20px; border-radius: 6px 0 0 6px;">Member Accounts</a>
                <a href="users.php?filter=all" class="btn btn-sm <?php echo $filterRole === 'all' ? 'btn-primary' : 'btn-outline-secondary'; ?>" style="font-weight: 600; padding: 8px 20px; border-radius: 0 6px 6px 0;">All Accounts</a>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge badge-info" style="font-size: 12px; padding: 8px 14px; border-radius: 20px;"><?php echo count($users); ?> Accounts</span>
                <button onclick="toggleModal('addUserModal')" class="btn btn-primary shadow-sm" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; font-weight: 600; font-size: 0.875rem; border-radius: 8px;">
                    <i class='bx bx-user-plus' style="font-size: 1.1rem;"></i> Create Account
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th style="padding: 18px 24px;">User Name</th>
                            <th style="padding: 18px 24px;">Email Address</th>
                            <th style="padding: 18px 24px;">Account Role</th>
                            <th class="text-right" style="padding: 18px 24px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" class="text-center p-4 text-muted">No user accounts found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                            <?php $isProtectedAccount = in_array($u['role_name'], ['Administrator', 'Secretary'], true); ?>
                            <?php $avatar = userAvatarPath($u['member_photo'] ?? ''); ?>
                            <tr style="border-bottom: 1px solid rgba(0,0,0,0.03);">
                                <td style="padding-left: 24px;">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($avatar); ?>" class="user-avatar" alt="Profile">
                                        <div>
                                            <div style="font-weight: 700; color: var(--dark-text);"><?php echo htmlspecialchars($u['name']); ?></div>
                                            <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
                                                <span style="font-size: 0.65rem; color: var(--primary-color); font-weight: 800; text-transform: uppercase;">Current Session</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td style="color: var(--muted-text); font-weight: 500;"><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="badge badge-secondary" style="background: rgba(30, 58, 138, 0.05); color: var(--navy-blue); border: none;">
                                        <i class='bx bx-user-check'></i> <?php echo htmlspecialchars($u['role_name']); ?>
                                    </span>
                                </td>
                                <td class="text-right" style="padding-right: 24px;">
                                    <div class="d-flex justify-content-end align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary" style="display: inline-flex; align-items: center; gap: 4px;"
                                                onclick="openEditUserModal(<?php echo (int)$u['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($u['name'])); ?>', '<?php echo htmlspecialchars(addslashes($u['email'])); ?>', <?php echo (int)$u['role_id']; ?>, '<?php echo htmlspecialchars($u['birthday'] ?? ''); ?>', '<?php echo htmlspecialchars($u['wedding_anniversary'] ?? ''); ?>')" 
                                                title="Edit User Account">
                                            <i class='bx bx-edit'></i> Edit
                                        </button>
                                        
                                        <?php if ($u['user_id'] == $_SESSION['user_id']): ?>
                                            <span class="badge badge-info shadow-none" style="opacity: 0.7; align-self: center;">ACTIVE</span>
                                        <?php elseif ($isProtectedAccount): ?>
                                            <span class="badge badge-secondary shadow-none" style="opacity: 0.75; align-self: center;" title="Protected account - cannot be deleted">
                                                Protected
                                            </span>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" style="display: inline-flex; align-items: center; gap: 4px;"
                                                    onclick="openDeleteUserModal(<?php echo (int)$u['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($u['name'])); ?>')" 
                                                    title="Delete User Account">
                                                <i class='bx bx-trash'></i> Delete
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal-overlay" id="addUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="mb-0"><i class='bx bx-user-plus' style="color:var(--cms-red); margin-right:8px;"></i>Create New User Account</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleModal('addUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Full name of user" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="user@church.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">System Role</label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>" <?php echo strtolower($role['role_name']) === 'member' ? 'selected' : ''; ?>><?php echo htmlspecialchars($role['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Initial Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Birthday (Optional)</label>
                            <input type="date" name="birthday" class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Wedding Anniversary (Optional)</label>
                            <input type="date" name="wedding_anniversary" class="form-control">
                        </div>

                        <div class="form-group full-width mb-0">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" id="user_agree" required style="width: 16px; height: 16px;">
                                <label for="user_agree" class="small text-muted mb-0" style="cursor:pointer;">I confirm the user account credentials have been verified.</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleModal('addUserModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class='bx bx-check-circle'></i> Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal-overlay" id="editUserModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="mb-0"><i class='bx bx-edit' style="color:var(--cms-blue); margin-right:8px;"></i>Edit User Account</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleModal('editUserModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="editUserForm" onsubmit="return confirmEditUserSubmission(event, this)">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_user_id" value="">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" id="edit_user_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" id="edit_user_email" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Account Role</label>
                            <select name="role_id" id="edit_user_role_id" class="form-select" required>
                                <?php foreach ($roles as $role): ?>
                                    <option value="<?php echo $role['role_id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">New Password (Optional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep unchanged">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Birthday</label>
                            <input type="date" name="birthday" id="edit_user_birthday" class="form-control">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Wedding Anniversary</label>
                            <input type="date" name="wedding_anniversary" id="edit_user_wedding_anniversary" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <p class="text-muted small mb-0 mr-auto">Note: Leaving password empty preserves the existing password.</p>
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleModal('editUserModal')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div class="modal-overlay" id="deleteUserModal">
        <div class="modal-content" style="max-width: 440px;">
            <div class="modal-header">
                <h3 class="mb-0 text-danger"><i class='bx bx-trash' style="margin-right:8px;"></i>Confirm Account Deletion</h3>
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleModal('deleteUserModal')">&times;</button>
            </div>
            <div class="modal-body text-center py-4">
                <i class='bx bx-error-circle' style="font-size: 3.5rem; color: #dc2626; margin-bottom: 12px; display: block;"></i>
                <h4 style="font-weight: 700; color: #1f2937; margin-bottom: 8px;">Are you sure?</h4>
                <p class="text-muted small mb-4">You are about to delete the user account for <strong id="delete_user_name_text" class="text-dark">this member</strong>. This action cannot be undone.</p>

                <form method="POST" id="deleteUserForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_user_id" value="">

                    <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="toggleModal('deleteUserModal')">Cancel</button>
                        <button type="submit" class="btn btn-danger"><i class='bx bx-trash'></i> Delete Account</button>
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

    function openEditUserModal(id, name, email, roleId, birthday, weddingAnniversary) {
        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_user_name').value = name;
        document.getElementById('edit_user_email').value = email;
        document.getElementById('edit_user_role_id').value = roleId;
        document.getElementById('edit_user_birthday').value = birthday || '';
        document.getElementById('edit_user_wedding_anniversary').value = weddingAnniversary || '';
        toggleModal('editUserModal');
    }

    function openDeleteUserModal(id, name) {
        document.getElementById('delete_user_id').value = id;
        document.getElementById('delete_user_name_text').textContent = name;
        toggleModal('deleteUserModal');
    }

    function confirmEditUserSubmission(event, form) {
        event.preventDefault();
        const userName = document.getElementById('edit_user_name').value;
        
        if (confirm(`Are you sure you want to save changes for user account "${userName}"?`)) {
            form.submit();
        }
        return false;
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
