<?php
/**
 * Role Management & Access Control View
 * Admin only, read-only. Lists the 4 protected system roles
 * (Administrator, Member, Secretary, Treasurer) with their assignment counts.
 */

$page_title = 'System Roles & Permissions';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../controllers/RoleController.php';

requireLogin();
authorizeRoles(['Administrator']);

$controller = new RoleController($pdo);

$roles = $controller->index();
$csrf_token = generateCsrfToken();

include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
        <h1 class="page-title">Role Management & Access Control</h1>
        <p class="text-muted small">View the system roles. Core system roles are protected from alteration.</p>
    </div>
</div>

<!-- System Protection Notice Banner -->
<div class="alert alert-info shadow-sm mb-4 d-flex align-items-center gap-3" style="background: rgba(14, 165, 233, 0.08); border-left: 4px solid #0ea5e9; border-radius: 12px; color: #0369a1;">
    <i class='bx bxs-shield-quarter' style="font-size: 2rem;"></i>
    <div>
        <strong style="font-size: 0.95rem;">System Protection Active</strong>
        <p class="mb-0 small text-muted">Default system roles (Administrator, Member, Secretary, Treasurer) are protected. They cannot be renamed, modified, or deleted to maintain system integrity.</p>
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
                        <th class="pe-4 text-end" style="width: 160px;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roles as $role): ?>
                        <?php
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
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1" style="border-radius: 20px; font-weight: 600;">
                                    <i class='bx bxs-lock-alt me-1'></i> Protected System Role
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border px-3 py-1 font-weight-600" style="font-size: 0.85rem;">
                                    <i class='bx bxs-user me-1'></i> <?php echo $total_assigned; ?>
                                </span>
                            </td>
                            <td class="pe-4 text-end">
                                <button class="btn btn-sm btn-light border text-muted px-3" disabled title="Protected system roles cannot be modified or deleted.">
                                    <i class='bx bxs-lock-alt me-1'></i> Protected
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
