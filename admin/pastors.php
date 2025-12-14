<?php
$pageTitle = 'Pastorial Team';
require_once 'includes/header.php';

$conn = getDBConnection();

// Ensure table exists
$conn->exec("CREATE TABLE IF NOT EXISTS pastors (
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
    $conn->exec("ALTER TABLE pastors ADD COLUMN sort_order INT DEFAULT 0");
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
                $stmt = $conn->prepare("INSERT INTO pastors (name, title, email, phone, location, image_url, bio, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$name, $titleRole, $email, $phone, $location, $imageUrl, $bio, $sortOrder]);
                $success = 'Team member added successfully.';
            } elseif ($action === 'update') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $conn->prepare("UPDATE pastors SET name = ?, title = ?, email = ?, phone = ?, location = ?, image_url = ?, bio = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$name, $titleRole, $email, $phone, $location, $imageUrl, $bio, $sortOrder, $id]);
                $success = 'Team member updated successfully.';
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                $stmt = $conn->prepare("DELETE FROM pastors WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Team member deleted.';
            }
        } catch (PDOException $e) {
            $error = 'Error saving team member.';
            error_log($e->getMessage());
        }
    }
}

$pastors = [];
try {
$stmt = $conn->query("SELECT * FROM pastors ORDER BY sort_order ASC, created_at DESC");
    $pastors = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error loading pastors.';
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
        <h2><i class="fas fa-user-tie"></i> Pastorial Team</h2>
        <button onclick="showPastorModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add to Team
        </button>
    </div>
    <div class="card-body">
        <div class="media-grid">
            <?php if (count($pastors) > 0): ?>
                <?php foreach ($pastors as $pastor): ?>
                    <div class="media-item">
                        <div class="media-thumbnail">
                            <?php if ($pastor['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($pastor['image_url']); ?>" alt="<?php echo htmlspecialchars($pastor['name']); ?>">
                            <?php else: ?>
                                <div class="icon-placeholder"><i class="fas fa-user-tie"></i></div>
                            <?php endif; ?>
                        </div>
                        <div class="media-info">
                            <div class="media-meta">
                                <span class="media-date"><?php echo date('M j, Y', strtotime($pastor['created_at'])); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($pastor['name']); ?></h3>
                            <?php if ($pastor['title']): ?><p class="media-type" style="display:inline-block; margin-bottom:8px;"><?php echo htmlspecialchars($pastor['title']); ?></p><?php endif; ?>
                            <?php if ($pastor['email']): ?><div class="directory-line"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($pastor['email']); ?></div><?php endif; ?>
                            <?php if ($pastor['phone']): ?><div class="directory-line"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($pastor['phone']); ?></div><?php endif; ?>
                            <?php if ($pastor['location']): ?><div class="directory-line"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($pastor['location']); ?></div><?php endif; ?>
                            <?php if ($pastor['bio']): ?>
                                <p style="margin-top:8px; color:#4b5563;"><?php echo nl2br(htmlspecialchars($pastor['bio'])); ?></p>
                            <?php endif; ?>
                            <div class="media-actions" style="margin-top:12px;">
                <button class="btn btn-sm btn-primary" onclick='editPastor(<?php echo json_encode($pastor); ?>)'><i class="fas fa-edit"></i> Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deletePastor(<?php echo (int)$pastor['id']; ?>, '<?php echo htmlspecialchars($pastor['name']); ?>')"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </div>
    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; color:#666; padding:30px;">No pastors added yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="pastorModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h2 id="pastorModalTitle">Add Pastor</h2>
            <button onclick="closePastorModal()" class="modal-close">&times;</button>
        </div>
        <form method="POST" action="" id="pastorForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" id="pastorAction" value="create">
            <input type="hidden" name="id" id="pastor_id">

            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" id="pastor_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Title/Role</label>
                <input type="text" name="title" id="pastor_title" class="form-control">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="pastor_email" class="form-control">
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" id="pastor_phone" class="form-control">
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" id="pastor_location" class="form-control">
            </div>
            <div class="form-group">
                <label>Image URL</label>
                <input type="url" name="image_url" id="pastor_image_url" class="form-control" placeholder="https://example.com/photo.jpg">
                <input type="file" id="pastor_image_upload" class="form-control" accept="image/*" style="margin-top:8px;">
                <small style="color:#666;">Upload an image to fill the URL automatically.</small>
            </div>
            <div class="form-group">
                <label>Order</label>
                <input type="number" name="sort_order" id="pastor_sort_order" class="form-control" value="0" min="0" step="1">
                <small style="color:#666;">Lower numbers appear first.</small>
            </div>
            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" id="pastor_bio" class="form-control" rows="3"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:16px;">
                <button type="button" onclick="closePastorModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> <span id="pastorSubmitText">Add to Team</span></button>
            </div>
        </form>
    </div>
</div>

<script>
const allPastors = <?php echo json_encode($pastors); ?>;

function showPastorModal() {
    document.getElementById('pastorModalTitle').textContent = 'Add to Team';
    document.getElementById('pastorAction').value = 'create';
    document.getElementById('pastorSubmitText').textContent = 'Add to Team';
    document.getElementById('pastorForm').reset();
    document.getElementById('pastorModal').style.display = 'block';
}

function editPastor(pastor) {
    document.getElementById('pastorModalTitle').textContent = 'Edit Pastor';
    document.getElementById('pastorAction').value = 'update';
    document.getElementById('pastorSubmitText').textContent = 'Save Changes';
    document.getElementById('pastor_id').value = pastor.id;
    document.getElementById('pastor_name').value = pastor.name;
    document.getElementById('pastor_title').value = pastor.title;
    document.getElementById('pastor_email').value = pastor.email;
    document.getElementById('pastor_phone').value = pastor.phone;
    document.getElementById('pastor_location').value = pastor.location;
    document.getElementById('pastor_image_url').value = pastor.image_url;
    document.getElementById('pastor_bio').value = pastor.bio;
    document.getElementById('pastor_sort_order').value = pastor.sort_order || 0;
    document.getElementById('pastorModal').style.display = 'block';
}

function deletePastor(id, name) {
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

function closePastorModal() {
    document.getElementById('pastorModal').style.display = 'none';
}

document.getElementById('pastor_image_upload').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

    document.getElementById('pastor_image_url').value = 'Uploading...';

    fetch('api/upload.php', { method: 'POST', body: formData, credentials: 'same-origin' })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.file && data.file.url) {
                document.getElementById('pastor_image_url').value = data.file.url;
            } else {
                document.getElementById('pastor_image_url').value = '';
                alert((data && data.error) ? data.error : 'Upload failed');
            }
        })
        .catch(() => {
            document.getElementById('pastor_image_url').value = '';
            alert('Upload failed');
        });
});

// Close modal when clicking outside
document.getElementById('pastorModal').addEventListener('click', function(e) {
    if (e.target === this) closePastorModal();
});
</script>

<?php require_once 'includes/footer.php'; ?>
