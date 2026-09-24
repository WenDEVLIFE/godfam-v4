<?php
/**
 * Notification Model
 * Handles system notifications, reminders, and alerts for Birthdays, Wedding Anniversaries, and Church Events.
 */
class Notification {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create a new notification record.
     */
    public function create($type, $title, $message, $reminderDate = null, $memberId = null) {
        $reminderDate = $reminderDate ?: date('Y-m-d');
        $stmt = $this->pdo->prepare(
            "INSERT INTO notifications (type, title, message, reminder_date, member_id)
             VALUES (?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$type, $title, $message, $reminderDate, $memberId]);
    }

    /**
     * Check if a specific notification was already created today to prevent duplicates.
     */
    public function existsToday($type, $title) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM notifications
             WHERE type = ? AND title = ? AND reminder_date = CURDATE()"
        );
        $stmt->execute([$type, $title]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Fetch unread notifications.
     */
    public function getUnread($limit = 10) {
        $stmt = $this->pdo->prepare(
            "SELECT n.*, m.full_name AS member_name, m.photo_path
             FROM notifications n
             LEFT JOIN members m ON n.member_id = m.member_id
             WHERE n.is_read = 0
             ORDER BY n.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Fetch latest notifications.
     */
    public function getAll($limit = 50) {
        $stmt = $this->pdo->prepare(
            "SELECT n.*, m.full_name AS member_name, m.photo_path
             FROM notifications n
             LEFT JOIN members m ON n.member_id = m.member_id
             ORDER BY n.created_at DESC
             LIMIT ?"
        );
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Mark single notification as read.
     */
    public function markAsRead($id) {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead() {
        $stmt = $this->pdo->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
        return $stmt->rowCount();
    }

    /**
     * Count unread notifications.
     */
    public function countUnread() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
        return (int)$stmt->fetchColumn();
    }
}
