<?php
/**
 * Announcements View - Church Communications Feed.
 * Accessible by all (Role-based CRUD).
 */

$page_title = 'Church Announcements';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../controllers/AnnouncementController.php';

// Ensure user is logged in
requireLogin();

$controller = new AnnouncementController($pdo);
$error = '';
$success = '';
$canManageAnnouncements = hasRole(['Administrator', 'Secretary']);

// Handle Actions (Administrator/Secretary only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canManageAnnouncements) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF Token Validation Failed.");
    }

    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $result = $controller->create($_POST, $_FILES);
        if (is_array($result) && !empty($result['announcement_saved'])) {
            $success = $result['message'] ?? "Announcement published successfully.";
        } elseif ($result === true) {
            $success = "Announcement published successfully.";
        } else {
            $error = (string)$result;
        }
    } elseif ($action === 'update') {
        $result = $controller->update($_POST, $_FILES);
        if (is_array($result) && !empty($result['announcement_saved'])) {
            $success = $result['message'] ?? 'Announcement updated successfully.';
        } else {
            $error = (string)$result;
        }
    } elseif ($action === 'delete') {
        $result = $controller->delete((int)($_POST['announcement_id'] ?? 0));
        if (is_array($result) && !empty($result['announcement_saved'])) {
            $success = $result['message'] ?? 'Announcement deleted successfully.';
        } else {
            $error = (string)$result;
        }
    }
}

$announcements = $controller->index();
$csrf_token = generateCsrfToken();

// Include Layout Header
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<style>
.announcement-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin-top: 10px;
}

.announcement-card {
    height: 100%;
    display: flex;
    flex-direction: column;
    border: none;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.announcement-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.1);
}

.announcement-image-container {
    height: 160px;
    position: relative;
    overflow: hidden;
    background: #f8fafc;
}

.announcement-image-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.announcement-card:hover .announcement-image-container img {
    transform: scale(1.05);
}

.announcement-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: rgba(255, 255, 255, 0.9);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 800;
    color: var(--accent);
    backdrop-filter: blur(4px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.announcement-content {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    font-size: 0.85rem;
    line-height: 1.6;
    color: #64748b;
    margin-bottom: 1rem;
}

.announcement-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.6rem;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-footer {
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    padding: 12px 16px;
}

.announcement-author-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.announcement-author-avatar {
    width: 24px;
    height: 24px;
    background: var(--accent);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
}

@media (min-width: 1200px) {
    .announcement-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
</style>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="page-title">Announcements</h1>
                    <p class="text-muted small">System Updates and Communications Feed</p>
                </div>
                <?php if ($canManageAnnouncements): ?>
                    <button type="button" onclick="document.getElementById('addAnnouncementModal').classList.add('active')" class="btn btn-primary">
                        <i class='bx bx-plus-circle'></i> Publish Announcement
                    </button>
                <?php endif; ?>
            </div>

        <div class="mb-4">
            <input type="text" id="announcementSearch" class="form-control" placeholder="Search announcements..." onkeyup="filterAnnouncements()">
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (empty($announcements)): ?>
            <p class="text-center text-muted p-5 bg-white border">No announcements shared yet.</p>
        <?php else: ?>
            <div id="announcementFeed" class="announcement-grid">
                <?php foreach ($announcements as $ann): ?>
                <div class="card announcement-card">
                    <?php if (!empty($ann['image_path'])): ?>
                        <div class="announcement-image-container" onclick="viewAnnouncementPicture('<?php echo htmlspecialchars($ann['image_path']); ?>')" style="cursor: pointer;">
                            <span class="announcement-badge">NEW</span>
                            <img src="<?php echo htmlspecialchars($ann['image_path']); ?>" alt="Announcement Image">
                        </div>
                    <?php endif; ?>
                    <div class="card-header d-none">
                        <!-- Hidden but kept for logic if needed -->
                    </div>
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge badge-info" style="font-size: 10px;">UPDATE</span>
                            <span class="text-muted small"><?php echo date('M d, Y', strtotime($ann['created_at'])); ?></span>
                        </div>
                        <h4 class="announcement-title" title="<?php echo htmlspecialchars($ann['title']); ?>"><?php echo htmlspecialchars($ann['title']); ?></h4>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($ann['content'])); ?>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="announcement-author-info">
                            <div class="announcement-author-avatar">
                                <?php echo strtoupper(substr($ann['author'] ?? 'A', 0, 1)); ?>
                            </div>
                            <span class="small font-weight-600"><?php echo htmlspecialchars($ann['author'] ?? 'Admin'); ?></span>
                        </div>
                        <div class="d-flex align-items-center announcement-card-actions">
                            <span class="small text-muted"><?php echo date('h:i A', strtotime($ann['created_at'])); ?></span>
                            <?php if ($canManageAnnouncements): ?>
                                <button type="button"
                                    class="btn btn-outline-primary btn-sm edit-announcement-btn"
                                    data-id="<?php echo (int)$ann['announcement_id']; ?>"
                                    data-title="<?php echo htmlspecialchars($ann['title'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-content="<?php echo htmlspecialchars($ann['content'], ENT_QUOTES, 'UTF-8'); ?>">
                                    Edit
                                </button>
                                <form method="post" class="announcement-inline-form" onsubmit="event.preventDefault(); var form = this; confirmAction('Delete this announcement? This cannot be undone.', function() { form.submit(); });">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="announcement_id" value="<?php echo (int)$ann['announcement_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<!-- Add Announcement Modal -->
<div class="modal-overlay" id="addAnnouncementModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="mb-0">New Announcement</h3>
            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('addAnnouncementModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label class="form-label">Headline</label>
                    <input type="text" name="title" class="form-control" placeholder="Subject..." required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Message Content</label>
                    <textarea name="content" class="form-control" rows="5" required></textarea>
                </div>

                <div class="form-group mt-3">
                    <label class="form-label">Attach Picture (Optional)</label>
                    <input type="file" name="announcement_image" class="form-control" accept="image/*">
                    <small class="text-muted">Max size: 2MB. Format: JPG, PNG, GIF</small>
                </div>

                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="sendEmailNotification" name="send_email_notification" value="1" checked>
                    <label class="form-check-label" for="sendEmailNotification">
                        Send email notification to registered users
                    </label>
                </div>
                
                <div class="mt-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('addAnnouncementModal').classList.remove('active')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Publish Announcement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Announcement Modal -->
<div class="modal-overlay" id="editAnnouncementModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="mb-0">Edit Announcement</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('editAnnouncementModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="announcement_id" id="edit_announcement_id" value="">

                <div class="form-group">
                    <label class="form-label">Headline</label>
                    <input type="text" name="title" id="edit_title" class="form-control" placeholder="Subject..." required>
                </div>

                <div class="form-group">
                    <label class="form-label">Message Content</label>
                    <textarea name="content" id="edit_content" class="form-control" rows="5" required></textarea>
                </div>

                <div class="form-group mt-3">
                    <label class="form-label">Change Picture (Optional)</label>
                    <input type="file" name="announcement_image" class="form-control" accept="image/*">
                    <small class="text-muted">Leaving this blank will keep the existing picture.</small>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editAnnouncementModal').classList.remove('active')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Picture Viewer Modal -->
<div class="modal-overlay" id="pictureViewerModal">
    <div class="modal-content" style="max-width: 800px; background: transparent; box-shadow: none;">
        <div style="text-align: right; margin-bottom: 10px;">
            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('pictureViewerModal').classList.remove('active')"><i class='bx bx-x'></i> Close</button>
        </div>
        <img id="viewerImage" src="" style="width: 100%; max-height: 80vh; object-fit: contain; border-radius: 8px;">
    </div>
</div>

<script>
function openEditAnnouncementModal(id, title, content) {
    document.getElementById('edit_announcement_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_content').value = content;
    document.getElementById('editAnnouncementModal').classList.add('active');
}

document.querySelectorAll('.edit-announcement-btn').forEach(button => {
    button.addEventListener('click', function() {
        openEditAnnouncementModal(this.dataset.id, this.dataset.title, this.dataset.content);
    });
});

function viewAnnouncementPicture(imagePath) {
    document.getElementById('viewerImage').src = imagePath;
    document.getElementById('pictureViewerModal').classList.add('active');
}

function filterAnnouncements() {
    const input = document.getElementById('announcementSearch');
    const filter = input.value.toLowerCase();
    const cards = document.getElementsByClassName('announcement-card');

    for (let i = 0; i < cards.length; i++) {
        const title = cards[i].querySelector('.announcement-title').textContent.toLowerCase();
        const content = cards[i].querySelector('.announcement-content').textContent.toLowerCase();
        cards[i].style.display = (title.includes(filter) || content.includes(filter)) ? "" : "none";
    }
}
</script>

<?php 
// Include Layout Footer
include __DIR__ . '/layout/footer.php';
?>
