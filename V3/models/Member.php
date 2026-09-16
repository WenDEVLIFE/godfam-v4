<?php
/**
 * Member Model
 */
class Member {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function all() {
        $stmt = $this->pdo->query("SELECT m.*, u.user_id as linked_user_id, u.email as user_email, u.role_id
                                FROM members m 
                                LEFT JOIN users u ON m.member_id = u.member_id 
                                ORDER BY m.full_name ASC");
        return $stmt->fetchAll();
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT m.*, u.user_id as linked_user_id, u.email as user_email, r.role_name 
                                   FROM members m 
                                   LEFT JOIN users u ON m.member_id = u.member_id 
                                   LEFT JOIN roles r ON u.role_id = r.role_id
                                   WHERE m.member_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Find a member record by email address.
     * Used as a safe fallback when a member user account exists
     * but session/member linkage is temporarily missing.
     */
    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT m.*, u.user_id as linked_user_id, u.email as user_email, r.role_name
                                   FROM members m
                                   LEFT JOIN users u ON m.member_id = u.member_id
                                   LEFT JOIN roles r ON u.role_id = r.role_id
                                   WHERE m.email = ?
                                   LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function create($data) {
        $qrToken = bin2hex(random_bytes(16));
        $stmt = $this->pdo->prepare("INSERT INTO members (full_name, photo_path, email, phone, contact_info, address, birthday, status, qr_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([
            $data['full_name'],
            $data['photo_path'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['contact_info'] ?? null,
            $data['address'] ?? null,
            !empty($data['birthday']) ? $data['birthday'] : null,
            $data['status'] ?? 'active',
            $qrToken
        ])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE members SET full_name = ?, photo_path = ?, email = ?, phone = ?, contact_info = ?, address = ?, birthday = ?, status = ? WHERE member_id = ?");
        return $stmt->execute([
            $data['full_name'],
            $data['photo_path'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['contact_info'] ?? null,
            $data['address'] ?? null,
            !empty($data['birthday']) ? $data['birthday'] : null,
            $data['status'] ?? 'active',
            $id
        ]);
    }

    /**
     * Automatically update member status based on attendance record.
     * Rule: If 3 or more consecutive 'Absent' entries exist (or 3+ total recorded absences), mark as inactive.
     * If member attends service ('Present'), restore to 'active'.
     * Only auto-updates if not currently 'visiting'.
     */
    public function recalculateStatus($id) {
        $member = $this->find($id);
        if (!$member || $member['status'] === 'visiting') return false;

        // Fetch recent attendance entries for this member sorted by date DESC
        $stmt = $this->pdo->prepare("
            SELECT status FROM attendance 
            WHERE member_id = ? 
            ORDER BY date DESC, created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$id]);
        $recentLogs = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $consecutiveAbsences = 0;
        foreach ($recentLogs as $logStatus) {
            if (strtolower($logStatus) === 'absent') {
                $consecutiveAbsences++;
            } else if (strtolower($logStatus) === 'present') {
                break;
            }
        }

        // Total distinct absent count
        $stmtCount = $this->pdo->prepare("SELECT COUNT(DISTINCT event_id) FROM attendance WHERE member_id = ? AND status = 'Absent'");
        $stmtCount->execute([$id]);
        $totalAbsences = (int)$stmtCount->fetchColumn();

        $newStatus = ($consecutiveAbsences >= 3 || $totalAbsences >= 3) ? 'inactive' : 'active';
        
        if ($member['status'] !== $newStatus) {
            $stmtUpdate = $this->pdo->prepare("UPDATE members SET status = ? WHERE member_id = ?");
            return $stmtUpdate->execute([$newStatus, $id]);
        }
        return true;
    }

    /**
     * Bulk recalculate status for all active/inactive members.
     */
    public function recalculateAllMembersStatus() {
        $stmt = $this->pdo->query("SELECT member_id FROM members WHERE status != 'visiting'");
        $members = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        $updated = 0;
        foreach ($members as $memberId) {
            if ($this->recalculateStatus($memberId)) {
                $updated++;
            }
        }
        return $updated;
    }

    public function delete($id) {
        try {
            $this->pdo->beginTransaction();
            // Delete related user if exists
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE member_id = ?");
            $stmt->execute([$id]);
            
            // Delete member
            $stmt = $this->pdo->prepare("DELETE FROM members WHERE member_id = ?");
            $result = $stmt->execute([$id]);
            
            $this->pdo->commit();
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function findByQrToken($token) {
        $stmt = $this->pdo->prepare("SELECT * FROM members WHERE qr_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }


    /**
     * Force-regenerate a fresh QR token for a specific member.
     * Used when a member's card needs to be reissued or a token is suspected compromised.
     */
    public function regenerateToken($memberId) {
        $newToken = bin2hex(random_bytes(16));
        $stmt = $this->pdo->prepare("UPDATE members SET qr_token = ? WHERE member_id = ?");
        if ($stmt->execute([$newToken, $memberId])) {
            return $newToken;
        }
        return false;
    }

    public function createLinkedUser($memberId, $email, $password) {
        $member = $this->find($memberId);
        if (!$member) return false;

        // Check if email already exists in users table
        $stmt = $this->pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception("Email '$email' is already in use by another account.");
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Role ID 4 is 'Member'
        $stmt = $this->pdo->prepare("INSERT INTO users (role_id, member_id, name, email, password) VALUES (4, ?, ?, ?, ?)");
        return $stmt->execute([
            $memberId,
            $member['full_name'],
            $email,
            $hashedPassword
        ]);
    }

    /**
     * Get all unique emails for announcement broadcasting.
     */
    public function getEmails() {
        $stmt = $this->pdo->query("SELECT DISTINCT email FROM members WHERE email IS NOT NULL AND email != ''");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get members whose birthday is today (ignores year).
     */
    public function getBirthdayCelebrantsToday(): array {
        $stmt = $this->pdo->query(
            "SELECT member_id, full_name, birthday, photo_path
             FROM members
             WHERE birthday IS NOT NULL
               AND MONTH(birthday) = MONTH(CURDATE())
               AND DAY(birthday)   = DAY(CURDATE())
             ORDER BY full_name ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Get new member registration counts per month for the last N months.
     * Returns array of ['month_label' => 'Jan 2026', 'count' => 5].
     */
    public function getMembersGrowthByMonth(int $months = 12): array {
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(created_at, '%b %Y') AS month_label,
                    DATE_FORMAT(created_at, '%Y-%m')  AS month_key,
                    COUNT(*) AS count
             FROM members
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
             GROUP BY month_key, month_label
             ORDER BY month_key ASC"
        );
        $stmt->execute([$months]);
        return $stmt->fetchAll();
    }
}
