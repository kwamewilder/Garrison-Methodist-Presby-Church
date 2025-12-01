<?php
$pageTitle = 'Manage Events';
require_once 'includes/header.php';

$conn = getDBConnection();
$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $title = sanitizeInput($_POST['title']);
            $description = sanitizeInput($_POST['description']);
            $event_date = sanitizeInput($_POST['event_date']);
            $event_time = sanitizeInput($_POST['event_time']);
            $location = sanitizeInput($_POST['location']);
            $image_url = sanitizeInput($_POST['image_url']);
            $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'published';

            try {
                $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, event_time, location, image_url, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $event_date, $event_time, $location, $image_url, $status, getCurrentAdmin()['id']]);
                $success = 'Event created successfully!';
            } catch(PDOException $e) {
                $error = 'Error creating event: ' . $e->getMessage();
                error_log($e->getMessage());
            }
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $title = sanitizeInput($_POST['title']);
            $description = sanitizeInput($_POST['description']);
            $event_date = sanitizeInput($_POST['event_date']);
            $event_time = sanitizeInput($_POST['event_time']);
            $location = sanitizeInput($_POST['location']);
            $image_url = sanitizeInput($_POST['image_url']);
            $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'published';

            try {
                $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?, location = ?, image_url = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $description, $event_date, $event_time, $location, $image_url, $status, $id]);
                $success = 'Event updated successfully!';
            } catch(PDOException $e) {
                $error = 'Error updating event: ' . $e->getMessage();
                error_log($e->getMessage());
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Event deleted successfully!';
            } catch(PDOException $e) {
                $error = 'Error deleting event.';
                error_log($e->getMessage());
            }
        }
    }
}

// Get all events
try {
    $stmt = $conn->query("SELECT * FROM events ORDER BY event_date DESC, created_at DESC");
    $events = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Error loading events.';
    error_log($e->getMessage());
}

// Separate upcoming and past events
$upcomingEvents = [];
$pastEvents = [];
$today = date('Y-m-d');

foreach ($events as $event) {
    if ($event['event_date'] >= $today) {
        $upcomingEvents[] = $event;
    } else {
        $pastEvents[] = $event;
    }
}
?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-calendar-alt"></i> Manage Events</h2>
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Event
        </button>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 20px; color: #666;">
            <i class="fas fa-info-circle"></i> Manage church events, meetings, and special services.
        </p>

        <!-- Tabs -->
        <div class="event-tabs" style="margin-bottom: 30px; display: flex; gap: 10px; border-bottom: 2px solid #e0e0e0;">
            <button class="event-tab-btn active" onclick="showEventTab('upcoming')">Upcoming Events (<?php echo count($upcomingEvents); ?>)</button>
            <button class="event-tab-btn" onclick="showEventTab('past')">Past Events (<?php echo count($pastEvents); ?>)</button>
        </div>

        <!-- Upcoming Events -->
        <div id="tab-upcoming-events" class="event-tab-content active">
            <?php if (count($upcomingEvents) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingEvents as $event): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['event_time'] ?: 'TBD'); ?></td>
                                    <td><?php echo htmlspecialchars($event['location'] ?: 'TBD'); ?></td>
                                    <td>
                                        <?php if ($event['status'] === 'published'): ?>
                                            <span class="badge badge-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button onclick="editEvent(<?php echo $event['id']; ?>)" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No upcoming events. Click "Create New Event" to add one.</p>
            <?php endif; ?>
        </div>

        <!-- Past Events -->
        <div id="tab-past-events" class="event-tab-content">
            <?php if (count($pastEvents) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pastEvents as $event): ?>
                                <tr style="opacity: 0.7;">
                                    <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['event_time'] ?: 'TBD'); ?></td>
                                    <td><?php echo htmlspecialchars($event['location'] ?: 'TBD'); ?></td>
                                    <td><span class="badge badge-secondary"><?php echo ($event["status"] === "published" ? "Published" : "Draft"); ?></span></td>
                                    <td>
                                        <button onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No past events.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="eventModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 id="modalTitle">Create New Event</h2>
            <button onclick="closeModal()" class="modal-close">&times;</button>
        </div>

        <form method="POST" action="" id="eventForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="event_id">

            <div class="form-group">
                <label>Event Title *</label>
                <input type="text" name="title" id="event_title" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="event_description" class="form-control" rows="4"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Event Date *</label>
                    <input type="date" name="event_date" id="event_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Event Time</label>
                    <input type="time" name="event_time" id="event_time" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" id="event_location" class="form-control" placeholder="e.g., Main Sanctuary">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Event Image URL</label>
                    <input type="url" name="image_url" id="event_image_url" class="form-control" placeholder="https://example.com/image.jpg">
                    <small style="color: #666;">Optional: Link to an event poster or image</small>
                </div>

                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="event_status" class="form-control" required>
                        <option value="published">Published (visible on website)</option>
                        <option value="draft">Draft (not visible)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitBtnText">Create Event</span>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    padding: 20px;
    overflow-y: auto;
}

.modal-content {
    background: white;
    border-radius: 12px;
    padding: 0;
    margin: 50px auto;
}

.modal-header {
    padding: 20px 30px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: #999;
}

.modal-close:hover {
    color: #333;
}

.modal-content form {
    padding: 30px;
}

.event-tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    transition: all 0.3s;
}

.event-tab-btn:hover {
    color: #5DADEC;
}

.event-tab-btn.active {
    color: #5DADEC;
    border-bottom-color: #5DADEC;
}

.event-tab-content {
    display: none;
}

.event-tab-content.active {
    display: block;
}
</style>

<script>
const allEvents = <?php echo json_encode($events); ?>;

function showEventTab(tab) {
    document.querySelectorAll('.event-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.event-tab-content').forEach(content => content.classList.remove('active'));

    event.target.classList.add('active');
    document.getElementById('tab-' + tab + '-events').classList.add('active');
}

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create New Event';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitBtnText').textContent = 'Create Event';
    document.getElementById('eventForm').reset();
    document.getElementById('eventModal').style.display = 'block';
}

function editEvent(id) {
    const event = allEvents.find(e => e.id == id);
    if (!event) return;

    document.getElementById('modalTitle').textContent = 'Edit Event';
    document.getElementById('formAction').value = 'update';
    document.getElementById('submitBtnText').textContent = 'Save Changes';
    document.getElementById('event_id').value = event.id;
    document.getElementById('event_title').value = event.title;
    document.getElementById('event_description').value = event.description || '';
    document.getElementById('event_date').value = event.event_date;
    document.getElementById('event_time').value = event.event_time || '';
    document.getElementById('event_location').value = event.location || '';
    document.getElementById('event_image_url').value = event.image_url || '';
    document.getElementById('event_status').value = event.status || 'published';

    document.getElementById('eventModal').style.display = 'block';
}

function deleteEvent(id, title) {
    if (!confirm(`Delete event "${title}"? This action cannot be undone.`)) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function closeModal() {
    document.getElementById('eventModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('eventModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
