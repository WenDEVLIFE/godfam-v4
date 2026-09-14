<?php
/**
 * Announcement Model
 */
class Announcement {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function latest($limit = 10) {
        $stmt = $this->pdo->prepare("SELECT a.*, u.name as author FROM announcements a JOIN users u ON a.created_by = u.user_id ORDER BY a.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, u.name as author FROM announcements a
             JOIN users u ON a.created_by = u.user_id
             WHERE a.announcement_id = ?"
        );
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    public function update($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE announcements SET title = ?, content = ?, image_path = COALESCE(?, image_path) WHERE announcement_id = ?");
        return $stmt->execute([
            $data['title'],
            $data['content'],
            $data['image_path'] ?? null,
            (int)$id
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?");
        return $stmt->execute([(int)$id]);
    }

    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO announcements (title, content, created_by, image_path) VALUES (?, ?, ?, ?)");
        $ok = $stmt->execute([
            $data['title'],
            $data['content'],
            $data['created_by'],
            $data['image_path'] ?? null
        ]);

        if (!$ok) {
            return false;
        }

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Persist per-recipient delivery status for an announcement.
     * Returns false only when a DB error occurs.
     */
    public function recordNotificationAttempt($announcementId, $recipientEmail, $status, $errorMessage = null) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO announcement_notifications (announcement_id, recipient_email, status, error_message, sent_at)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE status = VALUES(status), error_message = VALUES(error_message), sent_at = NOW()"
        );

        return $stmt->execute([
            $announcementId,
            $recipientEmail,
            $status,
            $errorMessage
        ]);
    }

    /**
     * Returns true if this announcement was already attempted for this recipient.
     */
    public function hasNotificationAttempt($announcementId, $recipientEmail) {
        $stmt = $this->pdo->prepare(
            "SELECT notification_id FROM announcement_notifications
             WHERE announcement_id = ? AND recipient_email = ?
             LIMIT 1"
        );
        $stmt->execute([$announcementId, $recipientEmail]);
        return (bool)$stmt->fetchColumn();
    }
}
