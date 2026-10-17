<?php
/**
 * Attendance Controller
 */

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/Attendance.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/middleware/CSRF.php';

class AttendanceController {
    private $attendanceModel;

    public function __construct($pdo) {
        $this->attendanceModel = new Attendance($pdo);
    }

    /**
     * View attendance for an event.
     * Roles: Administrator, Secretary
     */
    public function viewAttendance($eventId) {
        authorizeRoles(['Administrator', 'Secretary']);
        return $this->attendanceModel->getByEvent($eventId);
    }

    /**
     * Record attendance.
     * Roles: Administrator, Secretary
     */
    public function record($data) {
        authorizeRoles(['Administrator', 'Secretary']);
        
        if (empty($data['member_id']) || empty($data['event_id']) || empty($data['date'])) {
            return "Missing required fields.";
        }

        if ($this->attendanceModel->record($data)) {
            // Trigger auto-status update
            require_once BASE_PATH . '/models/Member.php';
            $memberModel = new Member($this->attendanceModel->getPDO());
            $memberModel->recalculateStatus($data['member_id']);
            return true;
        }
        return "Failed to record attendance (duplicate or error).";
    }

    /**
     * Record time out for attendance manually.
     */
    public function recordTimeOutManual($attendanceId) {
        authorizeRoles(['Administrator', 'Secretary']);
        if ($this->attendanceModel->recordTimeOut($attendanceId)) {
            return true;
        }
        return "Failed to record time out.";
    }

    /**
     * Record attendance via QR Token.
     * Roles: Administrator, Secretary
     */
    public function recordByQr($data, $pdo) {
        authorizeRoles(['Administrator', 'Secretary']);
        
        $tokenInput = trim($data['qr_token'] ?? '');
        $memberIdInput = trim($data['member_id'] ?? '');

        if (empty($tokenInput) && empty($memberIdInput)) {
            return "Please scan a QR pass or select a member from the list.";
        }
        if (empty($data['event_id']) || empty($data['date'])) {
            return "Missing required event information.";
        }

        // We need Member model to find by token or member_id
        require_once BASE_PATH . '/models/Member.php';
        $memberModel = new Member($pdo);
        $member = null;

        // 1. Direct member_id passed from auto-suggest search
        if (!empty($memberIdInput)) {
            $member = $memberModel->find($memberIdInput);
        }

        // 2. Lookup by QR token
        if (!$member && !empty($tokenInput)) {
            $member = $memberModel->findByQrToken($tokenInput);

            // 3. Fallback: If typed code or numeric ID (e.g., ID #0001, 0001, 1)
            if (!$member) {
                $cleanedId = preg_replace('/[^0-9]/', '', $tokenInput);
                if (!empty($cleanedId)) {
                    $member = $memberModel->find((int)$cleanedId);
                }
            }

            // 4. Fallback: Search by full name (exact or substring)
            if (!$member) {
                $stmtName = $pdo->prepare("SELECT * FROM members WHERE LOWER(full_name) = LOWER(?) LIMIT 1");
                $stmtName->execute([$tokenInput]);
                $member = $stmtName->fetch();

                if (!$member) {
                    $stmtLike = $pdo->prepare("SELECT * FROM members WHERE LOWER(full_name) LIKE LOWER(?) ORDER BY full_name ASC LIMIT 1");
                    $stmtLike->execute(['%' . $tokenInput . '%']);
                    $member = $stmtLike->fetch();
                }
            }
        }

        if (!$member) {
            return "Member not found. Please verify the name or QR code.";
        }

        $mode = $data['mode'] ?? 'time_in';

        if ($mode === 'time_out') {
            $record = $this->attendanceModel->getRecord($member['member_id'], $data['event_id'], $data['date']);
            if (!$record) {
                return "Member hasn't timed in yet.";
            }
            if (!empty($record['time_out'])) {
                return "Time out already recorded for " . htmlspecialchars($member['full_name']) . ".";
            }
            if ($this->attendanceModel->recordTimeOut($record['attendance_id'])) {
                return true;
            }
            return "Failed to record time out.";
        } else {
            $recordData = [
                'member_id' => $member['member_id'],
                'event_id' => $data['event_id'],
                'date' => $data['date'],
                'status' => 'Present'
            ];

            if ($this->attendanceModel->checkExists($member['member_id'], $data['event_id'], $data['date'])) {
                 return "Attendance already recorded for " . htmlspecialchars($member['full_name']) . ".";
            }

            if ($this->attendanceModel->record($recordData)) {
                $memberModel->recalculateStatus($member['member_id']);
                return true;
            }
            return "Failed to record attendance.";
        }
    }
}
