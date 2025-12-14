<?php
$pageTitle = 'Community Media';
require_once 'includes/header.php';

$conn = getDBConnection();
$success = '';
$error = '';

// Validate community slug
$communitySlug = isset($_GET['community']) ? strtolower(trim($_GET['community'])) : '';
if ($communitySlug === '') {
    die('<p style="padding:20px;">Community slug is required. Go back to <a href="communities.php">Communities</a>.</p>');
}

// Ensure table exists
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS community_media (
            id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            community_slug VARCHAR(255) NOT NULL,
            title VARCHAR(200) NOT NULL,
            file_url TEXT NOT NULL,
            description TEXT NULL,
            uploaded_by INT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (community_slug),
            CONSTRAINT fk_cm_admin FOREIGN KEY (uploaded_by) REFERENCES admins(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
} catch (PDOException $e) {
    $error = 'Error preparing media storage.';
    error_log($e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create') {
            $title = sanitizeInput($_POST['title']);
            $file_url = sanitizeInput($_POST['file_url']);
            $description = sanitizeInput($_POST['description']);

            if (!$title || !$file_url) {
                $error = 'Title and image URL are required.';
            } else {
                try {
                    $stmt = $conn->prepare("INSERT INTO community_media (community_slug, title, file_url, description, uploaded_by) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$communitySlug, $title, $file_url, $description, getCurrentAdmin()['id']]);
                    $success = 'Image added to community media.';
                } catch(PDOException $e) {
                    $error = 'Error saving media item.';
                    error_log($e->getMessage());
                }
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("DELETE FROM community_media WHERE id = ? AND community_slug = ?");
                $stmt->execute([$id, $communitySlug]);
                $success = 'Media item deleted.';
            } catch(PDOException $e) {
                $error = 'Error deleting media item.';
                error_log($e->getMessage());
            }
        }
    }
}

// Load community media
$mediaItems = [];
try {
    $stmt = $conn->prepare("SELECT * FROM community_media WHERE community_slug = ? ORDER BY created_at DESC");
    $stmt->execute([$communitySlug]);
    $mediaItems = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Error loading media items.';
    error_log($e->getMessage());
}
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2>Community Media: <?php echo htmlspecialchars($communitySlug); ?></h2>
        <a class="btn btn-secondary" href="communities.php"><i class="fas fa-arrow-left"></i> Back to Communities</a>
    </div>
    <div class="card-body">
        <form method="POST" action="" style="margin-bottom: 20px;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="create">
            <div class="form-grid">
                <div class="form-group">
                    <label>Title *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Image URL *</label>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <input type="url" name="file_url" id="cm_file_url" class="form-control" placeholder="https://example.com/image.jpg" required>
                        <input type="file" id="cm_file_input" accept="image/*" style="display:none;">
                        <button type="button" class="btn btn-secondary" id="cm_upload_btn"><i class="fas fa-upload"></i> Upload</button>
                    </div>
                    <small id="cm_upload_status" style="color:#666;"></small>
                </div>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Optional note"></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Add Image</button>
        </form>

        <?php if (count($mediaItems) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Title</th>
                            <th>URL</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mediaItems as $item): ?>
                            <tr>
                                <td style="width:120px;">
                                    <img src="<?php echo htmlspecialchars($item['file_url']); ?>" alt="" style="width:100%; max-height:80px; object-fit:cover; border-radius:6px;">
                                </td>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td style="word-break: break-all; font-size: 0.9rem;"><?php echo htmlspecialchars($item['file_url']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($item['created_at'])); ?></td>
                                <td>
                                    <form method="POST" action="" onsubmit="return confirm('Delete this image?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align:center; color:#777; padding: 20px 0;">No media yet. Add images above to populate this community's gallery.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
(function() {
    const uploadBtn = document.getElementById('cm_upload_btn');
    const fileInput = document.getElementById('cm_file_input');
    const urlInput = document.getElementById('cm_file_url');
    const statusEl = document.getElementById('cm_upload_status');

    if (!uploadBtn || !fileInput || !urlInput) return;

    uploadBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', () => {
        const file = fileInput.files[0];
        if (!file) return;

        statusEl.textContent = 'Uploading...';
        const formData = new FormData();
        formData.append('file', file);
        formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

        fetch('api/upload.php', { method: 'POST', body: formData, credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.file && data.file.url) {
                    urlInput.value = data.file.url;
                    statusEl.textContent = 'Uploaded successfully.';
                } else {
                    statusEl.textContent = data.error || 'Upload failed.';
                }
            })
            .catch(() => { statusEl.textContent = 'Upload failed.'; });
    });
})();
</script>
