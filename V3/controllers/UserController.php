<?php
/**
 * User Controller - Handles Admin-level system user management.
 */

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/User.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/middleware/CSRF.php';

class UserController {
    private $userModel;

    public function __construct($pdo) {
        $this->userModel = new User($pdo);
    }

    /**
     * Display a list of system users.
     */
    public function index($excludeAdmin = true) {
        authorizeRoles(['Administrator']);
        return $this->userModel->all($excludeAdmin);
    }

    /**
     * Fetch available roles.
     */
    public function roleList() {
        authorizeRoles(['Administrator']);
        return $this->userModel->getRoles();
    }

    /**
     * Add a system user.
     */
    public function add($data) {
        authorizeRoles(['Administrator']);
        
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            return "Name, email, and password are required.";
        }

        if ($this->userModel->create($data)) {
            return true;
        }
        return "Failed to create user. Email might already be in use.";
    }

    /**
     * Update a system user.
     */
    public function edit($id, $data) {
        authorizeRoles(['Administrator']);
        
        if (empty($data['name']) || empty($data['email'])) {
            return "Name and email are required.";
        }

        if ($this->userModel->update($id, $data)) {
            return true;
        }
        return "Failed to update user.";
    }

    /**
     * Delete a system user.
     */
    public function delete($id) {
        authorizeRoles(['Administrator']);

        $id = (int)$id;
        if ($id <= 0) {
            return "Invalid user account.";
        }
        
        // Prevent self-deletion
        if ($id == $_SESSION['user_id']) {
            return "You cannot delete your own account.";
        }

        $targetUser = $this->userModel->findWithRole($id);
        if (!$targetUser) {
            return "User account not found.";
        }

        if (in_array($targetUser['role_name'], ['Administrator', 'Secretary'], true)) {
            error_log(sprintf(
                '[SECURITY] Protected user deletion blocked at %s. Acting user: %s. Target user: %s (%s, role: %s). Result: blocked.',
                date('c'),
                $_SESSION['user_id'] ?? 'unknown',
                $targetUser['user_id'],
                $targetUser['email'],
                $targetUser['role_name']
            ));

            return "Administrator and Secretary accounts cannot be deleted.";
        }

        if ($this->userModel->delete($id)) {
            return true;
        }
        return "Failed to delete user.";
    }
}
