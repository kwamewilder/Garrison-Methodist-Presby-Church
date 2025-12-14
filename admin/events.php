<?php
$pageTitle = 'Manage Events';
require_once 'includes/header.php';
require_once 'includes/permission_helpers.php';

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
            if (!hasRole('admin')) {
                $error = 'Only admins can create events.';
            }
            $title = sanitizeInput($_POST['title']);
            $content_json = $_POST['content_json'] ?? '';
            $description = $content_json ?: sanitizeInput($_POST['description']);
            $event_date = sanitizeInput($_POST['event_date']);
            $event_time = sanitizeInput($_POST['event_time']);
            $location = sanitizeInput($_POST['location']);
            $image_url = sanitizeInput($_POST['image_url']);
            $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'published';

            if (!$error) {
                try {
                    $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, event_time, location, image_url, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $event_date, $event_time, $location, $image_url, $status, getCurrentAdmin()['id']]);
                    $newId = $conn->lastInsertId();
                    log_audit('create', 'event', $newId, "Created event {$title}");
                    $success = 'Event created successfully!';
                } catch(PDOException $e) {
                    $error = 'Error creating event: ' . $e->getMessage();
                    error_log($e->getMessage());
                }
            }
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            if (!canEditEvent($id)) {
                $error = 'No permission to edit this event.';
            }
            $title = sanitizeInput($_POST['title']);
            $content_json = $_POST['content_json'] ?? '';
            $description = $content_json ?: sanitizeInput($_POST['description']);
            $event_date = sanitizeInput($_POST['event_date']);
            $event_time = sanitizeInput($_POST['event_time']);
            $location = sanitizeInput($_POST['location']);
            $image_url = sanitizeInput($_POST['image_url']);
            $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'published';

            if (!$error) {
                try {
                    $stmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?, location = ?, image_url = ?, status = ? WHERE id = ?");
                    $stmt->execute([$title, $description, $event_date, $event_time, $location, $image_url, $status, $id]);
                    log_audit('update', 'event', $id, "Updated event {$title}");
                    $success = 'Event updated successfully!';
                } catch(PDOException $e) {
                    $error = 'Error updating event: ' . $e->getMessage();
                    error_log($e->getMessage());
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            if (!canEditEvent($id)) {
                $error = 'No permission to delete this event.';
            }
            if (!$error) {
                try {
                    $stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
                    $stmt->execute([$id]);
                    log_audit('delete', 'event', $id, 'Deleted event');
                    $success = 'Event deleted successfully!';
                } catch(PDOException $e) {
                    $error = 'Error deleting event.';
                    error_log($e->getMessage());
                }
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
                                <?php $canEdit = canEditEvent($event['id']); ?>
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
                                        <?php if ($canEdit): ?>
                                            <button onclick="editEvent(<?php echo $event['id']; ?>)" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">No access</span>
                                        <?php endif; ?>
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
                                <?php $canEdit = canEditEvent($event['id']); ?>
                                <tr style="opacity: 0.7;">
                                    <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($event['event_time'] ?: 'TBD'); ?></td>
                                    <td><?php echo htmlspecialchars($event['location'] ?: 'TBD'); ?></td>
                                    <td><span class="badge badge-secondary"><?php echo ($event["status"] === "published" ? "Published" : "Draft"); ?></span></td>
                                    <td>
        <?php if ($canEdit): ?>
                                        <button onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
        <?php else: ?>
            <span class="badge badge-secondary">No access</span>
        <?php endif; ?>
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
    <div class="modal-content" style="max-width: 900px;">
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
                <label>Event Content</label>
                <div class="block-editor">
                    <div class="block-editor-toolbar">
                        <div class="toolbar-left">
                            <span class="badge badge-soft">Drag & reorder blocks</span>
                        </div>
                        <div class="toolbar-right">
                            <select id="eventBlockTypeSelect" class="form-control" style="width: 200px;">
                                <option value="">Add block...</option>
                                <option value="heading">Heading</option>
                                <option value="paragraph">Rich Text</option>
                                <option value="list">List</option>
                                <option value="quote">Quote</option>
                                <option value="image">Image</option>
                                <option value="video">Video</option>
                                <option value="button">Button</option>
                                <option value="scripture">Scripture</option>
                                <option value="event">Event</option>
                                <option value="location">Location / Map</option>
                                <option value="divider">Divider</option>
                                <option value="spacer">Spacer</option>
                            </select>
                            <button type="button" class="btn btn-primary" id="eventAddBlockBtn"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </div>

                    <div class="block-canvas" id="eventBlockCanvas"></div>
                </div>
                <input type="hidden" name="content_json" id="event_content_json">
                <textarea name="description" id="event_description" class="form-control" rows="4" style="display:none;"></textarea>
                <small style="color: #666;">Build your event details with blocks.</small>
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

.block-editor {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 18px 45px rgba(10, 10, 51, 0.08);
    margin-bottom: 10px;
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.block-editor:hover {
    transform: translateY(-2px);
    box-shadow: 0 22px 55px rgba(10, 10, 51, 0.1);
}

.block-editor-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
    background: linear-gradient(90deg, #f8fafc, #f1f5f9);
}

.block-editor .badge-soft {
    background: #e0f2ff;
    color: #1b6bd7;
    padding: 6px 10px;
    border-radius: 8px;
    font-weight: 600;
}

.block-canvas {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.block-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    background: #fdfefe;
    padding: 12px;
    position: relative;
    display: flex;
    gap: 12px;
    box-shadow: 0 8px 20px rgba(10, 10, 51, 0.05);
    animation: blockFade 0.2s ease;
}

.block-handle {
    width: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: grab;
    color: #94a3b8;
}

.block-content {
    flex: 1;
    display: grid;
    gap: 8px;
}

.block-title {
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 6px;
}

.block-actions {
    display: flex;
    gap: 6px;
    justify-content: flex-end;
}

.block-actions button {
    border: 1px solid #e5e7eb;
    background: #fff;
    border-radius: 6px;
    padding: 6px 8px;
    cursor: pointer;
    color: #475569;
    transition: all 0.2s ease;
}

.block-actions button:hover {
    background: #f1f5f9;
    transform: translateY(-1px);
}

@keyframes blockFade {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
}

.block-tag {
    display: inline-block;
    padding: 2px 8px;
    background: #e2e8f0;
    border-radius: 999px;
    font-size: 0.85rem;
    color: #475569;
}

.block-input,
.block-textarea {
    width: 100%;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 10px;
    background: #fff;
}

.block-textarea {
    min-height: 90px;
}

.block-preview {
    border: 1px dashed #dce3ed;
    border-radius: 8px;
    padding: 10px;
    background: #fbfdff;
    color: #475569;
    font-size: 0.95rem;
}

.block-image-preview {
    max-width: 100%;
    border-radius: 8px;
    margin-top: 8px;
    display: none;
}

@media (max-width: 768px) {
    .block-card {
        flex-direction: column;
    }
    .block-handle {
        width: 100%;
        justify-content: flex-start;
    }
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
const eventBlockCanvas = document.getElementById('eventBlockCanvas');
const eventContentJsonField = document.getElementById('event_content_json');
const eventAddBlockBtn = document.getElementById('eventAddBlockBtn');
const eventBlockTypeSelect = document.getElementById('eventBlockTypeSelect');
let eventBlocks = [];
const eventDefaultBlockData = {
    heading: { level: 2, text: 'Event heading', align: 'left' },
    paragraph: { text: 'Add event details here...', align: 'left' },
    list: { style: 'unordered', items: ['Detail 1', 'Detail 2'], align: 'left' },
    quote: { text: 'Key highlight', caption: '', align: 'left' },
    image: { url: '', caption: '' },
    video: { url: '', caption: '' },
    button: { text: 'Register', url: '#', style: 'primary' },
    scripture: { reference: 'Psalm 122:1', text: 'I was glad when they said unto me...', version: 'NIV' },
    event: { title: 'Event', date: '', time: '', location: '', description: '' },
    location: { address: '123 Church St, City', mapUrl: '' },
    divider: {},
    spacer: { height: 24 },
};

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
    eventBlocks = [
        { type: 'heading', data: { level: 2, text: 'Event Title' } },
        { type: 'paragraph', data: { text: 'Add event details here...' } }
    ];
    renderEventBlocks();
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
    hydrateEventBlocks(event.description || '');
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

function hydrateEventBlocks(raw) {
    try {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
            eventBlocks = parsed;
        } else if (parsed && Array.isArray(parsed.blocks)) {
            eventBlocks = parsed.blocks;
        } else {
            eventBlocks = [];
        }
    } catch (e) {
        eventBlocks = raw ? [{ type: 'paragraph', data: { text: raw } }] : [];
    }
    renderEventBlocks();
}

function addEventBlock(type) {
    if (!type) return;
    const clone = JSON.parse(JSON.stringify(eventDefaultBlockData[type] || {}));
    eventBlocks.push({ type, data: clone });
    renderEventBlocks();
    eventBlockTypeSelect.value = '';
}

function renderEventBlocks() {
    eventBlockCanvas.innerHTML = '';
    eventBlocks.forEach((block, index) => {
        const card = document.createElement('div');
        card.className = 'block-card';
        card.draggable = true;
        card.dataset.index = index;
        card.addEventListener('dragstart', onEventDragStart);
        card.addEventListener('dragover', onEventDragOver);
        card.addEventListener('drop', onEventDrop);

        const handle = document.createElement('div');
        handle.className = 'block-handle';
        handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';

        const content = document.createElement('div');
        content.className = 'block-content';
        const title = document.createElement('div');
        title.className = 'block-title';
        title.innerHTML = `<span class="block-tag">${block.type}</span>`;
        content.appendChild(title);
        content.appendChild(renderEventBlockFields(block, index));

        const actions = document.createElement('div');
        actions.className = 'block-actions';
        actions.innerHTML = `
            <button type="button" aria-label="Move up" onclick="moveEventBlock(${index}, -1)"><i class="fas fa-arrow-up"></i></button>
            <button type="button" aria-label="Move down" onclick="moveEventBlock(${index}, 1)"><i class="fas fa-arrow-down"></i></button>
            <button type="button" aria-label="Duplicate" onclick="duplicateEventBlock(${index})"><i class="fas fa-copy"></i></button>
            <button type="button" aria-label="Delete" onclick="deleteEventBlock(${index})"><i class="fas fa-trash"></i></button>
        `;
        content.appendChild(actions);

        card.appendChild(handle);
        card.appendChild(content);
        eventBlockCanvas.appendChild(card);
    });
    syncEventJsonField();
}

function createEventAlignControl(block, index) {
    const wrap = document.createElement('div');
    wrap.className = 'block-input';
    const label = document.createElement('label');
    label.textContent = 'Alignment';
    label.style.display = 'block';
    label.style.fontSize = '12px';
    label.style.color = '#666';
    const select = document.createElement('select');
    select.className = 'block-input';
    ['left','center','right','justify'].forEach(al => {
        const opt = document.createElement('option');
        opt.value = al;
        opt.textContent = al.charAt(0).toUpperCase() + al.slice(1);
        if ((block.data.align || 'left') === al) opt.selected = true;
        select.appendChild(opt);
    });
    select.addEventListener('change', (e) => {
        eventBlocks[index].data.align = e.target.value;
        syncEventJsonField();
    });
    wrap.append(label, select);
    return wrap;
}

function renderEventBlockFields(block, index) {
    const wrapper = document.createElement('div');
    wrapper.className = 'block-fields';
    const bindInput = (input, path) => {
        input.addEventListener('input', (e) => {
            setEventNested(eventBlocks[index], path, e.target.value);
            syncEventJsonField();
        });
        return input;
    };
    switch (block.type) {
        case 'heading': {
            const level = document.createElement('select');
            level.className = 'block-input';
            [1,2,3,4,5,6].forEach(l => {
                const opt = document.createElement('option');
                opt.value = l;
                opt.textContent = `H${l}`;
                if (Number(block.data.level) === l) opt.selected = true;
                level.appendChild(opt);
            });
            bindInput(level, ['data','level']);
            const text = document.createElement('input');
            text.className = 'block-input';
            text.placeholder = 'Heading text';
            text.value = block.data.text || '';
            bindInput(text, ['data','text']);
            wrapper.append(level, text, createEventAlignControl(block, index));
            break;
        }
        case 'paragraph': {
            const area = document.createElement('textarea');
            area.className = 'block-textarea';
            area.placeholder = 'Rich text';
            area.value = block.data.text || '';
            bindInput(area, ['data','text']);
            wrapper.append(area, createEventAlignControl(block, index));
            break;
        }
        case 'list': {
            const style = document.createElement('select');
            style.className = 'block-input';
            ['unordered','ordered'].forEach(s => {
                const opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s === 'unordered' ? 'Bullets' : 'Numbers';
                if (block.data.style === s) opt.selected = true;
                style.appendChild(opt);
            });
            bindInput(style, ['data','style']);
            const area = document.createElement('textarea');
            area.className = 'block-textarea';
            area.placeholder = 'One item per line';
            area.value = (block.data.items || []).join('\\n');
            area.addEventListener('input', (e) => {
                eventBlocks[index].data.items = e.target.value.split('\\n').filter(Boolean);
                syncEventJsonField();
            });
            wrapper.append(style, area, createEventAlignControl(block, index));
            break;
        }
        case 'quote': {
            const quote = document.createElement('textarea');
            quote.className = 'block-textarea';
            quote.placeholder = 'Quote text';
            quote.value = block.data.text || '';
            bindInput(quote, ['data','text']);
            const caption = document.createElement('input');
            caption.className = 'block-input';
            caption.placeholder = 'Caption';
            caption.value = block.data.caption || '';
            bindInput(caption, ['data','caption']);
            wrapper.append(quote, caption, createEventAlignControl(block, index));
            break;
        }
        case 'image': {
            const url = document.createElement('input');
            url.className = 'block-input';
            url.placeholder = 'Image URL';
            url.value = block.data.url || '';
            bindInput(url, ['data','url']);
            const file = document.createElement('input');
            file.type = 'file';
            file.accept = 'image/*';
            file.className = 'block-input';
            file.addEventListener('change', (e) => handleEventMediaUpload(e, index));
            const caption = document.createElement('input');
            caption.className = 'block-input';
            caption.placeholder = 'Caption';
            caption.value = block.data.caption || '';
            bindInput(caption, ['data','caption']);
            const preview = document.createElement('img');
            preview.className = 'block-image-preview';
            if (block.data.url) {
                preview.src = block.data.url;
                preview.style.display = 'block';
            }
            wrapper.append(url, file, caption, preview);
            break;
        }
        case 'video': {
            const url = document.createElement('input');
            url.className = 'block-input';
            url.placeholder = 'YouTube, Vimeo, or direct video URL';
            url.value = block.data.url || '';
            bindInput(url, ['data','url']);
            const file = document.createElement('input');
            file.type = 'file';
            file.accept = 'video/*';
            file.className = 'block-input';
            file.addEventListener('change', (e) => handleEventMediaUpload(e, index));
            const caption = document.createElement('input');
            caption.className = 'block-input';
            caption.placeholder = 'Caption (optional)';
            caption.value = block.data.caption || '';
            bindInput(caption, ['data','caption']);
            const preview = document.createElement('p');
            preview.className = 'block-preview';
            preview.textContent = block.data.url ? `Linked to: ${block.data.url}` : 'Upload a video file or paste a video link.';
            wrapper.append(url, file, caption, preview);
            break;
        }
        case 'button': {
            const text = document.createElement('input');
            text.className = 'block-input';
            text.placeholder = 'Button text';
            text.value = block.data.text || '';
            bindInput(text, ['data','text']);
            const link = document.createElement('input');
            link.className = 'block-input';
            link.placeholder = 'https://example.com';
            link.value = block.data.url || '';
            bindInput(link, ['data','url']);
            const style = document.createElement('select');
            style.className = 'block-input';
            ['primary','secondary'].forEach(s => {
                const opt = document.createElement('option');
                opt.value = s;
                opt.textContent = s.charAt(0).toUpperCase() + s.slice(1);
                if (block.data.style === s) opt.selected = true;
                style.appendChild(opt);
            });
            bindInput(style, ['data','style']);
            wrapper.append(text, link, style);
            break;
        }
        case 'scripture': {
            const ref = document.createElement('input');
            ref.className = 'block-input';
            ref.placeholder = 'e.g., Psalm 23:1-6';
            ref.value = block.data.reference || '';
            bindInput(ref, ['data','reference']);
            const text = document.createElement('textarea');
            text.className = 'block-textarea';
            text.placeholder = 'Scripture text';
            text.value = block.data.text || '';
            bindInput(text, ['data','text']);
            const version = document.createElement('input');
            version.className = 'block-input';
            version.placeholder = 'Version (NIV, ESV, etc.)';
            version.value = block.data.version || '';
            bindInput(version, ['data','version']);
            wrapper.append(ref, text, version);
            break;
        }
        case 'location': {
            const addr = document.createElement('input');
            addr.className = 'block-input';
            addr.placeholder = 'Address';
            addr.value = block.data.address || '';
            bindInput(addr, ['data','address']);
            const map = document.createElement('input');
            map.className = 'block-input';
            map.placeholder = 'Google Maps embed URL (optional)';
            map.value = block.data.mapUrl || '';
            bindInput(map, ['data','mapUrl']);
            wrapper.append(addr, map);
            break;
        }
        case 'divider': {
            const note = document.createElement('p');
            note.style.fontSize = '12px';
            note.style.color = '#666';
            note.textContent = 'Adds a horizontal line break between sections.';
            wrapper.append(note);
            break;
        }
        case 'spacer': {
            const height = document.createElement('input');
            height.type = 'number';
            height.min = 8;
            height.max = 200;
            height.className = 'block-input';
            height.placeholder = 'Height in pixels';
            height.value = block.data.height || 24;
            height.addEventListener('input', (e) => {
                eventBlocks[index].data.height = Number(e.target.value || 0);
                syncEventJsonField();
            });
            const hint = document.createElement('small');
            hint.style.color = '#666';
            hint.textContent = 'Use spacers to add breathing room between blocks.';
            wrapper.append(height, hint);
            break;
        }
        case 'event': {
            const title = document.createElement('input');
            title.className = 'block-input';
            title.placeholder = 'Event title';
            title.value = block.data.title || '';
            bindInput(title, ['data','title']);
            const date = document.createElement('input');
            date.type = 'date';
            date.className = 'block-input';
            date.value = block.data.date || '';
            bindInput(date, ['data','date']);
            const time = document.createElement('input');
            time.type = 'time';
            time.className = 'block-input';
            time.value = block.data.time || '';
            bindInput(time, ['data','time']);
            const location = document.createElement('input');
            location.className = 'block-input';
            location.placeholder = 'Location';
            location.value = block.data.location || '';
            bindInput(location, ['data','location']);
            const desc = document.createElement('textarea');
            desc.className = 'block-textarea';
            desc.placeholder = 'Short description';
            desc.value = block.data.description || '';
            bindInput(desc, ['data','description']);
            wrapper.append(title, date, time, location, desc);
            break;
        }
        default:
            wrapper.textContent = 'Unknown block';
    }
    return wrapper;
}

function setEventNested(obj, path, value) {
    let target = obj;
    for (let i = 0; i < path.length - 1; i++) {
        const key = path[i];
        if (!target[key]) target[key] = {};
        target = target[key];
    }
    target[path[path.length - 1]] = value;
}

function moveEventBlock(index, delta) {
    const newIndex = index + delta;
    if (newIndex < 0 || newIndex >= eventBlocks.length) return;
    const [moved] = eventBlocks.splice(index, 1);
    eventBlocks.splice(newIndex, 0, moved);
    renderEventBlocks();
}

function deleteEventBlock(index) {
    eventBlocks.splice(index, 1);
    renderEventBlocks();
}

function duplicateEventBlock(index) {
    const clone = JSON.parse(JSON.stringify(eventBlocks[index]));
    eventBlocks.splice(index + 1, 0, clone);
    renderEventBlocks();
}

function handleEventMediaUpload(event, index) {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

    fetch('api/upload.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.file && data.file.url) {
                eventBlocks[index].data.url = data.file.url;
                renderEventBlocks();
            } else {
                alert('Upload failed');
            }
        })
        .catch(() => alert('Upload failed'));
}

function onEventDragStart(e) {
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.index);
    e.dropEffect = 'move';
}

function onEventDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function onEventDrop(e) {
    e.preventDefault();
    const from = Number(e.dataTransfer.getData('text/plain'));
    const to = Number(e.currentTarget.dataset.index);
    if (isNaN(from) || isNaN(to) || from === to) return;
    const [moved] = eventBlocks.splice(from, 1);
    eventBlocks.splice(to, 0, moved);
    renderEventBlocks();
}

function syncEventJsonField() {
    eventContentJsonField.value = JSON.stringify(eventBlocks);
}

eventAddBlockBtn.addEventListener('click', () => addEventBlock(eventBlockTypeSelect.value));

document.getElementById('eventForm').addEventListener('submit', () => {
    syncEventJsonField();
});

// Initialize
renderEventBlocks();
</script>

<?php require_once 'includes/footer.php'; ?>
