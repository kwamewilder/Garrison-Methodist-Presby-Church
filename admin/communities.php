<?php
$pageTitle = 'Manage Communities';
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
            $name = sanitizeInput($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $name)));
            $description = sanitizeInput($_POST['description']);
            $full_content = $_POST['full_content'];
            $meeting_day = sanitizeInput($_POST['meeting_day']);
            $meeting_time = sanitizeInput($_POST['meeting_time']);
            $meeting_location = sanitizeInput($_POST['meeting_location']);
            $contact_person = sanitizeInput($_POST['contact_person']);
            $contact_email = sanitizeInput($_POST['contact_email']);

            try {
                $stmt = $conn->prepare("INSERT INTO communities (name, slug, description, full_content, meeting_day, meeting_time, meeting_location, contact_person, contact_email, status, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', (SELECT COALESCE(MAX(display_order), 0) + 1 FROM communities c))");
                $stmt->execute([$name, $slug, $description, $full_content, $meeting_day, $meeting_time, $meeting_location, $contact_person, $contact_email]);
                $success = 'Community created successfully!';
            } catch(PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'A community with this name already exists.';
                } else {
                    $error = 'Error creating community.';
                }
                error_log($e->getMessage());
            }
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $name = sanitizeInput($_POST['name']);
            $description = sanitizeInput($_POST['description']);
            $full_content = $_POST['full_content'];
            $meeting_day = sanitizeInput($_POST['meeting_day']);
            $meeting_time = sanitizeInput($_POST['meeting_time']);
            $meeting_location = sanitizeInput($_POST['meeting_location']);
            $contact_person = sanitizeInput($_POST['contact_person']);
            $contact_email = sanitizeInput($_POST['contact_email']);

            try {
                $stmt = $conn->prepare("UPDATE communities SET name = ?, description = ?, full_content = ?, meeting_day = ?, meeting_time = ?, meeting_location = ?, contact_person = ?, contact_email = ? WHERE id = ?");
                $stmt->execute([$name, $description, $full_content, $meeting_day, $meeting_time, $meeting_location, $contact_person, $contact_email, $id]);
                $success = 'Community updated successfully!';
            } catch(PDOException $e) {
                $error = 'Error updating community.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'archive') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("UPDATE communities SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Community archived successfully!';
            } catch(PDOException $e) {
                $error = 'Error archiving community.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'restore') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("UPDATE communities SET status = 'active' WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Community restored successfully!';
            } catch(PDOException $e) {
                $error = 'Error restoring community.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("DELETE FROM communities WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Community deleted permanently!';
            } catch(PDOException $e) {
                $error = 'Error deleting community.';
                error_log($e->getMessage());
            }
        }
    }
}

// Get active communities
try {
    $stmt = $conn->query("SELECT * FROM communities WHERE status = 'active' ORDER BY display_order ASC");
    $activeCommunities = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Error loading communities.';
    error_log($e->getMessage());
}

// Get archived communities
try {
    $stmt = $conn->query("SELECT * FROM communities WHERE status = 'inactive' ORDER BY updated_at DESC");
    $archivedCommunities = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log($e->getMessage());
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
        <h2><i class="fas fa-users"></i> Manage Communities</h2>
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Community
        </button>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 20px; color: #666;">
            <i class="fas fa-info-circle"></i> Manage your church communities, their meeting schedules, and contact information.
        </p>

        <!-- Tabs -->
        <div class="community-tabs" style="margin-bottom: 30px; display: flex; gap: 10px; border-bottom: 2px solid #e0e0e0;">
            <button class="comm-tab-btn active" onclick="showCommTab('active')">Active Communities (<?php echo count($activeCommunities); ?>)</button>
            <button class="comm-tab-btn" onclick="showCommTab('archived')">Archived (<?php echo count($archivedCommunities); ?>)</button>
        </div>

        <!-- Active Communities -->
        <div id="tab-active-communities" class="comm-tab-content active">
            <?php if (count($activeCommunities) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Meeting Day</th>
                                <th>Meeting Time</th>
                                <th>Location</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeCommunities as $community): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($community['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($community['meeting_day'] ?: 'TBD'); ?></td>
                                    <td><?php echo htmlspecialchars($community['meeting_time'] ?: 'TBD'); ?></td>
                                    <td><?php echo htmlspecialchars($community['meeting_location'] ?: 'TBD'); ?></td>
                                    <td><?php echo htmlspecialchars($community['contact_person'] ?: '-'); ?></td>
                                    <td>
                                        <button onclick="editCommunity(<?php echo $community['id']; ?>)" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="archiveCommunity(<?php echo $community['id']; ?>, '<?php echo htmlspecialchars($community['name']); ?>')" class="btn btn-sm btn-warning">
                                            <i class="fas fa-archive"></i> Archive
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No active communities yet. Click "Create New Community" to add one.</p>
            <?php endif; ?>
        </div>

        <!-- Archived Communities -->
        <div id="tab-archived-communities" class="comm-tab-content">
            <?php if (count($archivedCommunities) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Archived On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archivedCommunities as $community): ?>
                                <tr style="opacity: 0.7;">
                                    <td><strong><?php echo htmlspecialchars($community['name']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($community['updated_at'])); ?></td>
                                    <td>
                                        <button onclick="restoreCommunity(<?php echo $community['id']; ?>, '<?php echo htmlspecialchars($community['name']); ?>')" class="btn btn-sm btn-success">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                        <button onclick="deleteCommunity(<?php echo $community['id']; ?>, '<?php echo htmlspecialchars($community['name']); ?>')" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete Permanently
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No archived communities.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="communityModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 800px;">
        <div class="modal-header">
            <h2 id="modalTitle">Create New Community</h2>
            <button onclick="closeModal()" class="modal-close">&times;</button>
        </div>

        <form method="POST" action="" id="communityForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="community_id">

            <div class="form-group">
                <label>Community Name *</label>
                <input type="text" name="name" id="community_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Short Description *</label>
                <textarea name="description" id="community_description" class="form-control" rows="2" required></textarea>
                <small style="color: #666;">Brief overview shown on the community card</small>
            </div>

            <div class="form-group">
                <label>Full Content</label>
                <textarea name="full_content" id="community_full_content" class="form-control" rows="6"></textarea>
                <small style="color: #666;">Detailed information about the community (supports HTML)</small>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Meeting Day</label>
                    <input type="text" name="meeting_day" id="community_meeting_day" class="form-control" placeholder="e.g., Every Sunday">
                </div>

                <div class="form-group">
                    <label>Meeting Time</label>
                    <input type="text" name="meeting_time" id="community_meeting_time" class="form-control" placeholder="e.g., 6:00 PM - 8:00 PM">
                </div>
            </div>

            <div class="form-group">
                <label>Meeting Location</label>
                <input type="text" name="meeting_location" id="community_meeting_location" class="form-control" placeholder="e.g., Fellowship Hall">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Contact Person</label>
                    <input type="text" name="contact_person" id="community_contact_person" class="form-control" placeholder="e.g., John Doe">
                </div>

                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" name="contact_email" id="community_contact_email" class="form-control" placeholder="e.g., contact@example.com">
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitBtnText">Create Community</span>
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

.comm-tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    transition: all 0.3s;
}

.comm-tab-btn:hover {
    color: #5DADEC;
}

.comm-tab-btn.active {
    color: #5DADEC;
    border-bottom-color: #5DADEC;
}

.comm-tab-content {
    display: none;
}

.comm-tab-content.active {
    display: block;
}
</style>

<script>
const activeCommunities = <?php echo json_encode($activeCommunities); ?>;
const archivedCommunities = <?php echo json_encode($archivedCommunities); ?>;

function showCommTab(tab) {
    document.querySelectorAll('.comm-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.comm-tab-content').forEach(content => content.classList.remove('active'));

    event.target.classList.add('active');
    document.getElementById('tab-' + tab + '-communities').classList.add('active');
}

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create New Community';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitBtnText').textContent = 'Create Community';
    document.getElementById('communityForm').reset();
    document.getElementById('communityModal').style.display = 'block';
}

function editCommunity(id) {
    const community = [...activeCommunities, ...archivedCommunities].find(c => c.id == id);
    if (!community) return;

    document.getElementById('modalTitle').textContent = 'Edit Community';
    document.getElementById('formAction').value = 'update';
    document.getElementById('submitBtnText').textContent = 'Save Changes';
    document.getElementById('community_id').value = community.id;
    document.getElementById('community_name').value = community.name;
    document.getElementById('community_description').value = community.description;
    document.getElementById('community_full_content').value = community.full_content || '';
    document.getElementById('community_meeting_day').value = community.meeting_day || '';
    document.getElementById('community_meeting_time').value = community.meeting_time || '';
    document.getElementById('community_meeting_location').value = community.meeting_location || '';
    document.getElementById('community_contact_person').value = community.contact_person || '';
    document.getElementById('community_contact_email').value = community.contact_email || '';

    document.getElementById('communityModal').style.display = 'block';
}

function archiveCommunity(id, name) {
    if (!confirm(`Archive "${name}"? You can restore it later from the Archived tab.`)) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="archive">
        <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function restoreCommunity(id, name) {
    if (!confirm(`Restore "${name}" to active communities?`)) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="restore">
        <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteCommunity(id, name) {
    if (!confirm(`PERMANENTLY DELETE "${name}"? This action cannot be undone!`)) {
        return;
    }

    if (!confirm(`Are you absolutely sure? This will permanently remove all data for "${name}".`)) {
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
    document.getElementById('communityModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('communityModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
