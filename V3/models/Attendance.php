<?php
/**
 * Attendance Model
 */
class Attendance {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getByEvent($eventId) {
        $stmt = $this->pdo->prepare("SELECT a.*, m.full_name FROM attendance a JOIN members m ON a.member_id = m.member_id WHERE a.event_id = ? ORDER BY m.full_name ASC");
        $stmt->execute([$eventId]);
        return $stmt->fetchAll();
    }

    public function getPDO() {
        return $this->pdo;
    }

    public function record($data) {
        $status = $data['status'] ?? 'Present';
        
        if ($status === 'Time Out') {
            // Update existing record with time_out
            $stmt = $this->pdo->prepare("UPDATE attendance SET time_out = NOW() WHERE member_id = ? AND event_id = ? AND date = ?");
            $stmt->execute([$data['member_id'], $data['event_id'], $data['date']]);
            return $stmt->rowCount() > 0;
        }

        // Prevent duplicate
        if ($this->checkExists($data['member_id'], $data['event_id'], $data['date'])) {
            return false;
        }

        $stmt = $this->pdo->prepare("INSERT INTO attendance (member_id, event_id, date, status) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['member_id'],
            $data['event_id'],
            $data['date'],
            $status
        ]);
    }

    /**
     * Record time out for an attendance entry.
     */
    public function recordTimeOut($attendanceId) {
        $stmt = $this->pdo->prepare("UPDATE attendance SET time_out = NOW() WHERE attendance_id = ?");
        return $stmt->execute([(int)$attendanceId]);
    }

    /**
     * Find a specific attendance record.
     */
    public function getRecord($memberId, $eventId, $date) {
        $stmt = $this->pdo->prepare("SELECT * FROM attendance WHERE member_id = ? AND event_id = ? AND date = ? LIMIT 1");
        $stmt->execute([$memberId, $eventId, $date]);
        return $stmt->fetch();
    }

    /**
     * Get attendance log with filters for export / reporting.
     */
    public function getLog($filters = []) {
        $sql = "SELECT a.*, m.full_name, e.title as event_title, e.date as event_date 
                FROM attendance a 
                JOIN members m ON a.member_id = m.member_id 
                JOIN events e ON a.event_id = e.event_id 
                WHERE 1=1";
        $params = [];

        if (!empty($filters['event_id'])) {
            $sql .= " AND a.event_id = ?";
            $params[] = $filters['event_id'];
        }
        if (!empty($filters['member_id'])) {
            $sql .= " AND a.member_id = ?";
            $params[] = $filters['member_id'];
        }
        if (!empty($filters['status'])) {
            $sql .= " AND a.status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['start_date'])) {
            $sql .= " AND a.date >= ?";
            $params[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $sql .= " AND a.date <= ?";
            $params[] = $filters['end_date'];
        }

        $sql .= " ORDER BY a.date DESC, a.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function checkExists($memberId, $eventId, $date) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM attendance WHERE member_id = ? AND event_id = ? AND date = ?");
        $stmt->execute([$memberId, $eventId, $date]);
        return (bool)$stmt->fetch();
    }

    public function getByMember($memberId) {
        $stmt = $this->pdo->prepare("SELECT a.*, e.title as event_title, e.date as event_date 
                                FROM attendance a 
                                JOIN events e ON a.event_id = e.event_id 
                                WHERE a.member_id = ? 
                                ORDER BY e.date DESC, a.created_at DESC");
        $stmt->execute([$memberId]);
        return $stmt->fetchAll();
    }

    public function countAttendedServices($memberId) {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT event_id)
             FROM attendance
             WHERE member_id = ?
               AND status = 'Present'"
        );
        $stmt->execute([(int)$memberId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get per-day present count for the last 7 days.
     * Returns array of ['date' => 'YYYY-MM-DD', 'label' => 'Mon Jul 28', 'count' => 12].
     */
    public function getWeeklyStats(): array {
        $stmt = $this->pdo->query(
            "SELECT
                a.date,
                DATE_FORMAT(a.date, '%a %b %d') AS label,
                COUNT(*) AS count
             FROM attendance a
             WHERE a.date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
               AND a.status = 'Present'
             GROUP BY a.date
             ORDER BY a.date ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Get per-month present count for each month of the given year.
     * Returns array of ['month_num' => 1..12, 'label' => 'Jan', 'count' => 45].
     */
    public function getMonthlyStats(int $year): array {
        $stmt = $this->pdo->prepare(
            "SELECT
                MONTH(a.date)                      AS month_num,
                DATE_FORMAT(a.date, '%b')          AS label,
                COUNT(*)                           AS count
             FROM attendance a
             WHERE YEAR(a.date) = ?
               AND a.status = 'Present'
             GROUP BY MONTH(a.date), label
             ORDER BY month_num ASC"
        );
        $stmt->execute([$year]);
        return $stmt->fetchAll();
    }
}
