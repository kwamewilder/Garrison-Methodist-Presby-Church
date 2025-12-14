<?php
$pageTitle = 'Posts';
require_once 'includes/header.php';
require_once '../includes/block_renderer.php';

$conn = getDBConnection();

// Ensure table
$conn->exec("CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    community_id INT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    excerpt TEXT,
    content TEXT,
    image_url VARCHAR(500),
    author VARCHAR(255),
    status ENUM('draft','published') DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (community_id),
    INDEX (status),
    INDEX (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Fetch communities for dropdown
$communities = [];
try {
    $stmt = $conn->query("SELECT id, name FROM communities WHERE status = 'active' ORDER BY name");
    $communities = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Communities fetch error: ' . $e->getMessage());
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    return strtolower($text ?: uniqid('post-'));
}

$success = '';
$error = '';
$editingPost = null;

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        $title = sanitizeInput($_POST['title'] ?? '');
        $slug = sanitizeInput($_POST['slug'] ?? '');
        $slug = $slug ? slugify($slug) : slugify($title);
        $excerpt = trim($_POST['excerpt'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $imageUrl = sanitizeInput($_POST['image_url'] ?? '');
        $author = sanitizeInput($_POST['author'] ?? '');
        $status = in_array($_POST['status'] ?? 'draft', ['draft','published'], true) ? $_POST['status'] : 'draft';
        $publishedAt = $_POST['published_at'] ?? null;
        $communityId = !empty($_POST['community_id']) ? (int)$_POST['community_id'] : null;

        if ($action === 'create') {
            try {
                $stmt = $conn->prepare("INSERT INTO posts (community_id, title, slug, excerpt, content, image_url, author, status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$communityId, $title, $slug, $excerpt, $content, $imageUrl, $author, $status, $publishedAt ?: null]);
                $success = 'Post created.';
            } catch (PDOException $e) {
                $error = 'Error creating post.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            try {
                $stmt = $conn->prepare("UPDATE posts SET community_id = ?, title = ?, slug = ?, excerpt = ?, content = ?, image_url = ?, author = ?, status = ?, published_at = ? WHERE id = ?");
                $stmt->execute([$communityId, $title, $slug, $excerpt, $content, $imageUrl, $author, $status, $publishedAt ?: null, $id]);
                $success = 'Post updated.';
            } catch (PDOException $e) {
                $error = 'Error updating post.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            try {
                $stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Post deleted.';
            } catch (PDOException $e) {
                $error = 'Error deleting post.';
                error_log($e->getMessage());
            }
        }
    }
}

// Load for editing if requested
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    try {
        $stmt = $conn->prepare("SELECT * FROM posts WHERE id = ?");
        $stmt->execute([$editId]);
        $editingPost = $stmt->fetch();
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// Fetch all posts for list
$posts = [];
try {
    $posts = $conn->query("SELECT p.*, c.name AS community_name FROM posts p LEFT JOIN communities c ON p.community_id = c.id ORDER BY created_at DESC")->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>

<?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-newspaper"></i> Posts</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="" class="form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" value="<?php echo $editingPost ? 'update' : 'create'; ?>">
            <?php if ($editingPost): ?>
                <input type="hidden" name="id" value="<?php echo (int)$editingPost['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Title *</label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($editingPost['title'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Slug</label>
                <input type="text" name="slug" class="form-control" placeholder="leave blank to auto-generate" value="<?php echo htmlspecialchars($editingPost['slug'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Community</label>
                <select name="community_id" class="form-control">
                    <option value="">(None)</option>
                    <?php foreach ($communities as $comm): ?>
                        <option value="<?php echo (int)$comm['id']; ?>" <?php echo (!empty($editingPost['community_id']) && (int)$editingPost['community_id'] === (int)$comm['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($comm['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Excerpt</label>
                <textarea name="excerpt" class="form-control" rows="3"><?php echo htmlspecialchars($editingPost['excerpt'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Feature Image URL</label>
                <input type="url" name="image_url" id="post_image_url" class="form-control" value="<?php echo htmlspecialchars($editingPost['image_url'] ?? ''); ?>" placeholder="https://example.com/image.jpg">
                <input type="file" id="post_image_upload" class="form-control" accept="image/*" style="margin-top:8px;">
                <small style="color:#666;">Upload to auto-fill the URL.</small>
            </div>
            <div class="form-group">
                <label>Author</label>
                <input type="text" name="author" class="form-control" value="<?php echo htmlspecialchars($editingPost['author'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Published At</label>
                <input type="datetime-local" name="published_at" class="form-control" value="<?php echo !empty($editingPost['published_at']) ? date('Y-m-d\TH:i', strtotime($editingPost['published_at'])) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status" class="form-control">
                    <option value="draft" <?php echo (($editingPost['status'] ?? '') === 'draft') ? 'selected' : ''; ?>>Draft</option>
                    <option value="published" <?php echo (($editingPost['status'] ?? 'published') === 'published') ? 'selected' : ''; ?>>Published</option>
                </select>
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Content (use your block JSON or simple HTML/text)</label>
                <textarea name="content" class="form-control" rows="10"><?php echo htmlspecialchars($editingPost['content'] ?? ''); ?></textarea>
                <small style="color:#666;">If you paste block JSON, it will render via the block renderer. Otherwise, plain text/HTML is shown.</small>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end; grid-column: 1 / -1;">
                <a href="posts.php" class="btn btn-secondary">Reset</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $editingPost ? 'Save Changes' : 'Add Post'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> All Posts</h3>
    </div>
    <div class="card-body">
        <?php if (count($posts) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Community</th>
                            <th>Status</th>
                            <th>Published At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['title']); ?></td>
                                <td><?php echo htmlspecialchars($p['slug']); ?></td>
                                <td><?php echo htmlspecialchars($p['community_name'] ?? '—'); ?></td>
                                <td><?php echo htmlspecialchars(ucfirst($p['status'])); ?></td>
                                <td><?php echo $p['published_at'] ? date('M j, Y g:i A', strtotime($p['published_at'])) : '—'; ?></td>
                                <td>
                                    <a class="btn btn-sm btn-primary" href="posts.php?edit=<?php echo (int)$p['id']; ?>"><i class="fas fa-edit"></i> Edit</a>
                                    <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete this post?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p style="text-align:center; color:#666;">No posts yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('post_image_upload').addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
    document.getElementById('post_image_url').value = 'Uploading...';
    fetch('api/upload.php', { method: 'POST', body: formData, credentials: 'same-origin' })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.file && data.file.url) {
                document.getElementById('post_image_url').value = data.file.url;
            } else {
                document.getElementById('post_image_url').value = '';
                alert((data && data.error) ? data.error : 'Upload failed');
            }
        })
        .catch(() => {
            document.getElementById('post_image_url').value = '';
            alert('Upload failed');
        });
});
</script>

<?php require_once 'includes/footer.php'; ?>
