<?php
/**
 * Collection Model
 * Handles all financial collection records (tithes, offerings, special giving).
 */
class Collection {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Record a new collection entry.
     */
    public function create(array $data): int|false {
        $stmt = $this->pdo->prepare(
            "INSERT INTO collections (member_id, amount, category, payment_method, collection_date, remarks, recorded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        if ($stmt->execute([
            $data['member_id'] ?: null,
            $data['amount'],
            $data['category'],
            $data['payment_method'] ?? 'Cash',
            $data['collection_date'],
            $data['remarks'] ?? null,
            $data['recorded_by'],
        ])) {
            return (int) $this->pdo->lastInsertId();
        }
        return false;
    }

    /**
     * Delete a collection record.
     */
    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM collections WHERE collection_id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get all records for a specific date.
     */
    public function getByDate(string $date): array {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, m.full_name, u.name AS recorded_by_name
             FROM collections c
             LEFT JOIN members m ON c.member_id = m.member_id
             JOIN users u ON c.recorded_by = u.user_id
             WHERE c.collection_date = ?
             ORDER BY c.created_at DESC"
        );
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }

    /**
     * Get all records for a given month/year.
     */
    public function getByMonth(int $year, int $month): array {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, m.full_name, u.name AS recorded_by_name
             FROM collections c
             LEFT JOIN members m ON c.member_id = m.member_id
             JOIN users u ON c.recorded_by = u.user_id
             WHERE YEAR(c.collection_date) = ? AND MONTH(c.collection_date) = ?
             ORDER BY c.collection_date DESC, c.created_at DESC"
        );
        $stmt->execute([$year, $month]);
        return $stmt->fetchAll();
    }

    /**
     * Get today's total collection.
     */
    public function getTodayTotal(): float {
        $stmt = $this->pdo->query(
            "SELECT COALESCE(SUM(amount), 0) FROM collections WHERE collection_date = CURDATE()"
        );
        return (float) $stmt->fetchColumn();
    }

    /**
     * Get this month's total collection.
     */
    public function getMonthTotal(): float {
        $stmt = $this->pdo->query(
            "SELECT COALESCE(SUM(amount), 0) FROM collections
             WHERE YEAR(collection_date) = YEAR(CURDATE()) AND MONTH(collection_date) = MONTH(CURDATE())"
        );
        return (float) $stmt->fetchColumn();
    }

    /**
     * Get monthly totals for the last 12 months, broken down by type.
     * Returns an array keyed by 'YYYY-MM'.
     */
    public function getMonthlyBreakdown(int $months = 12): array {
        $stmt = $this->pdo->prepare(
            "SELECT
                DATE_FORMAT(collection_date, '%Y-%m') AS month_key,
                category,
                SUM(amount) AS total
             FROM collections
             WHERE collection_date >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month_key, category
             ORDER BY month_key ASC"
        );
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }

    /**
     * Get breakdown totals by type for a given date range.
     */
    public function getTypeBreakdown(string $start, string $end): array {
        $stmt = $this->pdo->prepare(
            "SELECT category, COALESCE(SUM(amount), 0) AS total
             FROM collections
             WHERE collection_date BETWEEN ? AND ?
             GROUP BY category"
        );
        $stmt->execute([$start, $end]);
        return $stmt->fetchAll();
    }
}
