<?php
/**
 * Role Model - Data access and protection logic for system and custom roles.
 */

class Role {
    private $pdo;

    // Core system roles that can NEVER be modified, renamed, or deleted
    private static $protectedRoles = [
        'administrator',
        'pastor',
        'secretary',
        'staff',
        'member',
        'committee_head',
        'treasurer'
    ];

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all roles with user count and member count.
     */
    public function all() {
        $sql = "SELECT r.*, 
                    (SELECT COUNT(*) FROM users u WHERE u.role_id = r.role_id) AS user_count,
                    (SELECT COUNT(*) FROM members m WHERE m.role_id = r.role_id) AS member_count
                FROM roles r 
                ORDER BY r.is_system DESC, r.role_name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a single role by ID.
     */
    public function find($role_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE role_id = ?");
        $stmt->execute([$role_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Determine if a role is a protected system role.
     */
    public function isSystemRole($role) {
        if (is_numeric($role)) {
            $roleData = $this->find($role);
            if (!$roleData) return false;
            $role = $roleData;
        }

        if (is_array($role)) {
            if (!empty($role['is_system'])) return true;
            $slug = strtolower($role['role_slug'] ?? str_replace(' ', '_', $role['role_name'] ?? ''));
            $name = strtolower($role['role_name'] ?? '');
            return in_array($slug, self::$protectedRoles) || in_array($name, self::$protectedRoles);
        }

        return false;
    }

    /**
     * Create a new custom role.
     */
    public function create($data) {
        $roleName = trim($data['role_name'] ?? '');
        $description = trim($data['description'] ?? '');

        if (empty($roleName)) {
            return "Role name is required.";
        }

        $roleSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $roleName));

        // Check for duplicate role name or slug
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM roles WHERE role_name = ? OR role_slug = ?");
        $stmt->execute([$roleName, $roleSlug]);
        if ($stmt->fetchColumn() > 0) {
            return "A role with the name '{$roleName}' already exists.";
        }

        $stmt = $this->pdo->prepare("INSERT INTO roles (role_name, role_slug, description, is_system) VALUES (?, ?, ?, 0)");
        if ($stmt->execute([$roleName, $roleSlug, $description])) {
            return true;
        }

        return "Failed to create custom role.";
    }

    /**
     * Update an existing custom role. Rejects protected system roles.
     */
    public function update($role_id, $data) {
        $role = $this->find($role_id);
        if (!$role) {
            return "Role not found.";
        }

        if ($this->isSystemRole($role)) {
            return "Protected system roles (Administrator, Pastor, Secretary, Staff, etc.) cannot be edited or modified.";
        }

        $roleName = trim($data['role_name'] ?? '');
        $description = trim($data['description'] ?? '');

        if (empty($roleName)) {
            return "Role name is required.";
        }

        $roleSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $roleName));

        // Check for duplicate excluding current role
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM roles WHERE (role_name = ? OR role_slug = ?) AND role_id != ?");
        $stmt->execute([$roleName, $roleSlug, $role_id]);
        if ($stmt->fetchColumn() > 0) {
            return "A role with the name '{$roleName}' already exists.";
        }

        $stmt = $this->pdo->prepare("UPDATE roles SET role_name = ?, role_slug = ?, description = ? WHERE role_id = ?");
        if ($stmt->execute([$roleName, $roleSlug, $description, $role_id])) {
            return true;
        }

        return "Failed to update role.";
    }

    /**
     * Delete a custom role. Rejects protected system roles.
     */
    public function delete($role_id) {
        $role = $this->find($role_id);
        if (!$role) {
            return "Role not found.";
        }

        if ($this->isSystemRole($role)) {
            return "Protected system roles (Administrator, Pastor, Secretary, Staff, etc.) cannot be deleted.";
        }

        // Check if any users or members are assigned
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
        $stmt->execute([$role_id]);
        $userCount = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM members WHERE role_id = ?");
        $stmt->execute([$role_id]);
        $memberCount = (int)$stmt->fetchColumn();

        $totalAssigned = $userCount + $memberCount;
        if ($totalAssigned > 0) {
            return "Cannot delete role '{$role['role_name']}' because it is assigned to {$totalAssigned} user(s)/member(s). Reassign them first.";
        }

        $stmt = $this->pdo->prepare("DELETE FROM roles WHERE role_id = ?");
        if ($stmt->execute([$role_id])) {
            return true;
        }

        return "Failed to delete role.";
    }
}
