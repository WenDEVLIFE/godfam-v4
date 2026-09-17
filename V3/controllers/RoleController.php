<?php
/**
 * Role Controller - Role Management & Security
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/Role.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/middleware/CSRF.php';

class RoleController {
    private $roleModel;

    public function __construct($pdo) {
        $this->roleModel = new Role($pdo);
    }

    /**
     * Fetch all roles with assigned counts.
     */
    public function index() {
        authorizeRoles(['Administrator']);
        return $this->roleModel->all();
    }

    /**
     * Add a custom role.
     */
    public function add($data) {
        authorizeRoles(['Administrator']);
        return $this->roleModel->create($data);
    }

    /**
     * Edit a custom role.
     */
    public function edit($id, $data) {
        authorizeRoles(['Administrator']);
        return $this->roleModel->update($id, $data);
    }

    /**
     * Delete a custom role.
     */
    public function delete($id) {
        authorizeRoles(['Administrator']);
        return $this->roleModel->delete($id);
    }
}
