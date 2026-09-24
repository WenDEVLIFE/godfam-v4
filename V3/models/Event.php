<?php
/**
 * Event Model
 */
class Event {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getPDO() {
        return $this->pdo;
    }

    public function all() {
        $stmt = $this->pdo->query("SELECT * FROM events ORDER BY date DESC, time DESC");
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM events WHERE event_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create($data) {
        if ($this->existsDuplicate($data)) {
            return false;
        }

        $stmt = $this->pdo->prepare("INSERT INTO events (title, description, date, time, location, status) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['date'],
            $data['time'] ?? null,
            $data['location'] ?? null,
            $data['status'] ?? 'upcoming'
        ]);
    }

    public function existsDuplicate($data, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM events
                WHERE title = ?
                  AND date = ?
                  AND ((time IS NULL AND ? IS NULL) OR time = ?)
                  AND ((location IS NULL AND ? IS NULL) OR location = ?)";

        $params = [
            $data['title'],
            $data['date'],
            $data['time'] ?? null,
            $data['time'] ?? null,
            $data['location'] ?? null,
            $data['location'] ?? null,
        ];

        if ($excludeId !== null) {
            $sql .= " AND event_id <> ?";
            $params[] = (int)$excludeId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function update($id, $data) {
        if ($this->existsDuplicate($data, $id)) {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE events SET title = ?, description = ?, date = ?, time = ?, location = ?, status = ? WHERE event_id = ?");
        return $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['date'],
            $data['time'] ?? null,
            $data['location'] ?? null,
            $data['status'] ?? 'upcoming',
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM events WHERE event_id = ?");
        return $stmt->execute([$id]);
    }

    public function deleteIfNoAttendance($id) {
        $id = (int)$id;

        try {
            $this->pdo->beginTransaction();

            $eventStmt = $this->pdo->prepare("SELECT event_id FROM events WHERE event_id = ? FOR UPDATE");
            $eventStmt->execute([$id]);
            if (!$eventStmt->fetch()) {
                $this->pdo->rollBack();
                return [
                    'deleted' => false,
                    'not_found' => true,
                    'attendance_count' => 0,
                ];
            }

            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM attendance WHERE event_id = ?");
            $countStmt->execute([$id]);
            $attendanceCount = (int)$countStmt->fetchColumn();
            if ($attendanceCount > 0) {
                $this->pdo->rollBack();
                return [
                    'deleted' => false,
                    'not_found' => false,
                    'attendance_count' => $attendanceCount,
                ];
            }

            $deleteStmt = $this->pdo->prepare("DELETE FROM events WHERE event_id = ?");
            $deleted = $deleteStmt->execute([$id]);
            $this->pdo->commit();

            return [
                'deleted' => $deleted,
                'not_found' => false,
                'attendance_count' => 0,
            ];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return [
                'deleted' => false,
                'not_found' => false,
                'attendance_count' => 0,
                'error' => true,
            ];
        }
    }

    public function countAttendance($id) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM attendance WHERE event_id = ?");
        $stmt->execute([(int)$id]);
        return (int)$stmt->fetchColumn();
    }

    public function getUpcoming($limit = null) {
        $sql = "SELECT * FROM events
                WHERE date > CURDATE()
                   OR (date = CURDATE() AND (time IS NULL OR time >= CURTIME()))
                ORDER BY date ASC, time IS NULL ASC, time ASC";
        if ($limit) $sql .= " LIMIT " . (int)$limit;
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function countUpcoming() {
        $stmt = $this->pdo->query(
            "SELECT COUNT(*) FROM events
             WHERE date > CURDATE()
                OR (date = CURDATE() AND (time IS NULL OR time >= CURTIME()))"
        );
        return (int)$stmt->fetchColumn();
    }

    public function getTodayEvents() {
        $stmt = $this->pdo->query("SELECT * FROM events WHERE date = CURDATE() ORDER BY time IS NULL ASC, time ASC");
        return $stmt->fetchAll();
    }

    public function getUpcomingEventsWithinDays($days = 7) {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM events
             WHERE date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
             ORDER BY date ASC, time IS NULL ASC, time ASC"
        );
        $stmt->execute([(int)$days]);
        return $stmt->fetchAll();
    }

    public function getPast($limit = null) {
        $sql = "SELECT * FROM events WHERE date < CURDATE() ORDER BY date DESC, time DESC";
        if ($limit) $sql .= " LIMIT " . (int)$limit;
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
