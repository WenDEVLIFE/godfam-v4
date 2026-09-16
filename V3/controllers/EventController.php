<?php
/**
 * Event Controller
 */

require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/Event.php';
require_once BASE_PATH . '/middleware/AuthMiddleware.php';
require_once BASE_PATH . '/middleware/CSRF.php';

class EventController {
    private $eventModel;

    public function __construct($pdo) {
        $this->eventModel = new Event($pdo);
    }

    /**
     * List all events.
     * Roles: Secretary, Committee Head, Staff
     */
    public function index() {
        authorizeRoles(['Secretary', 'Committee Head', 'Staff', 'Administrator', 'Member']);
        return $this->eventModel->all();
    }

    /**
     * Get past events for history view.
     */
    public function history($limit = 10) {
        authorizeRoles(['Secretary', 'Committee Head', 'Staff', 'Administrator', 'Member']);
        return $this->eventModel->getPast($limit);
    }

    /**
     * Get upcoming events for dashboard/list widgets.
     */
    public function upcoming($limit = null) {
        authorizeRoles(['Secretary', 'Committee Head', 'Staff', 'Administrator', 'Member']);
        return $this->eventModel->getUpcoming($limit);
    }

    /**
     * Create an event.
     * Roles: Secretary, Committee Head, Administrator
     */
    public function create($data) {
        authorizeRoles(['Secretary', 'Committee Head', 'Administrator']);
        
        $eventData = $this->normalizeEventData($data);
        if (is_string($eventData)) {
            return $eventData;
        }

        if ($this->eventModel->create($eventData)) {
            return true;
        }
        return "This event is already scheduled for the same date, time, and location.";
    }

    /**
     * Update an event.
     * Roles: Administrator, Secretary
     */
    public function update($data) {
        authorizeRoles(['Administrator', 'Secretary']);

        $id = isset($data['event_id']) ? (int)$data['event_id'] : 0;
        if ($id <= 0) {
            return 'Invalid event.';
        }

        if (!$this->eventModel->find($id)) {
            return 'Event not found.';
        }

        $eventData = $this->normalizeEventData($data);
        if (is_string($eventData)) {
            return $eventData;
        }

        if ($this->eventModel->update($id, $eventData)) {
            return [
                'event_saved' => true,
                'message' => 'Event updated successfully.',
            ];
        }

        return 'Another event already uses the same title, date, time, and location.';
    }

    /**
     * Delete an event.
     * Roles: Administrator, Secretary.
     * Requires password re-verification.
     */
    public function delete($id, $adminPassword = '') {
        authorizeRoles(['Administrator', 'Secretary']);

        $id = (int)$id;
        if ($id <= 0) {
            return 'Invalid event ID.';
        }

        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return 'Authentication required.';
        }

        // Verify Administrator / User Password
        $stmt = $this->eventModel->getPDO()->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || empty($adminPassword) || !password_verify($adminPassword, $user['password'])) {
            return 'Password verification failed. Incorrect password.';
        }

        if (!$this->eventModel->find($id)) {
            return 'Event not found.';
        }

        $deleteResult = $this->eventModel->deleteIfNoAttendance($id);
        if (!empty($deleteResult['not_found'])) {
            return 'Event not found.';
        }

        $attendanceCount = (int)($deleteResult['attendance_count'] ?? 0);
        if ($attendanceCount > 0) {
            return 'This event cannot be deleted because it has ' . $attendanceCount . ' linked attendance record' . ($attendanceCount === 1 ? '' : 's') . '. Keep the event to preserve attendance history.';
        }

        if (!empty($deleteResult['deleted'])) {
            return [
                'event_saved' => true,
                'message' => 'Event deleted successfully.',
            ];
        }

        return 'Failed to delete event.';
    }

    private function normalizeEventData($data) {
        $title = trim((string)($data['title'] ?? ''));
        $date = trim((string)($data['date'] ?? ''));
        $time = trim((string)($data['time'] ?? ''));
        $description = trim((string)($data['description'] ?? ''));
        $location = trim((string)($data['location'] ?? ''));

        if ($title === '' || $date === '') {
            return 'Title and date are required.';
        }

        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
            return 'Please enter a valid event date.';
        }

        if ($time !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
            return 'Please enter a valid event time.';
        }

        $status = trim((string)($data['status'] ?? 'upcoming'));
        if (!in_array($status, ['upcoming', 'complete', 'cancel'])) {
            $status = 'upcoming';
        }

        return [
            'title' => $title,
            'description' => $description === '' ? null : $description,
            'date' => $date,
            'time' => $time === '' ? null : $time,
            'location' => $location === '' ? null : $location,
            'status' => $status
        ];
    }

}
