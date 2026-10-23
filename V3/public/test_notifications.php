<?php
/**
 * Test Notifications - Admin tool for sending real test emails.
 * Accessible by Administrator only.
 */

$page_title = 'Test Notifications';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../utils/TestNotifier.php';

requireLogin();
if (!isAdmin()) {
    header("Location: dashboard.php");
    exit;
}

$notifier = new TestNotifier($pdo);
$error = '';
$success = '';
$results = [];
$defaultEmail = 'camillemadrid629@gmail.com';
$testEmail = $defaultEmail;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        die("CSRF Token Validation Failed.");
    }

    $testEmail = trim((string)($_POST['test_email'] ?? ''));
    $sendAnnouncement = !empty($_POST['send_announcement']);
    $sendBirthday = !empty($_POST['send_birthday']);
    $sendAnniversary = !empty($_POST['send_anniversary']);
    $sendOtp = !empty($_POST['send_otp']);

    if ($testEmail === '' || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid recipient email address.';
    } elseif (!$sendAnnouncement && !$sendBirthday && !$sendAnniversary && !$sendOtp) {
        $error = 'Select at least one item to send.';
    } else {
        $imagePath = null;
        if ($sendAnnouncement && isset($_FILES['test_image']) && $_FILES['test_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['test_image'];
            $ext = strtolower((string)pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $isImage = @getimagesize($file['tmp_name']) !== false;

            if (!in_array($ext, $allowed, true) || !$isImage) {
                $error = 'The test picture must be a JPG, PNG, or WEBP image.';
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = 'The test picture must not exceed 5MB.';
            } else {
                // Test uploads live in their own folder so they never mix with real announcements.
                $filename = uniqid('test_') . '.' . $ext;
                $target = BASE_PATH . '/public/uploads/test_notifications/' . $filename;

                if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0777, true) && !is_dir(dirname($target))) {
                    $error = 'The test upload folder could not be created.';
                } elseif (move_uploaded_file($file['tmp_name'], $target)) {
                    $imagePath = $target;
                } else {
                    $error = 'The test picture could not be uploaded.';
                }
            }
        }

        if ($error === '') {
            if ($sendAnnouncement && $imagePath === null) {
                $imagePath = $notifier->latestAnnouncementImage();
            }

            $announcementTitle = 'Sample Announcement';
            $announcementContent = 'This is a test announcement with an attached photo. If you can see the picture below, image delivery is working.';

            if ($sendAnnouncement) {
                $results[] = $notifier->sendAnnouncementEmail($testEmail, $imagePath, $announcementTitle, $announcementContent);
            }
            if ($sendBirthday) {
                $results[] = $notifier->sendBirthdayEmail($testEmail);
            }
            if ($sendAnniversary) {
                $results[] = $notifier->sendAnniversaryEmail($testEmail);
            }
            if ($sendOtp) {
                $results[] = $notifier->sendOtpEmail($testEmail);
            }

            $sentCount = 0;
            foreach ($results as $result) {
                if (!empty($result['success'])) {
                    $sentCount++;
                }
            }

            $totalCount = count($results);
            if ($sentCount === $totalCount && $totalCount > 0) {
                $success = "All {$totalCount} test email(s) sent successfully to {$testEmail}.";
            } elseif ($sentCount > 0) {
                $success = "{$sentCount} of {$totalCount} test email(s) sent successfully to {$testEmail}.";
            } else {
                $error = 'No test emails could be sent. See the results below for details.';
            }
        }
    }
}

$csrf_token = generateCsrfToken();

include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="page-title">Test Notifications</h1>
                    <p class="text-muted small">Send real test emails to verify delivery, wording, and image handling.</p>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="alert alert-info small mb-4">
                        <i class='bx bx-info-circle'></i>
                        The uploaded picture is inserted into the email body and attached to the message. If no picture is uploaded, the latest announcement picture is used for the announcement test.
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                        <div class="form-group">
                            <label class="form-label">Recipient Email</label>
                            <input type="email" name="test_email" class="form-control" value="<?php echo htmlspecialchars($testEmail); ?>" placeholder="name@example.com" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Picture (Optional)</label>
                            <input type="file" name="test_image" class="form-control" accept="image/*">
                            <small class="text-muted mt-1">Used only for the announcement test. Max size: 5MB. Format: JPG, PNG, WEBP.</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Items to Send</label>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" id="sendAnnouncement" name="send_announcement" value="1" checked style="width: 16px; height: 16px;">
                                    <label class="small text-muted mb-0" for="sendAnnouncement" style="cursor:pointer;">Announcement with picture</label>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" id="sendBirthday" name="send_birthday" value="1" checked style="width: 16px; height: 16px;">
                                    <label class="small text-muted mb-0" for="sendBirthday" style="cursor:pointer;">Birthday greeting</label>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" id="sendAnniversary" name="send_anniversary" value="1" checked style="width: 16px; height: 16px;">
                                    <label class="small text-muted mb-0" for="sendAnniversary" style="cursor:pointer;">Wedding anniversary greeting</label>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="checkbox" id="sendOtp" name="send_otp" value="1" style="width: 16px; height: 16px;">
                                    <label class="small text-muted mb-0" for="sendOtp" style="cursor:pointer;">OTP verification code (password reset template)</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class='bx bx-paper-plane'></i> Send Test Emails</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (!empty($results)): ?>
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-3">Results</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Status</th>
                                        <th>Detail</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results as $result): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string)($result['label'] ?? 'Item')); ?></td>
                                            <td>
                                                <?php if (!empty($result['success'])): ?>
                                                    <span class="badge badge-success">Sent</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Failed</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars((string)($result['detail'] ?? '')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/layout/footer.php';
?>
