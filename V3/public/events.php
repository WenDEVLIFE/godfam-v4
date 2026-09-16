<?php
/**
 * Events View - Unified Event Management Dashboard (Calendar View).
 * Accessible by all (Role-based actions).
 */

$page_title = 'Church Events';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CSRF.php';
require_once __DIR__ . '/../controllers/EventController.php';

// Ensure user is logged in
requireLogin();

$controller = new EventController($pdo);
$error = $_SESSION['event_error'] ?? '';
$success = $_SESSION['event_success'] ?? '';
unset($_SESSION['event_error'], $_SESSION['event_success']);
$canScheduleEvents = isAdmin() || isStaff();
$canEditDeleteEvents = hasRole(['Administrator', 'Secretary']);

function redirectToEventsPage() {
    $target = 'events.php';
    if (!empty($_GET['ym']) && preg_match('/^\d{4}-\d{2}$/', $_GET['ym'])) {
        $target .= '?ym=' . rawurlencode($_GET['ym']);
    }

    header('Location: ' . $target);
    exit;
}

// Handle event actions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken($_POST['csrf_token'] ?? '');

    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $result = $controller->create($_POST);
        if ($result === true) $_SESSION['event_success'] = "Event scheduled successfully.";
        else $_SESSION['event_error'] = $result;
        redirectToEventsPage();
    } elseif ($action === 'update') {
        $result = $controller->update($_POST);
        if (is_array($result) && !empty($result['event_saved'])) {
            $_SESSION['event_success'] = $result['message'] ?? 'Event updated successfully.';
        } else {
            $_SESSION['event_error'] = (string)$result;
        }
        redirectToEventsPage();
    } elseif ($action === 'delete') {
        $eventId = $_POST['event_id'] ?? 0;
        $adminPassword = $_POST['admin_password'] ?? '';
        $result = $controller->delete($eventId, $adminPassword);
        if (is_array($result) && !empty($result['event_saved'])) {
            $_SESSION['event_success'] = $result['message'] ?? 'Event deleted successfully.';
        } else {
            $_SESSION['event_error'] = (string)$result;
        }
        redirectToEventsPage();
    } else {
        $_SESSION['event_error'] = 'Invalid event action.';
        redirectToEventsPage();
    }
}

$events = $controller->index();
$csrf_token = generateCsrfToken();

// --- Calendar Logic ---
$ym = $_GET['ym'] ?? date('Y-m');
$timestamp = strtotime($ym . '-01');
if ($timestamp === false) {
    $ym = date('Y-m');
    $timestamp = strtotime($ym . '-01');
}

$today = date('Y-m-d', time());
$html_title = date('F Y', $timestamp);

$prev = date('Y-m', mktime(0, 0, 0, date('m', $timestamp)-1, 1, date('Y', $timestamp)));
$next = date('Y-m', mktime(0, 0, 0, date('m', $timestamp)+1, 1, date('Y', $timestamp)));

$day_count = date('t', $timestamp);
$str = date('w', mktime(0, 0, 0, date('m', $timestamp), 1, date('Y', $timestamp))); // 0 for Sunday

// Organize events by date for easy calendar rendering
$events_by_date = [];
foreach ($events as $event) {
    $date = date('Y-m-d', strtotime($event['date']));
    if (!isset($events_by_date[$date])) {
        $events_by_date[$date] = [];
    }
    $events_by_date[$date][] = $event;
}

// Prepare Data for JS Modals
$events_json = json_encode($events_by_date, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$upcoming_events = $controller->upcoming(3);

// --- Past Events (History) ---
$past_events = $controller->history(10);

// Include Layout Header
include __DIR__ . '/layout/header.php';
include __DIR__ . '/layout/sidebar.php';
?>

<div class="mb-4">
    <h1 class="page-title">Event Management</h1>
    <p class="text-muted small">System Calendar and Church Activities</p>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success shadow-sm"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<!-- Calendar Section -->
<div class="calendar-container">
    <div class="card mb-4 border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
        <div class="card-header bg-transparent py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-3" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
            <div class="d-flex align-items-center gap-2">
                <a href="?ym=<?php echo $prev; ?>" class="btn btn-outline-secondary btn-sm" title="Previous Month">
                    <i class='bx bx-chevron-left'></i> Prev
                </a>
                <h3 class="calendar-header-title mb-0" style="font-weight: 800; font-size: 1.25rem; color: var(--primary-color); margin: 0 8px;"><?php echo $html_title; ?></h3>
                <a href="?ym=<?php echo $next; ?>" class="btn btn-outline-secondary btn-sm" title="Next Month">
                    Next <i class='bx bx-chevron-right'></i>
                </a>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="?ym=<?php echo date('Y-m'); ?>" class="btn btn-outline-secondary btn-sm" style="font-weight: 600; padding: 6px 14px;">
                    <i class='bx bx-calendar-event'></i> Today
                </a>
                <?php if ($canScheduleEvents): ?>
                    <button onclick="document.getElementById('addEventModal').classList.add('active')" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 16px; font-weight: 600; border-radius: 6px;">
                        <i class='bx bx-plus-circle'></i> Schedule New Event
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="calendar-grid">
                <div class="calendar-day-name">SUN</div>
                <div class="calendar-day-name">MON</div>
                <div class="calendar-day-name">TUE</div>
                <div class="calendar-day-name">WED</div>
                <div class="calendar-day-name">THU</div>
                <div class="calendar-day-name">FRI</div>
                <div class="calendar-day-name">SAT</div>
    
                <?php 
                    for ($i = 0; $i < $str; $i++) {
                        echo '<div class="calendar-day empty"></div>';
                    }
    
                    for ($day = 1; $day <= $day_count; $day++) {
                        $date = $ym . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                        $classes = ['calendar-day'];
                        if ($today === $date) $classes[] = 'today';
    
                        $has_events = isset($events_by_date[$date]) && count($events_by_date[$date]) > 0;
                        
                        echo '<div class="' . implode(' ', $classes) . '" onclick="openDayModal(\'' . $date . '\')">';
                        echo '<span class="day-number">' . $day . '</span>';
                        if ($has_events) {
                            echo '<div class="event-indicator">';
                            foreach($events_by_date[$date] as $idx => $e) {
                                if($idx < 1) {
                                    echo '<div class="event-brief">' . htmlspecialchars($e['title']) . '</div>';
                                }
                            }
                            if(count($events_by_date[$date]) > 1) {
                                echo '<div class="event-more">+' . (count($events_by_date[$date])-1) . '</div>';
                            }
                            echo '</div>';
                        }
                        echo '</div>';
                    }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Upcoming Events -->
<div class="card mb-4">
    <div class="card-header bg-light">UPCOMING EVENTS</div>
    <div class="card-body p-0">
        <?php if (empty($upcoming_events)): ?>
            <p class="p-4 text-center text-muted">No upcoming events scheduled.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Event</th>
                            <th>Location</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_events as $event): ?>
                        <tr>
                            <td class="small font-weight-700"><?php echo date('M d, Y', strtotime($event['date'])); ?></td>
                            <td class="font-weight-600"><?php echo htmlspecialchars($event['title']); ?></td>
                            <td class="small text-muted"><?php echo htmlspecialchars($event['location'] ?? 'Main Hall'); ?></td>
                            <td class="small"><?php echo $event['time'] ? date('h:i A', strtotime($event['time'])) : 'All Day'; ?></td>
                            <td>
                                <?php 
                                    $statusClass = 'badge-info';
                                    if ($event['status'] === 'complete') $statusClass = 'badge-success';
                                    if ($event['status'] === 'cancel') $statusClass = 'badge-danger';
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($event['status']); ?></span>
                            </td>
                            <td>
                                <div class="event-actions">
                                    <a href="attendance.php?event_id=<?php echo (int)$event['event_id']; ?>" class="btn btn-outline-primary btn-sm">
                                        <i class='bx bx-list-ul'></i> View Logs
                                    </a>
                                    <?php if ($canScheduleEvents): ?>
                                        <a href="scan_attendance.php?event_id=<?php echo (int)$event['event_id']; ?>" class="btn btn-outline-success btn-sm">
                                            <i class='bx bx-scan'></i> Scanner
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($canEditDeleteEvents): ?>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="openEditEventModal(<?php echo (int)$event['event_id']; ?>)">
                                            <i class='bx bx-edit'></i> Edit
                                        </button>
                                        <form method="POST" class="event-inline-form" onsubmit="event.preventDefault(); var form = this; confirmAction('Are you sure you want to delete this event? This action cannot be undone.', function() { form.submit(); }, 'Delete Event?', '📅 <?php echo htmlspecialchars(addslashes($event['title'])); ?> &bull; <?php echo date('M d, Y', strtotime($event['date'])); ?>', 'Delete Event', 'Keep Event');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="event_id" value="<?php echo (int)$event['event_id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">
                                                <i class='bx bx-trash'></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Past Events History -->
<div class="card">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <span>PAST EVENTS HISTORY</span>
        <span class="badge badge-info"><?php echo count($past_events); ?> Records</span>
    </div>
    <div class="card-body p-0">
        <?php if (empty($past_events)): ?>
            <p class="p-4 text-center text-muted">No past events recorded.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Event</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past_events as $event): ?>
                        <tr>
                            <td class="small font-weight-700"><?php echo date('M d, Y', strtotime($event['date'])); ?></td>
                            <td class="font-weight-600"><?php echo htmlspecialchars($event['title']); ?></td>
                            <td class="small text-muted"><?php echo htmlspecialchars($event['location'] ?? 'Main Hall'); ?></td>
                            <td>
                                <?php 
                                    $statusClass = 'badge-info';
                                    if ($event['status'] === 'complete') $statusClass = 'badge-success';
                                    if ($event['status'] === 'cancel') $statusClass = 'badge-danger';
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($event['status']); ?></span>
                            </td>
                            <td>
                                <div class="event-actions">
                                <a href="attendance.php?event_id=<?php echo (int)$event['event_id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class='bx bx-list-ul'></i> View Logs
                                </a>
                                <?php if ($canEditDeleteEvents): ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openEditEventModal(<?php echo (int)$event['event_id']; ?>)">
                                        <i class='bx bx-edit'></i> Edit
                                    </button>
                                    <form method="POST" class="event-inline-form" onsubmit="event.preventDefault(); var form = this; confirmAction('Are you sure you want to delete this event? This action cannot be undone.', function() { form.submit(); }, 'Delete Event?', '📅 <?php echo htmlspecialchars(addslashes($event['title'])); ?> &bull; <?php echo date('M d, Y', strtotime($event['date'])); ?>', 'Delete Event', 'Keep Event');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="event_id" value="<?php echo (int)$event['event_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class='bx bx-trash'></i> Delete
                                        </button>
                                    </form>
                                <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Day View Modal -->
<div class="modal-overlay" id="dayViewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="dayModalTitle" class="mb-0">Date</h3>
            <button class="btn btn-secondary btn-sm" onclick="document.getElementById('dayViewModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body" id="dayModalBody"></div>
    </div>
</div>

<?php if ($canScheduleEvents): ?>
<!-- Add Event Modal -->
<div class="modal-overlay" id="addEventModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="mb-0"><i class='bx bx-calendar-plus' style="color:var(--cms-red); margin-right:8px;"></i>Schedule Event</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('addEventModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="add">
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">Event Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Service title, meeting, etc." required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Time</label>
                        <input type="time" name="time" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Main Sanctuary, Fellowship Hall, etc.">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="upcoming">Upcoming</option>
                            <option value="complete">Complete</option>
                            <option value="cancel">Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('addEventModal').classList.remove('active')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class='bx bx-calendar-check'></i> Schedule Event</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canEditDeleteEvents): ?>
<!-- Edit Event Modal -->
<div class="modal-overlay" id="editEventModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="mb-0"><i class='bx bx-edit' style="color:var(--cms-blue); margin-right:8px;"></i>Edit Event</h3>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('editEventModal').classList.remove('active')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="event_id" id="edit_event_id" value="">

                <div class="form-grid">
                    <div class="form-group full-width">
                        <label class="form-label">Event Title</label>
                        <input type="text" name="title" id="edit_event_title" class="form-control" required>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_event_description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" id="edit_event_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Time</label>
                        <input type="time" name="time" id="edit_event_time" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" id="edit_event_location" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_event_status" class="form-control">
                            <option value="upcoming">Upcoming</option>
                            <option value="complete">Complete</option>
                            <option value="cancel">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="form-actions d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteFromEditModal()">
                        <i class='bx bx-trash'></i> Delete Event
                    </button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('editEventModal').classList.remove('active')">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Save Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const eventsData = <?php echo $events_json; ?>;
const canUseEventTools = <?php echo $canScheduleEvents ? 'true' : 'false'; ?>;
const canEditDeleteEvents = <?php echo $canEditDeleteEvents ? 'true' : 'false'; ?>;
const csrfToken = <?php echo json_encode($csrf_token); ?>;
const eventLookup = {};

Object.keys(eventsData).forEach(dateKey => {
    eventsData[dateKey].forEach(event => {
        eventLookup[String(event.event_id)] = event;
    });
});

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}

function openEditEventModal(eventId) {
    const event = eventLookup[String(eventId)];
    const modal = document.getElementById('editEventModal');
    if (!event || !modal) return;

    document.getElementById('edit_event_id').value = event.event_id || '';
    document.getElementById('edit_event_title').value = event.title || '';
    document.getElementById('edit_event_description').value = event.description || '';
    document.getElementById('edit_event_date').value = event.date || '';
    document.getElementById('edit_event_time').value = event.time ? event.time.substring(0, 5) : '';
    document.getElementById('edit_event_location').value = event.location || '';
    document.getElementById('edit_event_status').value = event.status || 'upcoming';
    modal.classList.add('active');
}

function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    if (modal.classList.contains('active')) {
        modal.classList.remove('active');
    } else {
        modal.classList.add('active');
    }
}

function openDayModal(dateString) {
    const titleEl = document.getElementById('dayModalTitle');
    const bodyEl = document.getElementById('dayModalBody');
    
    // Format date for title
    const dateObj = new Date(dateString + 'T00:00:00'); // Prevent timezone shift
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    titleEl.innerText = dateObj.toLocaleDateString('en-US', options);

    const dayEvents = eventsData[dateString] || [];
    
    if (dayEvents.length === 0) {
        bodyEl.innerHTML = '<p class="text-muted mb-0">No events scheduled for this day.</p>';
    } else {
        let html = '<div class="list-group list-group-flush">';
        dayEvents.forEach(event => {
            // Format time
            let timeStr = 'All Day';
            if(event.time) {
                const timeParts = event.time.split(':');
                const d = new Date();
                d.setHours(timeParts[0]);
                d.setMinutes(timeParts[1]);
                timeStr = d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }

            const eventId = encodeURIComponent(event.event_id);
            const eventTitle = escapeHtml(event.title);
            const eventLocation = escapeHtml(event.location || 'Church Campus');
            const eventDescription = event.description ? `<p class="small mb-3">${escapeHtml(event.description)}</p>` : '';

            html += `<div class="list-group-item px-0 py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="mb-0" style="font-weight: 600;">${eventTitle}</h5>
                            <span class="badge" style="background: var(--light-bg); color: var(--dark-text);">${escapeHtml(timeStr)}</span>
                        </div>
                        <p class="text-muted small mb-2"><i class='bx bx-map text-danger'></i> ${eventLocation}</p>
                        ${eventDescription}
                        `;
             
            if (canUseEventTools) {
                html += `<div class="d-flex gap-2" style="margin-top:10px;">
                            <a href="attendance.php?event_id=${eventId}" class="btn btn-sm btn-outline-primary" style="margin-right: 5px;">
                                <i class='bx bx-list-check'></i> Logs
                            </a>
                            <a href="scan_attendance.php?event_id=${eventId}" class="btn btn-sm btn-outline-success" style="margin-right: 5px;">
                                <i class='bx bx-scan'></i> Scanner
                            </a>`;
                if (canEditDeleteEvents) {
                    html += `<button type="button" class="btn btn-sm btn-outline-primary" onclick="openEditEventModal(${Number(event.event_id)})">
                                <i class='bx bx-edit'></i> Edit
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="openDeleteEventPasswordModal('${Number(event.event_id)}', '${escapeHtml(event.title)}')">
                                <i class='bx bx-trash'></i> Delete
                            </button>`;
                }
                html += `</div>`;
            }
            html += `</div>`;
        });
        html += '</div>';
        bodyEl.innerHTML = html;
    }

    toggleModal('dayViewModal');
}

function confirmDeleteFromEditModal() {
    const eventId = document.getElementById('edit_event_id').value;
    const title = document.getElementById('edit_event_title').value;
    if (!eventId) return;

    toggleModal('editEventModal');
    openDeleteEventPasswordModal(eventId, title);
}

function openDeleteEventPasswordModal(eventId, title) {
    const modal = document.getElementById('deleteEventPasswordModal');
    if (!modal) return;
    document.getElementById('delete_event_id_input').value = eventId;
    document.getElementById('delete_admin_password').value = '';
    const summaryEl = document.getElementById('delete_event_summary_badge');
    if (summaryEl) {
        summaryEl.innerHTML = `<strong>Event:</strong> ${escapeHtml(title)}`;
    }
    modal.classList.add('active');
    setTimeout(() => {
        const pwdInput = document.getElementById('delete_admin_password');
        if (pwdInput) pwdInput.focus();
    }, 100);
}

// Close modals when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.classList.remove('active');
    }
}
</script>

<!-- Admin Password Verification Modal for Event Deletion -->
<div class="modal-overlay" id="deleteEventPasswordModal" role="dialog" aria-modal="true" style="z-index: 9999 !important; backdrop-filter: blur(8px);">
    <div class="modal-content" style="max-width: 440px !important; margin: auto !important; border-radius: 16px !important; text-align: center; padding: 2.25rem;">
        <div style="width: 64px; height: 64px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 1.25rem auto;">
            <i class='bx bx-shield-quarter'></i>
        </div>
        <h4 style="font-weight: 800; font-size: 1.3rem; color: #1e293b; margin-bottom: 0.5rem; margin-top:0;">Admin Password Required</h4>
        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 1.25rem; line-height: 1.5;">High Security Action: Re-enter your administrator password to permanently delete this event.</p>
        
        <div id="delete_event_summary_badge" style="background: rgba(239, 68, 68, 0.06); border-left: 4px solid #ef4444; padding: 10px 14px; border-radius: 6px; text-align: left; margin-bottom: 1.25rem; font-size: 0.88rem; font-weight: 600; color: #1e293b;"></div>

        <form method="POST" id="deleteEventConfirmForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="event_id" id="delete_event_id_input">
            
            <div class="form-group mb-4" style="text-align: left;">
                <label class="form-label font-weight-600 small text-muted">Current Admin Password</label>
                <input type="password" name="admin_password" id="delete_admin_password" class="form-control" placeholder="••••••••" required>
            </div>

            <div class="d-flex justify-content-center gap-3">
                <button type="button" class="btn btn-outline-secondary px-4 py-2" onclick="document.getElementById('deleteEventPasswordModal').classList.remove('active')" style="font-weight: 600; border-radius: 8px;">Cancel</button>
                <button type="submit" class="btn btn-danger px-4 py-2 shadow-sm" style="font-weight: 600; border-radius: 8px;">
                    <i class='bx bx-trash'></i> Confirm Delete
                </button>
            </div>
        </form>
    </div>
</div>

<?php 
// Include Layout Footer
include __DIR__ . '/layout/footer.php';
?>
