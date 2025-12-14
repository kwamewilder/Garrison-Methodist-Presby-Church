<?php
$pageTitle = 'Church Elders';
require_once 'includes/header.php';

$conn = getDBConnection();
$conn->exec("CREATE TABLE IF NOT EXISTS elders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    title VARCHAR(255) DEFAULT '',
    email VARCHAR(255) DEFAULT '',
    phone VARCHAR(100) DEFAULT '',
    location VARCHAR(255) DEFAULT '',
    image_url VARCHAR(500) DEFAULT '',
    bio TEXT,
    sort_order INT DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add sort_order if missing
try {
    $conn->exec("ALTER TABLE elders ADD COLUMN sort_order INT DEFAULT 0");
} catch (PDOException $e) {
    // ignore if exists
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        $name = sanitizeInput($_POST['name'] ?? '');
        $titleRole = sanitizeInput($_POST['title'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        $imageUrl = sanitizeInput($_POST['image_url'] ?? '');
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        try {
            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO elders (name, title, email, phone, location, image_url, bio, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$name, $titleRole, $email, $phone, $location, $imageUrl, $bio, $sortOrder]);
                $success = 'Elder added successfully.';
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $conn->prepare("UPDATE elders SET name = ?, title = ?, email = ?, phone = ?, location = ?, image_url = ?, bio = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$name, $titleRole, $email, $phone, $location, $imageUrl, $bio, $sortOrder, $id]);
                $success = 'Elder updated successfully.';
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $conn->prepare("DELETE FROM elders WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Elder deleted.';
            }
        } catch (PDOException $e) {
            $error = 'Error saving elder.';
            error_log($e->getMessage());
        }
    }
}

$elders = [];
try {
    $stmt = $conn->query("SELECT * FROM elders ORDER BY sort_order ASC, created_at DESC");
    $elders = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error loading elders.';
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

<style>
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}
.media-item {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    box-shadow: 0 12px 26px rgba(15,23,42,0.08);
    transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
}
.media-item:hover {
    transform: translateY(-4px);
    border-color: rgba(93,173,236,0.35);
    box-shadow: 0 16px 32px rgba(15,23,42,0.12);
}
.media-thumbnail {
    height: 260px;
    background: #0b1024;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.media-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.icon-placeholder {
    font-size: 3rem;
    color: #5DADEC;
}
.media-info {
    padding: 14px 16px 18px 16px;
}
.media-info h3 {
    margin: 0 0 6px 0;
}
.media-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.directory-line {
    display: flex;
    gap: 6px;
    align-items: flex-start;
    color: #4b5563;
    margin: 4px 0;
}
.directory-line i {
    color: #5DADEC;
}
.media-actions {
    display: flex;
    gap: 8px;
}
</style>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-users"></i> Church Elders</h2>
        <button onclick="showElderModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Elder
        </button>
    </div>
    <div class="card-body">
        <div class="media-grid">
            <?php if (count($elders) > 0): ?>
                <?php foreach ($elders as $elder): ?>
                    <div class="media-item">
                        <div class="media-thumbnail">
                            <?php if ($elder['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($elder['image_url']); ?>" alt="<?php echo htmlspecialchars($elder['name']); ?>">
                            <?php else: ?>
                                <div class="icon-placeholder"><i class="fas fa-user"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="media-info">
                            <div class="media-meta">
                                <span class="media-date"><?php echo date('M j, Y', strtotime($elder['created_at'])); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($elder['name']); ?></h3>
                            <?php if ($elder['title']): ?><p class="media-type" style="display:inline-block; margin-bottom:8px;"><?php echo htmlspecialchars($elder['title']); ?></p><?php endif; ?>
                            <?php if ($elder['email']): ?><div class="directory-line"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($elder['email']); ?></div><?php endif; ?>
                            <?php if ($elder['phone']): ?><div class="directory-line"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($elder['phone']); ?></div><?php endif; ?>
                            <?php if ($elder['location']): ?><div class="directory-line"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($elder['location']); ?></div><?php endif; ?>
                            <?php if ($elder['bio']): ?>
                                <p style="margin-top:8px; color:#4b5563;"><?php echo nl2br(htmlspecialchars($elder['bio'])); ?></p>
                            <?php endif; ?>
                            <div class="media-actions" style="margin-top:12px;">
                <button class="btn btn-sm btn-primary" onclick='editElder(<?php echo json_encode($elder); ?>)'><i class="fas fa-edit"></i> Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteElder(<?php echo (int)$elder['id']; ?>, '<?php echo htmlspecialchars($elder['name']); ?>')"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color:#666; padding:30px;">No elders added yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="elderModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h2 id="elderModalTitle">Add Elder</h2>
            <button onclick="closeElderModal()" class="modal-close">&times;</button>
        </div>
        <form method="POST" action="" id="elderForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" id="elderAction" value="create">
            <input type="hidden" name="id" id="elder_id">

            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" id="elder_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Title/Role</label>
                <input type="text" name="title" id="elder_title" class="form-control">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="elder_email" class="form-control">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" id="elder_phone" class="form-control">
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" id="elder_location" class="form-control">
            </div>
            <div class="form-group">
                <label>Image URL</label>
                <input type="url" name="image_url" id="elder_image_url" class="form-control" placeholder="https://example.com/photo.jpg">
                <input type="file" id="elder_image_upload" class="form-control" accept="image/*" style="margin-top:8px;">
                <small style="color:#666;">Upload an image to fill the URL automatically.</small>
            </div>
            <div class="form-group">
                <label>Order</label>
                <input type="number" name="sort_order" id="elder_sort_order" class="form-control" value="0" min="0" step="1">
                <small style="color:#666;">Lower numbers appear first.</small>
            </div>
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" id="elder_bio" class="form-control" rows="3"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
                <button type="button" onclick="closeElderModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <span id="elderSubmitText">Add Elder</span></button>
            </div>
        </form>
    </div>
</div>

<script>
const allElders = <?php echo json_encode($elders); ?>;

function showElderModal() {
    document.getElementById('elderModalTitle').textContent = 'Add Elder';
    document.getElementById('elderAction').value = 'create';
    document.getElementById('elderSubmitText').textContent = 'Add Elder';
    document.getElementById('elderForm').reset();
    document.getElementById('elder_sort_order').value = 0;
    document.getElementById('elderModal').style.display = 'block';
}

function editElder(elder) {
    document.getElementById('elderModalTitle').textContent = 'Edit Elder';
    document.getElementById('elderAction').value = 'update';
    document.getElementById('elderSubmitText').textContent = 'Save Changes';
    document.getElementById('elder_id').value = elder.id;
    document.getElementById('elder_name').value = elder.name;
    document.getElementById('elder_title').value = elder.title;
    document.getElementById('elder_email').value = elder.email;
    document.getElementById('elder_phone').value = elder.phone;
    document.getElementById('elder_location').value = elder.location;
    document.getElementById('elder_image_url').value = elder.image_url;
    document.getElementById('elder_bio').value = elder.bio;
    document.getElementById('elder_sort_order').value = elder.sort_order || 0;
    document.getElementById('elderModal').style.display = 'block';
}

function deleteElder(id, name) {
    if (!confirm(`Delete ${name}?`)) return;
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

function closeElderModal() {
    document.getElementById('elderModal').style.display = 'none';
}

document.getElementById('elder_image_upload').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

    document.getElementById('elder_image_url').value = 'Uploading...';

    fetch('api/upload.php', { method: 'POST', body: formData, credentials: 'same-origin' })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.file && data.file.url) {
                document.getElementById('elder_image_url').value = data.file.url;
            } else {
                document.getElementById('elder_image_url').value = '';
                alert((data && data.error) ? data.error : 'Upload failed');
            }
        })
        .catch(() => {
            document.getElementById('elder_image_url').value = '';
            alert('Upload failed');
        });
});

// Close modal when clicking outside
document.getElementById('elderModal').addEventListener('click', function(e) {
    if (e.target === this) closeElderModal();
});
</script>

<?php require_once 'includes/footer.php'; ?>
