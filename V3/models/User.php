<?php
/**
 * User Model
 */
class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function all() {
        $stmt = $this->pdo->query(
            "SELECT u.*, r.role_name, m.photo_path AS member_photo
             FROM users u
             JOIN roles r ON u.role_id = r.role_id
             LEFT JOIN members m ON u.member_id = m.member_id
             ORDER BY u.name ASC"
        );
        return $stmt->fetchAll();
    }

    public function getRoles() {
        $stmt = $this->pdo->query("SELECT * FROM roles ORDER BY role_name ASC");
        return $stmt->fetchAll();
    }

    public function create($data) {
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (role_id, member_id, name, email, password) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['role_id'],
            $data['member_id'] ?? null,
            $data['name'],
            $data['email'],
            $hashedPassword
        ]);
    }

    public function update($id, $data) {
        $sql = "UPDATE users SET role_id = ?, name = ?, email = ?";
        $params = [$data['role_id'], $data['name'], $data['email']];
        
        if (!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE user_id = ?";
        $params[] = $id;
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function findWithRole($id) {
        $stmt = $this->pdo->prepare(
            "SELECT u.user_id, u.name, u.email, u.role_id, r.role_name
             FROM users u
             JOIN roles r ON u.role_id = r.role_id
             WHERE u.user_id = ?"
        );
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE user_id = ?");
        return $stmt->execute([(int)$id]);
    }

    /**
     * Get all unique emails for announcement broadcasting.
     */
    public function getEmails() {
        $stmt = $this->pdo->query("SELECT DISTINCT email FROM users WHERE email IS NOT NULL AND email != ''");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
