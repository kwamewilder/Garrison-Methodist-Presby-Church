<?php
$pageTitle = 'Manage Sermons';
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
            $preacher = sanitizeInput($_POST['preacher']);
            $sermon_date = sanitizeInput($_POST['sermon_date']);
            $scripture_reference = sanitizeInput($_POST['scripture_reference']);
            $description = sanitizeInput($_POST['description']);
            $audio_url = sanitizeInput($_POST['audio_url']);
            $video_url = sanitizeInput($_POST['video_url']);
            $series = sanitizeInput($_POST['series']);
            $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'published';

            try {
                $stmt = $conn->prepare("INSERT INTO sermons (title, preacher, sermon_date, scripture_reference, description, audio_url, video_url, series, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $preacher, $sermon_date, $scripture_reference, $description, $audio_url, $video_url, $series, $status, getCurrentAdmin()['id']]);
                $success = 'Sermon created successfully!';
            } catch(PDOException $e) {
                $error = 'Error creating sermon: ' . $e->getMessage();
                error_log($e->getMessage());
            }
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $title = sanitizeInput($_POST['title']);
            $preacher = sanitizeInput($_POST['preacher']);
            $sermon_date = sanitizeInput($_POST['sermon_date']);
            $scripture_reference = sanitizeInput($_POST['scripture_reference']);
            $description = sanitizeInput($_POST['description']);
            $audio_url = sanitizeInput($_POST['audio_url']);
            $video_url = sanitizeInput($_POST['video_url']);
            $series = sanitizeInput($_POST['series']);
            $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'published';

            try {
                $stmt = $conn->prepare("UPDATE sermons SET title = ?, preacher = ?, sermon_date = ?, scripture_reference = ?, description = ?, audio_url = ?, video_url = ?, series = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $preacher, $sermon_date, $scripture_reference, $description, $audio_url, $video_url, $series, $status, $id]);
                $success = 'Sermon updated successfully!';
            } catch(PDOException $e) {
                $error = 'Error updating sermon: ' . $e->getMessage();
                error_log($e->getMessage());
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("DELETE FROM sermons WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Sermon deleted successfully!';
            } catch(PDOException $e) {
                $error = 'Error deleting sermon.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'set_featured') {
            $id = (int)$_POST['id'];
            try {
                // First, unfeature all other sermons
                $stmt = $conn->prepare("UPDATE sermons SET featured = 0");
                $stmt->execute();

                // Then feature this sermon
                $stmt = $conn->prepare("UPDATE sermons SET featured = 1 WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Sermon set as featured successfully!';
            } catch(PDOException $e) {
                $error = 'Error setting featured sermon.';
                error_log($e->getMessage());
            }
        }
    }
}

// Get all sermons
try {
    $stmt = $conn->query("SELECT * FROM sermons ORDER BY sermon_date DESC, created_at DESC");
    $sermons = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Error loading sermons.';
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
        <h2><i class="fas fa-bible"></i> Manage Sermons</h2>
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Sermon
        </button>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 20px; color: #666;">
            <i class="fas fa-info-circle"></i> Manage sermon recordings, notes, and resources.
        </p>

        <?php if (count($sermons) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Preacher</th>
                            <th>Date</th>
                            <th>Scripture</th>
                            <th>Series</th>
                            <th>Media</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sermons as $sermon): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sermon['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sermon['preacher']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($sermon['sermon_date'])); ?></td>
                                <td><?php echo htmlspecialchars($sermon['scripture_reference'] ?: '-'); ?></td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($sermon['series'] ?: '-'); ?></span></td>
                                <td>
                                    <?php if ($sermon['audio_url']): ?>
                                        <i class="fas fa-microphone" title="Audio available"></i>
                                    <?php endif; ?>
                                    <?php if ($sermon['video_url']): ?>
                                        <i class="fas fa-video" title="Video available"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sermon['status'] === 'published'): ?>
                                        <span class="badge badge-success">Published</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sermon['featured']): ?>
                                        <span class="badge" style="background: #FFD700; color: #000;"><i class="fas fa-star"></i> Featured</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$sermon['featured']): ?>
                                        <button onclick="setFeatured(<?php echo $sermon['id']; ?>, '<?php echo htmlspecialchars($sermon['title']); ?>')" class="btn btn-sm btn-warning" style="background: #FFD700; color: #000;">
                                            <i class="fas fa-star"></i> Set Featured
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="editSermon(<?php echo $sermon['id']; ?>)" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="deleteSermon(<?php echo $sermon['id']; ?>, '<?php echo htmlspecialchars($sermon['title']); ?>')" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">No sermons yet. Click "Create New Sermon" to add one.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="sermonModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2 id="modalTitle">Create New Sermon</h2>
            <button onclick="closeModal()" class="modal-close">&times;</button>
        </div>

        <form method="POST" action="" id="sermonForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="sermon_id">

            <div class="form-group">
                <label>Sermon Title *</label>
                <input type="text" name="title" id="sermon_title" class="form-control" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Preacher *</label>
                    <input type="text" name="preacher" id="sermon_preacher" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Sermon Date *</label>
                    <input type="date" name="sermon_date" id="sermon_date" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Scripture Reference</label>
                    <input type="text" name="scripture_reference" id="sermon_scripture_reference" class="form-control" placeholder="e.g., John 3:16">
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="sermon_description" class="form-control" rows="3"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Audio URL</label>
                    <input type="url" name="audio_url" id="sermon_audio_url" class="form-control" placeholder="https://example.com/audio.mp3">
                    <small style="color: #666;">Link to MP3 or audio file</small>
                </div>

                <div class="form-group">
                    <label>Video URL</label>
                    <input type="url" name="video_url" id="sermon_video_url" class="form-control" placeholder="https://youtube.com/watch?v=...">
                    <small style="color: #666;">YouTube, Vimeo, or direct video link</small>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Series Name</label>
                    <input type="text" name="series" id="sermon_series" class="form-control" placeholder="e.g., Walking in Faith">
                    <small style="color: #666;">Optional: Group this sermon under a series</small>
                </div>

                <div class="form-group">
                    <label>Status *</label>
                    <select name="status" id="sermon_status" class="form-control" required>
                        <option value="published">Published (visible on website)</option>
                        <option value="draft">Draft (not visible)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitBtnText">Create Sermon</span>
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
</style>

<script>
const allSermons = <?php echo json_encode($sermons); ?>;

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create New Sermon';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitBtnText').textContent = 'Create Sermon';
    document.getElementById('sermonForm').reset();
    document.getElementById('sermonModal').style.display = 'block';
}

function editSermon(id) {
    const sermon = allSermons.find(s => s.id == id);
    if (!sermon) return;

    document.getElementById('modalTitle').textContent = 'Edit Sermon';
    document.getElementById('formAction').value = 'update';
    document.getElementById('submitBtnText').textContent = 'Save Changes';
    document.getElementById('sermon_id').value = sermon.id;
    document.getElementById('sermon_title').value = sermon.title;
    document.getElementById('sermon_preacher').value = sermon.preacher;
    document.getElementById('sermon_date').value = sermon.sermon_date;
    document.getElementById('sermon_scripture_reference').value = sermon.scripture_reference || '';
    document.getElementById('sermon_description').value = sermon.description || '';
    document.getElementById('sermon_audio_url').value = sermon.audio_url || '';
    document.getElementById('sermon_video_url').value = sermon.video_url || '';
    document.getElementById('sermon_series').value = sermon.series || '';
    document.getElementById('sermon_status').value = sermon.status || 'published';

    document.getElementById('sermonModal').style.display = 'block';
}

function setFeatured(id, title) {
    if (!confirm(`Set "${title}" as the featured sermon? This will replace the current featured sermon.`)) {
        return;
    }

    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="action" value="set_featured">
        <input type="hidden" name="id" value="${id}">
    `;
    document.body.appendChild(form);
    form.submit();
}

function deleteSermon(id, title) {
    if (!confirm(`Delete sermon "${title}"? This action cannot be undone.`)) {
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
    document.getElementById('sermonModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('sermonModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
