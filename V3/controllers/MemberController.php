<?php
/**
 * Member Controller
 */

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/Member.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/middleware/CSRF.php';

class MemberController {
    private $memberModel;

    public function __construct($pdo) {
        $this->memberModel = new Member($pdo);
    }

    /**
     * Display a list of members.
     * Roles: Secretary, Committee Head, Staff
     */
    public function index() {
        authorizeRoles(['Secretary', 'Committee Head', 'Staff', 'Administrator']);
        return $this->memberModel->all();
    }

    /**
     * Add a new member.
     * Roles: Secretary, Committee Head, Administrator
     */
    public function add($data, $files = null) {
        authorizeRoles(['Secretary', 'Committee Head', 'Administrator']);
        
        // Validation
        if (empty($data['full_name'])) return "Name is required.";
        if (empty($data['email'])) return "Email is required.";
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) return "Invalid email format.";

        $photoPath = null;
        if ($files && isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->handleUpload($files['photo']);
            if (strpos($photoPath, 'Error:') === 0) return $photoPath;
        }

        // Data Sanitization
        $sanitizedData = [
            'full_name'    => htmlspecialchars(trim($data['full_name'])),
            'email'        => trim($data['email']),
            'phone'        => htmlspecialchars(trim($data['phone'] ?? '')),
            'address'      => htmlspecialchars(trim($data['address'] ?? '')),
            'contact_info' => htmlspecialchars(trim($data['contact_info'] ?? '')),
            'status'       => $data['status'] ?? 'active',
            'photo_path'   => $photoPath
        ];

        $result = $this->memberModel->create($sanitizedData);
        if ($result) {
            if (!empty($data['email']) && !empty($data['password'])) {
                try {
                    $this->memberModel->createLinkedUser($result, $data['email'], $data['password']);
                } catch (Exception $e) {
                    return $e->getMessage();
                }
            }
            return $result;
        }
        return "Failed to create member.";
    }

    /**
     * Edit an existing member.
     * Roles: Secretary, Committee Head, Administrator
     */
    public function edit($id, $data, $files = null) {
        authorizeRoles(['Secretary', 'Committee Head', 'Administrator']);
        
        if (empty($data['full_name'])) return "Name is required.";
        if (empty($id)) return "Member ID is required.";

        $currentMember = $this->memberModel->find($id);
        $photoPath = $currentMember['photo_path'];

        if ($files && isset($files['photo']) && $files['photo']['error'] === UPLOAD_ERR_OK) {
            // Delete old photo if it exists
            if ($photoPath && file_exists(BASE_PATH . '/public/' . $photoPath)) {
                unlink(BASE_PATH . '/public/' . $photoPath);
            }
            $photoPath = $this->handleUpload($files['photo']);
            if (strpos($photoPath, 'Error:') === 0) return $photoPath;
        }

        // Handle explicit photo deletion
        if (isset($data['delete_photo']) && $data['delete_photo'] == '1') {
            if ($photoPath && file_exists(BASE_PATH . '/public/' . $photoPath)) {
                unlink(BASE_PATH . '/public/' . $photoPath);
            }
            $photoPath = null;
        }

        $sanitizedData = [
            'full_name'    => htmlspecialchars(trim($data['full_name'])),
            'email'        => trim($data['email'] ?? ''),
            'phone'        => htmlspecialchars(trim($data['phone'] ?? '')),
            'address'      => htmlspecialchars(trim($data['address'] ?? '')),
            'contact_info' => htmlspecialchars(trim($data['contact_info'] ?? '')),
            'status'       => $data['status'] ?? 'active',
            'photo_path'   => $photoPath
        ];

        if ($this->memberModel->update($id, $sanitizedData)) {
            // Trigger automatic status check after update
            $this->memberModel->recalculateStatus($id);
            return true;
        }
        return "Failed to update member.";
    }

    /**
     * Handle file upload securely.
     */
    private function handleUpload($file) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            return "Error: Invalid file type. Only JPG, PNG, WEBP allowed.";
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            return "Error: File too large. Max 2MB.";
        }

        $uploadDir = BASE_PATH . '/public/assets/uploads/members/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $newName = 'member_' . uniqid() . '.' . $ext;
        $dest = $uploadDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            return 'assets/uploads/members/' . $newName;
        }
        return "Error: Failed to move uploaded file.";
    }

    /**
     * Delete a member.
     * Roles: Secretary only.
     */
    public function delete($id) {
        authorizeRoles(['Secretary', 'Administrator']);
        
        $member = $this->memberModel->find($id);
        if ($member && $member['photo_path'] && file_exists(BASE_PATH . '/public/' . $member['photo_path'])) {
            unlink(BASE_PATH . '/public/' . $member['photo_path']);
        }

        if ($this->memberModel->delete($id)) {
            return true;
        }
        return "Failed to delete member.";
    }

    /**
     * Create a user account for a member.
     */
    public function createAccount($memberId, $email, $password) {
        authorizeRoles(['Administrator', 'Secretary']);

        if (empty($email) || empty($password)) {
            return "Email and password are required.";
        }

        if ($this->memberModel->createLinkedUser($memberId, $email, $password)) {
            return true;
        }
        return "Failed to create account. Email might already be in use.";
    }


    /**
     * Force-regenerate a new QR token for a single member.
     * The old token is immediately invalidated in the database.
     * Roles: Administrator only.
     */
    public function regenerateToken($memberId) {
        authorizeRoles(['Administrator']);
        if (empty($memberId)) return "Member ID is required.";
        $token = $this->memberModel->regenerateToken($memberId);
        if ($token) return true;
        return "Failed to regenerate token.";
    }
}
