<?php
/**
 * Members View - Church Member Management.
 * Accessible by Admin and Staff.
 */

$page_title = 'Member Management';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../controllers/MemberController.php';

// Ensure user is authorized
if (!isAdmin() && !isStaff()) {
    header("Location: dashboard.php");
    exit;
}

$controller = new MemberController($pdo);
$error = '';
$success = '';

// Handle Actions (Add, Edit, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("CSRF Token Validation Failed.");
    }

    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $result = $controller->add($_POST, $_FILES);
        if (is_numeric($result)) $success = "Member added successfully.";
        else $error = $result;
    } elseif ($action === 'edit') {
        $result = $controller->edit($_POST['id'], $_POST, $_FILES);
        if ($result === true) $success = "Member updated successfully.";
        else $error = $result;
    } elseif ($action === 'delete') {
        $result = $controller->delete($_POST['id']);
        if ($result === true) $success = "Member deleted successfully.";
        else $error = $result;
    }
}

$members = $controller->index();
$csrf_token = generateCsrfToken();

// Include Layout Header
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Member Management</h1>
            <p class="text-muted small">System Member Directory and Access Control</p>
        </div>
        <button onclick="document.getElementById('addMemberModal').classList.add('active')" class="btn btn-primary">
            Add New Member
        </button>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>


<div class="card">
    <div class="card-header">ACTIVE MEMBERS</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email / Phone</th>
                        <th>Additional Info</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($members)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No members found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($members as $member): ?>
                        <tr>
                            <td class="small font-weight-700">#<?php echo str_pad($member['member_id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td class="font-weight-600"><?php echo htmlspecialchars($member['full_name']); ?></td>
                            <td class="small">
                                <div><?php echo htmlspecialchars($member['email'] ?? ''); ?></div>
                                <div class="text-muted"><?php echo htmlspecialchars($member['phone'] ?? ''); ?></div>
                            </td>
                            <td class="small text-muted">
                                <?php echo htmlspecialchars($member['contact_info'] ?? '---'); ?>
                            </td>
                            <td>
                                <span class="badge badge-<?php 
                                    echo ($member['status'] === 'active' ? 'success' : ($member['status'] === 'visiting' ? 'info' : 'warning')); 
                                ?>">
                                    <?php echo ucfirst($member['status']); ?>
                                </span>
                            </td>
                            <td class="member-actions-cell">
                                <div class="member-actions">
                                    <button type="button" 
                                            class="btn btn-outline-primary btn-sm view-member-btn" 
                                            data-id="<?php echo $member['member_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($member['full_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($member['email'] ?? ''); ?>"
                                            data-phone="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>"
                                            data-address="<?php echo htmlspecialchars($member['address'] ?? ''); ?>"
                                            data-contact="<?php echo htmlspecialchars($member['contact_info'] ?? ''); ?>"
                                            data-status="<?php echo htmlspecialchars($member['status']); ?>"
                                            data-photo="<?php echo htmlspecialchars($member['photo_path'] ?? ''); ?>"
                                            data-birthday="<?php echo htmlspecialchars($member['birthday'] ?? ''); ?>"
                                            data-wedding-anniversary="<?php echo htmlspecialchars($member['wedding_anniversary'] ?? ''); ?>"
                                            data-role="<?php echo htmlspecialchars($member['role_name'] ?? 'Member'); ?>">
                                        View
                                    </button>
                                    <button type="button" 
                                            class="btn btn-outline-info btn-sm edit-member-btn" 
                                            data-id="<?php echo $member['member_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($member['full_name']); ?>"
                                            data-email="<?php echo htmlspecialchars($member['email'] ?? ''); ?>"
                                            data-phone="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>"
                                            data-address="<?php echo htmlspecialchars($member['address'] ?? ''); ?>"
                                            data-contact="<?php echo htmlspecialchars($member['contact_info'] ?? ''); ?>"
                                            data-photo="<?php echo htmlspecialchars($member['photo_path'] ?? ''); ?>"
                                            data-birthday="<?php echo htmlspecialchars($member['birthday'] ?? ''); ?>"
                                            data-wedding-anniversary="<?php echo htmlspecialchars($member['wedding_anniversary'] ?? ''); ?>"
                                            data-status="<?php echo htmlspecialchars($member['status']); ?>">
                                        Edit
                                    </button>
                                    
                                    <?php if (isAdmin() || isStaff()): ?>
                                        <a href="generate_id_card.php?id=<?php echo $member['member_id']; ?>" class="btn btn-outline-secondary btn-sm"><i class='bx bxs-id-card'></i> Digital ID</a>
                                        <button type="button" class="btn btn-danger btn-sm delete-member-btn" data-id="<?php echo $member['member_id']; ?>" data-name="<?php echo htmlspecialchars($member['full_name']); ?>">Delete</button>
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

<!-- View Member Modal -->
<div class="modal-overlay" id="viewMemberModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="mb-0">Member Details</h3>
            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('viewMemberModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="text-center mb-4">
                <div id="view_photo_preview" class="user-avatar mx-auto" style="width: 100px; height: 100px; font-size: 2rem;"></div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted small uppercase font-weight-bold">Full Name</div>
                <div class="col-8" id="view_full_name"></div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted small uppercase font-weight-bold">Email</div>
                <div class="col-8" id="view_email"></div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted small uppercase font-weight-bold">Phone</div>
                <div class="col-8" id="view_phone"></div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted small uppercase font-weight-bold">Address</div>
                <div class="col-8" id="view_address"></div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted small uppercase font-weight-bold">Birthday</div>
                <div class="col-8" id="view_birthday"></div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted small uppercase font-weight-bold">Wedding Anniversary</div>
                <div class="col-8" id="view_wedding_anniversary"></div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted small uppercase font-weight-bold">Role</div>
                <div class="col-8" id="view_role"></div>
            </div>
            <div class="row mb-3">
                <div class="col-4 text-muted small uppercase font-weight-bold">Status</div>
                <div class="col-8"><span id="view_status_badge" class="badge"></span></div>
            </div>
            <div class="mt-4 pt-3 border-top d-flex gap-2">
                <a href="#" id="view_id_btn" class="btn btn-primary btn-sm">View ID Card</a>
                <button class="btn btn-secondary btn-sm" onclick="document.getElementById('viewMemberModal').classList.remove('active')">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal-overlay" id="addMemberModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="mb-0"><i class='bx bx-user-plus' style="color:var(--cms-red); margin-right:8px;"></i>Add New Member</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('addMemberModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="John Doe" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+639171234567">
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Street, Barangay, City"></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Additional Contact Info</label>
                        <input type="text" name="contact_info" class="form-control" placeholder="Emergency contact, relationship, etc.">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Birthday</label>
                        <input type="date" name="birthday" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Wedding Anniversary</label>
                        <input type="date" name="wedding_anniversary" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Initial Status</label>
                        <select name="status" class="form-control">
                            <option value="active">Active</option>
                            <option value="visiting">Visiting</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Set member login password" required>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Profile Photo (Optional)</label>
                        <input type="file" name="photo" class="form-control" accept="image/jpeg, image/png, image/jpg, image/webp">
                        <p class="text-muted small mt-1 mb-0">Allowed formats: PNG, JPEG, JPG, WEBP. Maximum file size: 5MB.</p>
                    </div>

                    <div class="form-group full-width mb-0">
                        <div class="d-flex align-items-center gap-2">
                            <input type="checkbox" id="member_agree" required style="width: 16px; height: 16px;">
                            <label for="member_agree" class="small text-muted mb-0" style="cursor:pointer;">
                                Member agrees to the <a href="javascript:void(0)" onclick="openPrivacyModal('member_agree')">Church Privacy Policy &amp; Data Protection Consent</a>.
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('addMemberModal').classList.remove('active')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class='bx bx-check-circle'></i> Create Member Record</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div class="modal-overlay" id="editMemberModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="mb-0"><i class='bx bx-edit' style="color:var(--cms-blue); margin-right:8px;"></i>Edit Member Record</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('editMemberModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_member_id">
                
                <div class="form-group full-width mb-4">
                    <label class="form-label">Profile Photo</label>
                    <div class="d-flex align-items-center gap-3">
                        <div id="edit_photo_preview" class="user-avatar" style="width: 64px; height: 64px; font-size: 1.2rem;"></div>
                        <div class="flex-grow-1">
                            <input type="file" name="photo" class="form-control mb-2" accept="image/jpeg, image/png, image/jpg, image/webp">
                            <p class="text-muted small mb-2">Allowed formats: PNG, JPEG, JPG, WEBP. Max: 5MB.</p>
                            <label class="small text-danger mb-0" style="cursor:pointer;">
                                <input type="checkbox" name="delete_photo" value="1"> Remove current photo
                            </label>
                        </div>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control">
                    </div>
                    
                    <div class="form-group full-width">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Birthday</label>
                        <input type="date" name="birthday" id="edit_birthday" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Wedding Anniversary</label>
                        <input type="date" name="wedding_anniversary" id="edit_wedding_anniversary" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-control">
                            <option value="active">Active</option>
                            <option value="visiting">Visiting</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('editMemberModal').classList.remove('active')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal-overlay" id="deleteMemberModal">
    <div class="modal-content" style="max-width: 400px; padding: 25px; text-align: center;">
        <i class='bx bxs-trash' style="font-size: 4rem; color: #e53e3e; margin-bottom: 20px;"></i>
        <h3>Permanent Deletion?</h3>
        <p class="text-muted small">You are about to delete <strong id="delete_member_name"></strong>. This will also remove their user account. This action cannot be undone.</p>
        <form method="POST" class="mt-4">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_member_id">
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-secondary w-100" onclick="document.getElementById('deleteMemberModal').classList.remove('active')">No, Cancel</button>
                <button type="submit" class="btn btn-danger w-100">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<style>
.member-actions-cell { min-width: 360px; }
.member-actions {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
}
.member-actions .btn { white-space: nowrap; }
.row.mb-3 .col-4 { font-size: 11px; letter-spacing: 0.5px; }
</style>

<script>
// View Member
document.querySelectorAll('.view-member-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const d = this.dataset;
        document.getElementById('view_full_name').textContent = d.name;
        document.getElementById('view_email').textContent = d.email;
        document.getElementById('view_phone').textContent = d.phone || '---';
        document.getElementById('view_address').textContent = d.address || '---';
        document.getElementById('view_birthday').textContent = d.birthday ? new Date(d.birthday + 'T00:00:00').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : '---';
        document.getElementById('view_wedding_anniversary').textContent = d.weddingAnniversary ? new Date(d.weddingAnniversary + 'T00:00:00').toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' }) : '---';
        document.getElementById('view_role').textContent = d.role;
        
        const badge = document.getElementById('view_status_badge');
        badge.textContent = d.status.toUpperCase();
        document.getElementById('view_id_btn').href = 'profile.php?id=' + d.id;
        
        // Photo Preview
        const photoPreview = document.getElementById('view_photo_preview');
        if (d.photo) {
            photoPreview.innerHTML = `<img src="${d.photo}" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
        } else {
            photoPreview.innerHTML = d.name.charAt(0).toUpperCase();
            photoPreview.style.background = '#edf2f7';
            photoPreview.style.color = '#4a5568';
        }

        document.getElementById('viewMemberModal').classList.add('active');
    });
});

// Edit Member
document.querySelectorAll('.edit-member-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const d = this.dataset;
        document.getElementById('edit_member_id').value = d.id;
        document.getElementById('edit_full_name').value = d.name;
        document.getElementById('edit_email').value = d.email;
        document.getElementById('edit_phone').value = d.phone;
        document.getElementById('edit_address').value = d.address;
        document.getElementById('edit_status').value = d.status;
        
        if (d.birthday) {
            document.getElementById('edit_birthday').value = d.birthday;
        } else {
            document.getElementById('edit_birthday').value = '';
        }

        if (d.weddingAnniversary) {
            document.getElementById('edit_wedding_anniversary').value = d.weddingAnniversary;
        } else {
            document.getElementById('edit_wedding_anniversary').value = '';
        }
        
        // Photo Preview
        const editPhotoPreview = document.getElementById('edit_photo_preview');
        if (d.photo) {
            editPhotoPreview.innerHTML = `<img src="${d.photo}" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">`;
        } else {
            editPhotoPreview.innerHTML = d.name.charAt(0).toUpperCase();
        }

        document.getElementById('editMemberModal').classList.add('active');
    });
});

// Delete Member
document.querySelectorAll('.delete-member-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('delete_member_id').value = this.dataset.id;
        document.getElementById('delete_member_name').textContent = this.dataset.name;
        document.getElementById('deleteMemberModal').classList.add('active');
    });
});

</script>

<?php 
// Include Layout Footer
include __DIR__ . '/layout/footer.php';
?>
