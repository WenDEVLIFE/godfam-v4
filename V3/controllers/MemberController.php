<?php
/**
 * Member Controller
 */

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/Member.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/middleware/CSRF.php';

require_once BASE_PATH . '/utils/FileUpload.php';

class MemberController {
    private $memberModel;

    /** Single source of truth for member categories. */
    private $memberTypes = ['Member', 'Youth Member', 'Child', 'Senior'];

    public function __construct($pdo) {
        $this->memberModel = new Member($pdo);
    }

    /**
     * Validate a member_type against the allowed list, falling back to 'Member'.
     */
    private function sanitizeMemberType($value) {
        $value = trim((string)$value);
        return in_array($value, $this->memberTypes, true) ? $value : 'Member';
    }

    /**
     * Display a list of members.
     * Roles: Administrator, Secretary
     */
    public function index() {
        authorizeRoles(['Administrator', 'Secretary']);
        return $this->memberModel->all();
    }

    /**
     * Add a new member.
     * Roles: Administrator, Secretary
     */
    public function add($data, $files = null) {
        authorizeRoles(['Administrator', 'Secretary']);
        
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
            'full_name'           => htmlspecialchars(trim($data['full_name'])),
            'member_type'         => $this->sanitizeMemberType($data['member_type'] ?? ''),
            'email'               => trim($data['email']),
            'phone'               => $this->formatPhone($data['phone'] ?? ''),
            'address'             => htmlspecialchars(trim($data['address'] ?? '')),
            'contact_info'        => htmlspecialchars(trim($data['contact_info'] ?? '')),
            'birthday'            => !empty($data['birthday']) ? trim($data['birthday']) : null,
            'wedding_anniversary' => !empty($data['wedding_anniversary']) ? trim($data['wedding_anniversary']) : null,
            'status'              => $data['status'] ?? 'active',
            'photo_path'          => $photoPath
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
     * Roles: Administrator, Secretary
     */
    public function edit($id, $data, $files = null) {
        authorizeRoles(['Administrator', 'Secretary']);
        
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
            'full_name'           => htmlspecialchars(trim($data['full_name'])),
            'member_type'         => $this->sanitizeMemberType($data['member_type'] ?? ''),
            'email'               => trim($data['email'] ?? ''),
            'phone'               => $this->formatPhone($data['phone'] ?? ''),
            'address'             => htmlspecialchars(trim($data['address'] ?? '')),
            'contact_info'        => htmlspecialchars(trim($data['contact_info'] ?? '')),
            'birthday'            => !empty($data['birthday']) ? trim($data['birthday']) : null,
            'wedding_anniversary' => !empty($data['wedding_anniversary']) ? trim($data['wedding_anniversary']) : null,
            'status'              => $data['status'] ?? 'active',
            'photo_path'          => $photoPath
        ];

        if ($this->memberModel->update($id, $sanitizedData)) {
            // Trigger automatic status check after update
            $this->memberModel->recalculateStatus($id);
            return true;
        }
        return "Failed to update member.";
    }

    /**
     * Format Philippine phone numbers into standard +639XXXXXXXXX format.
     */
    private function formatPhone($phone) {
        $phone = trim($phone);
        if (empty($phone)) return '';
        
        $digits = preg_replace('/[^\d]/', '', $phone);
        if (empty($digits)) return '';

        if (strpos($digits, '09') === 0) {
            return '+63' . substr($digits, 1);
        } elseif (strpos($digits, '9') === 0 && strlen($digits) === 10) {
            return '+63' . $digits;
        } elseif (strpos($digits, '639') === 0) {
            return '+' . $digits;
        }
        return '+' . $digits;
    }

    /**
     * Handle file upload securely using FileUpload utility.
     */
    private function handleUpload($file) {
        $result = FileUpload::processImage($file, 'members', 5);
        if ($result['success']) {
            return $result['path'];
        }
        return 'Error: ' . $result['error'];
    }

    /**
     * Delete a member.
     * Roles: Secretary only.
     */
    public function delete($id) {
        authorizeRoles(['Administrator', 'Secretary']);
        
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
