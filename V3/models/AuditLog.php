<?php
/**
 * AuditLog Model
 * Records and retrieves system activity and security audit logs.
 */
class AuditLog {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Record an audit log entry.
     */
    public static function record($pdo, $userId, string $action, string $details, ?string $ipAddress = null, ?string $userAgent = null): bool {
        try {
            $ip = $ipAddress ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $ua = $userAgent ?? substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

            $stmt = $pdo->prepare(
                "INSERT INTO audit_logs (user_id, action, details, ip_address, user_agent)
                 VALUES (?, ?, ?, ?, ?)"
            );
            return $stmt->execute([
                $userId ?: null,
                strtoupper(trim($action)),
                $details,
                $ip,
                $ua
            ]);
        } catch (\Exception $e) {
            error_log("AuditLog Record Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Instance method for recording audit logs.
     */
    public function log($userId, string $action, string $details, ?string $ipAddress = null, ?string $userAgent = null): bool {
        return self::record($this->pdo, $userId, $action, $details, $ipAddress, $userAgent);
    }

    /**
     * Fetch logs with optional filtering.
     */
    public function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array {
        $sql = "SELECT a.*, u.name AS user_name, u.email AS user_email, r.role_name
                FROM audit_logs a
                LEFT JOIN users u ON a.user_id = u.user_id
                LEFT JOIN roles r ON u.role_id = r.role_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['action'])) {
            $sql .= " AND a.action = ?";
            $params[] = strtoupper($filters['action']);
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (a.details LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR a.ip_address LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(a.created_at) >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(a.created_at) <= ?";
            $params[] = $filters['end_date'];
        }

        $sql .= " ORDER BY a.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Count logs for pagination.
     */
    public function countLogs(array $filters = []): int {
        $sql = "SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.user_id WHERE 1=1";
        $params = [];

        if (!empty($filters['action'])) {
            $sql .= " AND a.action = ?";
            $params[] = strtoupper($filters['action']);
        }

        if (!empty($filters['user_id'])) {
            $sql .= " AND a.user_id = ?";
            $params[] = (int)$filters['user_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (a.details LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR a.ip_address LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(a.created_at) >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(a.created_at) <= ?";
            $params[] = $filters['end_date'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get distinct action types for filter options.
     */
    public function getDistinctActions(): array {
        $stmt = $this->pdo->query("SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL ORDER BY action ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
