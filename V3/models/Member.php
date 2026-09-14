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
        $stmt = $this->pdo->prepare("INSERT INTO members (full_name, photo_path, email, phone, contact_info, address, status, qr_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([
            $data['full_name'],
            $data['photo_path'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['contact_info'] ?? null,
            $data['address'] ?? null,
            $data['status'] ?? 'active',
            $qrToken
        ])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }

    public function update($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE members SET full_name = ?, photo_path = ?, email = ?, phone = ?, contact_info = ?, address = ?, status = ? WHERE member_id = ?");
        return $stmt->execute([
            $data['full_name'],
            $data['photo_path'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['contact_info'] ?? null,
            $data['address'] ?? null,
            $data['status'] ?? 'active',
            $id
        ]);
    }

    /**
     * Automatically update member status based on attendance record.
     * Rule: If 3 recorded 'Absent' entries exist, mark as inactive.
     * Only auto-updates if not currently 'visiting'.
     */
    public function recalculateStatus($id) {
        $member = $this->find($id);
        if (!$member || $member['status'] === 'visiting') return false;

        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT event_id) FROM attendance WHERE member_id = ? AND status = 'Absent'");
        $stmt->execute([$id]);
        $absences = $stmt->fetchColumn();

        $newStatus = ($absences >= 3) ? 'inactive' : 'active';
        
        if ($member['status'] !== $newStatus) {
            $stmt = $this->pdo->prepare("UPDATE members SET status = ? WHERE member_id = ?");
            return $stmt->execute([$newStatus, $id]);
        }
        return true;
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
        // We select the email directly from members, or link to users if they have an account
        $stmt = $this->pdo->query("SELECT DISTINCT email FROM members WHERE email IS NOT NULL AND email != ''");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
