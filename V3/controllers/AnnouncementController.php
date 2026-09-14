<?php
/**
 * Announcement Controller
 */

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/Announcement.php';
require_once BASE_PATH . '/models/User.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/middleware/CSRF.php';
require_once BASE_PATH . '/utils/MailService.php';

class AnnouncementController {
    private $announcementModel;
    private $userModel;
    private $mailService;
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->announcementModel = new Announcement($pdo);
        $this->userModel = new User($pdo);
        $this->mailService = new MailService();
    }

    /**
     * Get latest announcements.
     * Roles: Everyone authenticated
     */
    public function index($limit = 10) {
        requireLogin();
        return $this->announcementModel->latest($limit);
    }

    /**
     * Create an announcement.
     * Roles: Administrator, Secretary
     */
    public function create($data, $files = null) {
        authorizeRoles(['Administrator', 'Secretary']);

        if (empty($data['title']) || empty($data['content'])) {
            return "Title and content are required.";
        }

        $title = trim((string)$data['title']);
        $content = trim((string)$data['content']);
        if ($title === '' || $content === '') {
            return "Title and content are required.";
        }

        $data['title'] = $title;
        $data['content'] = $content;
        $data['created_by'] = (int)$_SESSION['user_id'];
        $sendEmailNotification = !empty($data['send_email_notification']);

        $image_path = null;
        if ($files && isset($files['announcement_image']) && $files['announcement_image']['error'] === UPLOAD_ERR_OK) {
            $file = $files['announcement_image'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('ann_') . '.' . $ext;
            $target = BASE_PATH . '/public/uploads/announcements/' . $filename;
            
            // Ensure directory exists
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $image_path = 'uploads/announcements/' . $filename;
            }
        }
        $data['image_path'] = $image_path;

        $announcementId = $this->announcementModel->create($data);
        if ($announcementId !== false) {
            if (!$sendEmailNotification) {
                return [
                    'announcement_saved' => true,
                    'notification_status' => 'not_sent',
                    'message' => 'Announcement published successfully.'
                ];
            }

            $recipients = $this->collectRecipientEmails();
            if (empty($recipients)) {
                return [
                    'announcement_saved' => true,
                    'notification_status' => 'not_sent',
                    'message' => 'Announcement published successfully. No valid recipient emails were found.'
                ];
            } else {
                return $this->sendAnnouncementNotifications($announcementId, $data, $recipients);
            }
        }
        return "Failed to create announcement.";
    }

    /**
     * Update an announcement.
     * Roles: Administrator, Secretary
     */
    public function update($data, $files = null) {
        authorizeRoles(['Administrator', 'Secretary']);

        $id = isset($data['announcement_id']) ? (int)$data['announcement_id'] : 0;
        if ($id <= 0) {
            return 'Invalid announcement.';
        }

        if (empty($data['title']) || empty($data['content'])) {
            return 'Title and content are required.';
        }

        $title = trim((string)$data['title']);
        $content = trim((string)$data['content']);
        if ($title === '' || $content === '') {
            return 'Title and content are required.';
        }

        $existing = $this->announcementModel->find($id);
        if (!$existing) {
            return 'Announcement not found.';
        }

        $updateData = ['title' => $title, 'content' => $content];

        if ($files && isset($files['announcement_image']) && $files['announcement_image']['error'] === UPLOAD_ERR_OK) {
            $file = $files['announcement_image'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('ann_') . '.' . $ext;
            $target = BASE_PATH . '/public/uploads/announcements/' . $filename;
            
            if (!is_dir(dirname($target))) {
                mkdir(dirname($target), 0777, true);
            }

            if (move_uploaded_file($file['tmp_name'], $target)) {
                $updateData['image_path'] = 'uploads/announcements/' . $filename;
                // Optional: Delete old image if it exists
            }
        }

        if ($this->announcementModel->update($id, $updateData)) {
            return [
                'announcement_saved' => true,
                'message' => 'Announcement updated successfully.',
            ];
        }

        return 'Failed to update announcement.';
    }

    /**
     * Delete an announcement.
     * Roles: Administrator, Secretary
     */
    public function delete($id) {
        authorizeRoles(['Administrator', 'Secretary']);

        $id = (int)$id;
        if ($id <= 0) {
            return 'Invalid announcement.';
        }

        if (!$this->announcementModel->find($id)) {
            return 'Announcement not found.';
        }

        if ($this->announcementModel->delete($id)) {
            return [
                'announcement_saved' => true,
                'message' => 'Announcement deleted successfully.',
            ];
        }

        return 'Failed to delete announcement.';
    }

    private function collectRecipientEmails() {
        $userEmails = $this->userModel->getEmails();

        $normalized = [];
        foreach ($userEmails as $email) {
            $clean = strtolower(trim((string)$email));
            if ($clean === '') {
                continue;
            }
            if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $normalized[$clean] = true;
        }

        return array_keys($normalized);
    }

    private function sendAnnouncementNotifications($announcementId, $data, $recipients) {
        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        $publisherRole = $_SESSION['role_name'] ?? 'System';
        $publishedAt = date('M d, Y h:i A');

        foreach ($recipients as $recipientEmail) {
            if ($this->announcementModel->hasNotificationAttempt($announcementId, $recipientEmail)) {
                $skippedCount++;
                continue;
            }

            $result = $this->mailService->sendAnnouncement(
                $data['title'],
                $data['content'],
                $recipientEmail,
                $publishedAt,
                $publisherRole
            );

            if (!empty($result['success'])) {
                $successCount++;
                $this->announcementModel->recordNotificationAttempt($announcementId, $recipientEmail, 'sent', null);
            } else {
                $failedCount++;
                $safeError = 'Failed to send.';
                $this->announcementModel->recordNotificationAttempt($announcementId, $recipientEmail, 'failed', $safeError);
                error_log('Announcement mail send failed for ' . $recipientEmail . ': ' . ($result['internal_error'] ?? $result['error'] ?? 'unknown error'));
            }
        }

        if ($successCount > 0 && $failedCount === 0) {
            return [
                'announcement_saved' => true,
                'notification_status' => 'sent',
                'message' => "Announcement published and emailed to {$successCount} recipient(s)."
            ];
        }

        if ($successCount > 0 && $failedCount > 0) {
            return [
                'announcement_saved' => true,
                'notification_status' => 'partial_failed',
                'message' => "Announcement published. Email sent to {$successCount} recipient(s), {$failedCount} failed."
            ];
        }

        if ($failedCount > 0) {
            return [
                'announcement_saved' => true,
                'notification_status' => 'failed',
                'message' => "Announcement published successfully, but email notifications could not be delivered."
            ];
        }

        return [
            'announcement_saved' => true,
            'notification_status' => 'not_sent',
            'message' => $skippedCount > 0
                ? 'Announcement published successfully. Email notifications were already attempted for all recipients.'
                : 'Announcement published successfully.'
        ];
    }
}
