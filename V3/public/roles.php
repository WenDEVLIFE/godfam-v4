<?php
/**
 * Role Management & Access Control View
 * Admin only. Protected system roles (Administrator, Pastor, Secretary, Staff, etc.) cannot be edited or deleted.
 */

$page_title = 'System Roles & Permissions';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../controllers/RoleController.php';

requireLogin();
authorizeRoles(['Administrator']);

$controller = new RoleController($pdo);
$error = '';
$success = '';

// Handle POST actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $result = $controller->add($_POST);
        if ($result === true) {
            $_SESSION['role_flash_success'] = "Custom role created successfully.";
            header("Location: roles.php");
            exit;
        } else {
            $error = $result;
        }
    } elseif ($action === 'edit') {
        $role_id = (int)($_POST['role_id'] ?? 0);
        $result = $controller->edit($role_id, $_POST);
        if ($result === true) {
            $_SESSION['role_flash_success'] = "Custom role updated successfully.";
            header("Location: roles.php");
            exit;
        } else {
            $error = $result;
        }
    } elseif ($action === 'delete') {
        $role_id = (int)($_POST['role_id'] ?? 0);
        $result = $controller->delete($role_id);
        if ($result === true) {
            $_SESSION['role_flash_success'] = "Custom role deleted successfully.";
            header("Location: roles.php");
            exit;
        } else {
            $error = $result;
        }
    }
}

if (!empty($_SESSION['role_flash_success'])) {
    $success = $_SESSION['role_flash_success'];
    unset($_SESSION['role_flash_success']);
}

$roles = $controller->index();
$csrf_token = generateCsrfToken();

include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h1 class="page-title">Role Management & Access Control</h1>
        <p class="text-muted small">View, create, and manage user roles. Core system roles are protected from alteration.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="openAddRoleModal()">
            <i class='bx bx-plus-circle'></i> Add Custom Role
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm mb-4"><i class='bx bx-error-circle me-1'></i> <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success shadow-sm mb-4"><i class='bx bx-check-circle me-1'></i> <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- System Protection Notice Banner -->
<div class="alert alert-info shadow-sm mb-4 d-flex align-items-center gap-3" style="background: rgba(14, 165, 233, 0.08); border-left: 4px solid #0ea5e9; border-radius: 12px; color: #0369a1;">
    <i class='bx bxs-shield-quarter' style="font-size: 2rem;"></i>
    <div>
        <strong style="font-size: 0.95rem;">System Protection Active</strong>
        <p class="mb-0 small text-muted">Default system roles (Administrator, Pastor, Secretary, Staff, Member, Committee Head, Treasurer) are protected. They cannot be renamed, modified, or deleted to maintain system integrity.</p>
    </div>
</div>

<!-- Roles Data Table -->
<div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <tr>
                        <th class="ps-4" style="width: 80px;">ID</th>
                        <th>Role Name</th>
                        <th>Description</th>
                        <th style="width: 180px;">Role Type</th>
                        <th style="width: 140px;" class="text-center">Assigned Users</th>
                        <th class="pe-4 text-end" style="width: 160px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <?php 
                            $is_protected = !empty($role['is_system']);
                            $total_assigned = (int)($role['user_count'] ?? 0) + (int)($role['member_count'] ?? 0);
                        ?>
                        <tr>
                            <td class="ps-4 font-weight-600 text-muted">#<?php echo (int)$role['role_id']; ?></td>
                            <td>
                                <strong class="text-dark d-block"><?php echo htmlspecialchars($role['role_name']); ?></strong>
                                <span class="badge bg-light text-muted border px-2 py-0" style="font-family: monospace; font-weight: 500; font-size: 11px;">
                                    <?php echo htmlspecialchars($role['role_slug']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small">
                                    <?php echo htmlspecialchars($role['description'] ?: 'No description provided.'); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($is_protected): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1" style="border-radius: 20px; font-weight: 600;">
                                        <i class='bx bxs-lock-alt me-1'></i> Protected System Role
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-1" style="border-radius: 20px; font-weight: 600;">
                                        <i class='bx bx-purchase-tag-alt me-1'></i> Custom Role
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-3 py-1 font-weight-600" style="font-size: 0.85rem;">
                                    <i class='bx bxs-user me-1'></i> <?php echo $total_assigned; ?>
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <?php if ($is_protected): ?>
                                    <button class="btn btn-sm btn-light border text-muted px-3" disabled title="Protected system roles cannot be modified or deleted.">
                                        <i class='bx bxs-lock-alt me-1'></i> Protected
                                    </button>
                                <?php else: ?>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary" onclick="openEditRoleModal(<?php echo htmlspecialchars(json_encode($role)); ?>)" title="Edit Custom Role">
                                            <i class='bx bxs-edit'></i> Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteRole(<?php echo (int)$role['role_id']; ?>, '<?php echo htmlspecialchars(addslashes($role['role_name'])); ?>')" title="Delete Custom Role">
                                            <i class='bx bxs-trash'></i> Delete
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Custom Role Modal -->
<div class="modal-overlay" id="addRoleModal">
    <div class="modal-content" style="max-width: 500px; padding: 2rem; border-radius: 16px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0" style="font-weight: 700;">Add Custom Role</h4>
            <button type="button" class="btn-close" onclick="closeAddRoleModal()"></button>
        </div>
        <form method="POST" action="roles.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="add">

            <div class="form-group mb-3">
                <label for="add_role_name" class="form-label font-weight-600">Role Name <span class="text-danger">*</span></label>
                <input type="text" id="add_role_name" name="role_name" class="form-control" placeholder="e.g., Youth Leader, Choir Coordinator" required>
            </div>

            <div class="form-group mb-4">
                <label for="add_description" class="form-label font-weight-600">Description</label>
                <textarea id="add_description" name="description" class="form-control" rows="3" placeholder="Briefly describe the responsibilities of this custom role..."></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" onclick="closeAddRoleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class='bx bx-check me-1'></i> Create Role</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Custom Role Modal -->
<div class="modal-overlay" id="editRoleModal">
    <div class="modal-content" style="max-width: 500px; padding: 2rem; border-radius: 16px;">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0" style="font-weight: 700;">Edit Custom Role</h4>
            <button type="button" class="btn-close" onclick="closeEditRoleModal()"></button>
        </div>
        <form method="POST" action="roles.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="role_id" id="edit_role_id">

            <div class="form-group mb-3">
                <label for="edit_role_name" class="form-label font-weight-600">Role Name <span class="text-danger">*</span></label>
                <input type="text" id="edit_role_name" name="role_name" class="form-control" required>
            </div>

            <div class="form-group mb-4">
                <label for="edit_description" class="form-label font-weight-600">Description</label>
                <textarea id="edit_description" name="description" class="form-control" rows="3"></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary px-4" onclick="closeEditRoleModal()">Cancel</button>
                <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class='bx bx-save me-1'></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Hidden Form -->
<form method="POST" action="roles.php" id="deleteRoleForm" style="display: none;">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="role_id" id="delete_role_id">
</form>

<script>
function openAddRoleModal() {
    document.getElementById('addRoleModal').classList.add('active');
}

function closeAddRoleModal() {
    document.getElementById('addRoleModal').classList.remove('active');
}

function openEditRoleModal(role) {
    document.getElementById('edit_role_id').value = role.role_id;
    document.getElementById('edit_role_name').value = role.role_name;
    document.getElementById('edit_description').value = role.description || '';
    document.getElementById('editRoleModal').classList.add('active');
}

function closeEditRoleModal() {
    document.getElementById('editRoleModal').classList.remove('active');
}

function confirmDeleteRole(roleId, roleName) {
    if (typeof confirmAction === 'function') {
        confirmAction(
            `Are you sure you want to delete the custom role "${roleName}"?`,
            function() {
                document.getElementById('delete_role_id').value = roleId;
                document.getElementById('deleteRoleForm').submit();
            },
            'Delete Custom Role',
            'Make sure no active users or members are assigned to this role before deleting.',
            'Delete Role',
            'Cancel'
        );
    } else {
        if (confirm(`Are you sure you want to delete the custom role "${roleName}"?`)) {
            document.getElementById('delete_role_id').value = roleId;
            document.getElementById('deleteRoleForm').submit();
        }
    }
}
</script>

<?php include __DIR__ . '/layout/footer.php'; ?>
