<?php
$pageTitle = 'Manage Media';
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
            $file_url = sanitizeInput($_POST['file_url']);
            $file_type = sanitizeInput($_POST['file_type']);
            $description = sanitizeInput($_POST['description']);

            try {
                $stmt = $conn->prepare("INSERT INTO media (title, file_url, file_type, description, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$title, $file_url, $file_type, $description, getCurrentAdmin()['id']]);
                $success = 'Media item created successfully!';
            } catch(PDOException $e) {
                $error = 'Error creating media item.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $title = sanitizeInput($_POST['title']);
            $file_url = sanitizeInput($_POST['file_url']);
            $description = sanitizeInput($_POST['description']);

            try {
                $stmt = $conn->prepare("UPDATE media SET title = ?, file_url = ?, description = ? WHERE id = ?");
                $stmt->execute([$title, $file_url, $description, $id]);
                $success = 'Media item updated successfully!';
            } catch(PDOException $e) {
                $error = 'Error updating media item.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("DELETE FROM media WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Media item deleted successfully!';
            } catch(PDOException $e) {
                $error = 'Error deleting media item.';
                error_log($e->getMessage());
            }
        }
    }
}

// Get all media
try {
    $stmt = $conn->query("SELECT * FROM media ORDER BY created_at DESC");
    $mediaItems = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Error loading media.';
    error_log($e->getMessage());
}

// Group media by type
$imageItems = array_filter($mediaItems, fn($item) => $item['file_type'] === 'image');
$videoItems = array_filter($mediaItems, fn($item) => $item['file_type'] === 'video');
$audioItems = array_filter($mediaItems, fn($item) => $item['file_type'] === 'audio');
$documentItems = array_filter($mediaItems, fn($item) => $item['file_type'] === 'document');
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
        <h2><i class="fas fa-photo-video"></i> Manage Media</h2>
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Media Item
        </button>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 20px; color: #666;">
            <i class="fas fa-info-circle"></i> Manage images, videos, audio files, and documents. Copy the URL to use in pages, sermons, or events.
        </p>

        <!-- Tabs -->
        <div class="media-tabs" style="margin-bottom: 30px; display: flex; gap: 10px; border-bottom: 2px solid #e0e0e0;">
            <button class="media-tab-btn active" onclick="showMediaTab('all')">All Media (<?php echo count($mediaItems); ?>)</button>
            <button class="media-tab-btn" onclick="showMediaTab('images')">Images (<?php echo count($imageItems); ?>)</button>
            <button class="media-tab-btn" onclick="showMediaTab('videos')">Videos (<?php echo count($videoItems); ?>)</button>
            <button class="media-tab-btn" onclick="showMediaTab('audio')">Audio (<?php echo count($audioItems); ?>)</button>
            <button class="media-tab-btn" onclick="showMediaTab('documents')">Documents (<?php echo count($documentItems); ?>)</button>
        </div>

        <!-- All Media Tab -->
        <div id="tab-all-media" class="media-tab-content active">
            <?php if (count($mediaItems) > 0): ?>
                <div class="media-grid">
                    <?php foreach ($mediaItems as $media): ?>
                        <div class="media-item">
                            <div class="media-preview">
                                <?php if ($media['file_type'] === 'image'): ?>
                                    <img src="<?php echo htmlspecialchars($media['file_url']); ?>" alt="<?php echo htmlspecialchars($media['title']); ?>" style="width: 100%; height: 150px; object-fit: cover;">
                                <?php elseif ($media['file_type'] === 'video'): ?>
                                    <div style="background: #000; color: white; height: 150px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-video" style="font-size: 3rem;"></i>
                                    </div>
                                <?php elseif ($media['file_type'] === 'audio'): ?>
                                    <div style="background: #5DADEC; color: white; height: 150px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-music" style="font-size: 3rem;"></i>
                                    </div>
                                <?php else: ?>
                                    <div style="background: #f0f0f0; color: #666; height: 150px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-file" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="media-info">
                                <h4><?php echo htmlspecialchars($media['title']); ?></h4>
                                <span class="badge badge-info"><?php echo ucfirst($media['file_type']); ?></span>
                                <div class="media-actions" style="margin-top: 10px;">
                                    <button onclick="copyUrl('<?php echo htmlspecialchars($media['file_url']); ?>')" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-copy"></i> Copy URL
                                    </button>
                                    <button onclick="editMedia(<?php echo $media['id']; ?>)" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="deleteMedia(<?php echo $media['id']; ?>, '<?php echo htmlspecialchars($media['title']); ?>')" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No media items yet. Click "Add Media Item" to upload.</p>
            <?php endif; ?>
        </div>

        <!-- Images Tab -->
        <div id="tab-images-media" class="media-tab-content">
            <?php if (count($imageItems) > 0): ?>
                <div class="media-grid">
                    <?php foreach ($imageItems as $media): ?>
                        <div class="media-item">
                            <div class="media-preview">
                                <img src="<?php echo htmlspecialchars($media['file_url']); ?>" alt="<?php echo htmlspecialchars($media['title']); ?>" style="width: 100%; height: 150px; object-fit: cover;">
                            </div>
                            <div class="media-info">
                                <h4><?php echo htmlspecialchars($media['title']); ?></h4>
                                <div class="media-actions" style="margin-top: 10px;">
                                    <button onclick="copyUrl('<?php echo htmlspecialchars($media['file_url']); ?>')" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-copy"></i> Copy URL
                                    </button>
                                    <button onclick="editMedia(<?php echo $media['id']; ?>)" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteMedia(<?php echo $media['id']; ?>, '<?php echo htmlspecialchars($media['title']); ?>')" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No images yet.</p>
            <?php endif; ?>
        </div>

        <!-- Videos Tab -->
        <div id="tab-videos-media" class="media-tab-content">
            <?php if (count($videoItems) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>URL</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($videoItems as $media): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($media['title']); ?></strong></td>
                                    <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars(substr($media['file_url'], 0, 50)) . '...'; ?></code></td>
                                    <td><?php echo date('M d, Y', strtotime($media['created_at'])); ?></td>
                                    <td>
                                        <button onclick="copyUrl('<?php echo htmlspecialchars($media['file_url']); ?>')" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                        <button onclick="editMedia(<?php echo $media['id']; ?>)" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteMedia(<?php echo $media['id']; ?>, '<?php echo htmlspecialchars($media['title']); ?>')" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No videos yet.</p>
            <?php endif; ?>
        </div>

        <!-- Audio Tab -->
        <div id="tab-audio-media" class="media-tab-content">
            <?php if (count($audioItems) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>URL</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($audioItems as $media): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($media['title']); ?></strong></td>
                                    <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars(substr($media['file_url'], 0, 50)) . '...'; ?></code></td>
                                    <td><?php echo date('M d, Y', strtotime($media['created_at'])); ?></td>
                                    <td>
                                        <button onclick="copyUrl('<?php echo htmlspecialchars($media['file_url']); ?>')" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                        <button onclick="editMedia(<?php echo $media['id']; ?>)" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteMedia(<?php echo $media['id']; ?>, '<?php echo htmlspecialchars($media['title']); ?>')" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No audio files yet.</p>
            <?php endif; ?>
        </div>

        <!-- Documents Tab -->
        <div id="tab-documents-media" class="media-tab-content">
            <?php if (count($documentItems) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>URL</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documentItems as $media): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($media['title']); ?></strong></td>
                                    <td><code style="font-size: 0.85rem;"><?php echo htmlspecialchars(substr($media['file_url'], 0, 50)) . '...'; ?></code></td>
                                    <td><?php echo date('M d, Y', strtotime($media['created_at'])); ?></td>
                                    <td>
                                        <button onclick="copyUrl('<?php echo htmlspecialchars($media['file_url']); ?>')" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-copy"></i> Copy
                                        </button>
                                        <button onclick="editMedia(<?php echo $media['id']; ?>)" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteMedia(<?php echo $media['id']; ?>, '<?php echo htmlspecialchars($media['title']); ?>')" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No documents yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="mediaModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 id="modalTitle">Add Media Item</h2>
            <button onclick="closeModal()" class="modal-close">&times;</button>
        </div>

        <form method="POST" action="" id="mediaForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="media_id">

            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" id="media_title" class="form-control" required>
            </div>

            <div class="form-group">
                <label>File URL *</label>
                <input type="url" name="file_url" id="media_file_url" class="form-control" required placeholder="https://example.com/file.jpg">
                <small style="color: #666;">Link to the file hosted online (e.g., Google Drive, Dropbox, YouTube, etc.)</small>
            </div>

            <div class="form-group" id="typeGroup">
                <label>File Type *</label>
                <select name="file_type" id="media_file_type" class="form-control" required>
                    <option value="image">Image</option>
                    <option value="video">Video</option>
                    <option value="audio">Audio</option>
                    <option value="document">Document</option>
                </select>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" id="media_description" class="form-control" rows="3"></textarea>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitBtnText">Add Media</span>
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

.media-tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    transition: all 0.3s;
}

.media-tab-btn:hover {
    color: #5DADEC;
}

.media-tab-btn.active {
    color: #5DADEC;
    border-bottom-color: #5DADEC;
}

.media-tab-content {
    display: none;
    padding: 20px 0;
}

.media-tab-content.active {
    display: block;
}

.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.media-item {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s;
}

.media-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.media-info {
    padding: 15px;
}

.media-info h4 {
    margin: 0 0 10px 0;
    font-size: 1rem;
}
</style>

<script>
const allMedia = <?php echo json_encode($mediaItems); ?>;

function showMediaTab(tab) {
    document.querySelectorAll('.media-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.media-tab-content').forEach(content => content.classList.remove('active'));

    event.target.classList.add('active');
    document.getElementById('tab-' + tab + '-media').classList.add('active');
}

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Add Media Item';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitBtnText').textContent = 'Add Media';
    document.getElementById('typeGroup').style.display = 'block';
    document.getElementById('mediaForm').reset();
    document.getElementById('mediaModal').style.display = 'block';
}

function editMedia(id) {
    const media = allMedia.find(m => m.id == id);
    if (!media) return;

    document.getElementById('modalTitle').textContent = 'Edit Media Item';
    document.getElementById('formAction').value = 'update';
    document.getElementById('submitBtnText').textContent = 'Save Changes';
    document.getElementById('media_id').value = media.id;
    document.getElementById('media_title').value = media.title;
    document.getElementById('media_file_url').value = media.file_url;
    document.getElementById('media_description').value = media.description || '';
    document.getElementById('typeGroup').style.display = 'none';

    document.getElementById('mediaModal').style.display = 'block';
}

function deleteMedia(id, title) {
    if (!confirm(`Delete media "${title}"? This action cannot be undone.`)) {
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

function copyUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        alert('URL copied to clipboard!');
    }).catch(err => {
        prompt('Copy this URL:', url);
    });
}

function closeModal() {
    document.getElementById('mediaModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('mediaModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
