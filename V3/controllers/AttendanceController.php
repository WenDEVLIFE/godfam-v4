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
     * Roles: Secretary, Committee Head, Staff
     */
    public function viewAttendance($eventId) {
        authorizeRoles(['Secretary', 'Committee Head', 'Staff', 'Administrator']);
        return $this->attendanceModel->getByEvent($eventId);
    }

    /**
     * Record attendance.
     * Roles: Secretary, Committee Head
     */
    public function record($data) {
        authorizeRoles(['Secretary', 'Committee Head', 'Administrator', 'Staff']);
        
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
        authorizeRoles(['Secretary', 'Committee Head', 'Administrator', 'Staff']);
        if ($this->attendanceModel->recordTimeOut($attendanceId)) {
            return true;
        }
        return "Failed to record time out.";
    }

    /**
     * Record attendance via QR Token.
     * Roles: Secretary, Committee Head
     */
    public function recordByQr($data, $pdo) {
        authorizeRoles(['Secretary', 'Committee Head', 'Administrator', 'Staff']);
        
        if (empty($data['qr_token']) || empty($data['event_id']) || empty($data['date'])) {
            return "Missing required fields.";
        }

        // We need Member model to find by token
        require_once BASE_PATH . '/models/Member.php';
        $memberModel = new Member($pdo);
        $member = $memberModel->findByQrToken($data['qr_token']);

        if (!$member) {
            return "Invalid QR Code.";
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
