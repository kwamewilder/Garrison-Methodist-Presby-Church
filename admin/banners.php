<?php
$pageTitle = 'Manage Scrolling Banners';
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

        if ($action === 'add') {
            $page_location = sanitizeInput($_POST['page_location']);
            $banner_text = sanitizeInput($_POST['banner_text']);
            $link_url = sanitizeInput($_POST['link_url'] ?? '');
            $text_color = sanitizeInput($_POST['text_color']);
            $background_color = sanitizeInput($_POST['background_color']);
            $display_order = (int)$_POST['display_order'];

            try {
                $stmt = $conn->prepare("INSERT INTO scrolling_banners (page_location, banner_text, link_url, text_color, background_color, display_order, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$page_location, $banner_text, $link_url ?: null, $text_color, $background_color, $display_order, getCurrentAdmin()['id']]);
                $success = 'Banner added successfully!';
            } catch(PDOException $e) {
                $error = 'Error adding banner.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $page_location = sanitizeInput($_POST['page_location']);
            $banner_text = sanitizeInput($_POST['banner_text']);
            $link_url = sanitizeInput($_POST['link_url'] ?? '');
            $text_color = sanitizeInput($_POST['text_color']);
            $background_color = sanitizeInput($_POST['background_color']);
            $display_order = (int)$_POST['display_order'];
            $status = sanitizeInput($_POST['status']);

            try {
                $stmt = $conn->prepare("UPDATE scrolling_banners SET page_location = ?, banner_text = ?, link_url = ?, text_color = ?, background_color = ?, display_order = ?, status = ? WHERE id = ?");
                $stmt->execute([$page_location, $banner_text, $link_url ?: null, $text_color, $background_color, $display_order, $status, $id]);
                $success = 'Banner updated successfully!';
            } catch(PDOException $e) {
                $error = 'Error updating banner.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("DELETE FROM scrolling_banners WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Banner deleted successfully!';
            } catch(PDOException $e) {
                $error = 'Error deleting banner.';
                error_log($e->getMessage());
            }
        }
    }
}

// Get all banners
try {
    $stmt = $conn->query("SELECT * FROM scrolling_banners ORDER BY page_location, display_order ASC");
    $banners = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Error loading banners.';
    error_log($e->getMessage());
}

// Group banners by page
$bannersByPage = [];
foreach ($banners as $banner) {
    $bannersByPage[$banner['page_location']][] = $banner;
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
        <h2><i class="fas fa-scroll"></i> Scrolling Banners</h2>
        <button onclick="showAddModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Banner
        </button>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 20px; color: #666;">
            <i class="fas fa-info-circle"></i> Create scrolling text banners for different pages. Each page can have multiple banners that scroll automatically.
        </p>

        <div class="banner-tabs" style="margin-bottom: 30px; display: flex; flex-wrap: wrap; gap: 5px;">
            <button class="tab-btn active" onclick="showTab('homepage')">Homepage</button>
            <button class="tab-btn" onclick="showTab('about')">About</button>
            <button class="tab-btn" onclick="showTab('sermons')">Sermons</button>
            <button class="tab-btn" onclick="showTab('events')">Events</button>
            <button class="tab-btn" onclick="showTab('contact')">Contact</button>
            <button class="tab-btn" onclick="showTab('communities')">All Communities</button>
            <button class="tab-btn" onclick="showTab('audio-visual-team')">A/V Team</button>
            <button class="tab-btn" onclick="showTab('choir')">Choir</button>
            <button class="tab-btn" onclick="showTab('israel')">Israel</button>
            <button class="tab-btn" onclick="showTab('junior-youth')">Junior Youth</button>
            <button class="tab-btn" onclick="showTab('youth-fellowship')">Youth</button>
            <button class="tab-btn" onclick="showTab('womens-fellowship')">Women</button>
            <button class="tab-btn" onclick="showTab('mens-fellowship')">Men</button>
            <button class="tab-btn" onclick="showTab('children-service')">Children</button>
            <button class="tab-btn" onclick="showTab('singing-band')">Singing Band</button>
            <button class="tab-btn" onclick="showTab('ark-of-praise')">Ark of Praise</button>
        </div>

        <!-- Homepage Banners -->
        <div id="tab-homepage" class="tab-content active">
            <h3>Homepage</h3>
            <?php if (isset($bannersByPage['homepage']) && count($bannersByPage['homepage']) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Banner Text</th>
                                <th>Colors</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bannersByPage['homepage'] as $banner): ?>
                                <tr>
                                    <td><?php echo $banner['display_order']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($banner['banner_text'], 0, 80)) . (strlen($banner['banner_text']) > 80 ? '...' : ''); ?></td>
                                    <td>
                                        <span style="background: <?php echo $banner['background_color']; ?>; color: <?php echo $banner['text_color']; ?>; padding: 5px 10px; border-radius: 5px;">
                                            Preview
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $banner['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($banner['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick='editBanner(<?php echo json_encode($banner); ?>)' class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteBanner(<?php echo $banner['id']; ?>)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No homepage banners yet. Click "Add New Banner" to create one.</p>
            <?php endif; ?>
        </div>

<?php
        // Define a function to render banner tables
        function renderBannerTable($banners) {
            if (empty($banners)) return;
            ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Banner Text</th>
                            <th>Colors</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($banners as $banner): ?>
                            <tr>
                                <td><?php echo $banner['display_order']; ?></td>
                                <td><?php echo htmlspecialchars(substr($banner['banner_text'], 0, 80)) . (strlen($banner['banner_text']) > 80 ? '...' : ''); ?></td>
                                <td>
                                    <span style="background: <?php echo $banner['background_color']; ?>; color: <?php echo $banner['text_color']; ?>; padding: 5px 10px; border-radius: 5px;">
                                        Preview
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $banner['status'] === 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($banner['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick='editBanner(<?php echo json_encode($banner); ?>)' class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button onclick="deleteBanner(<?php echo $banner['id']; ?>)" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        ?>

        <!-- About Page Banners -->
        <div id="tab-about" class="tab-content">
            <h3>About Page</h3>
            <?php if (isset($bannersByPage['about']) && count($bannersByPage['about']) > 0): ?>
                <?php renderBannerTable($bannersByPage['about']); ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No about page banners yet. Click "Add New Banner" to create one.</p>
            <?php endif; ?>
        </div>

        <!-- Sermons Page Banners -->
        <div id="tab-sermons" class="tab-content">
            <h3>Sermons Page</h3>
            <?php if (isset($bannersByPage['sermons']) && count($bannersByPage['sermons']) > 0): ?>
                <?php renderBannerTable($bannersByPage['sermons']); ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No sermons page banners yet. Click "Add New Banner" to create one.</p>
            <?php endif; ?>
        </div>

        <!-- Events Page Banners -->
        <div id="tab-events" class="tab-content">
            <h3>Events Page</h3>
            <?php if (isset($bannersByPage['events']) && count($bannersByPage['events']) > 0): ?>
                <?php renderBannerTable($bannersByPage['events']); ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No events page banners yet. Click "Add New Banner" to create one.</p>
            <?php endif; ?>
        </div>

        <!-- Contact Page Banners -->
        <div id="tab-contact" class="tab-content">
            <h3>Contact Page</h3>
            <?php if (isset($bannersByPage['contact']) && count($bannersByPage['contact']) > 0): ?>
                <?php renderBannerTable($bannersByPage['contact']); ?>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No contact page banners yet. Click "Add New Banner" to create one.</p>
            <?php endif; ?>
        </div>

        <!-- Community Pages Banners (All) -->
        <div id="tab-communities" class="tab-content">
            <h3>All Community Pages</h3>
            <?php if (isset($bannersByPage['communities']) && count($bannersByPage['communities']) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Banner Text</th>
                                <th>Colors</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bannersByPage['communities'] as $banner): ?>
                                <tr>
                                    <td><?php echo $banner['display_order']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($banner['banner_text'], 0, 80)) . (strlen($banner['banner_text']) > 80 ? '...' : ''); ?></td>
                                    <td>
                                        <span style="background: <?php echo $banner['background_color']; ?>; color: <?php echo $banner['text_color']; ?>; padding: 5px 10px; border-radius: 5px;">
                                            Preview
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $banner['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($banner['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button onclick='editBanner(<?php echo json_encode($banner); ?>)' class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button onclick="deleteBanner(<?php echo $banner['id']; ?>)" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No community page banners yet. Click "Add New Banner" to create one.</p>
            <?php endif; ?>
        </div>

        <!-- Individual Community Pages -->
        <?php
        $communityPages = [
            'audio-visual-team' => 'Audio Visual Team',
            'choir' => 'Choir',
            'israel' => 'Israel',
            'junior-youth' => 'Junior Youth',
            'youth-fellowship' => 'Youth Fellowship',
            'womens-fellowship' => "Women's Fellowship",
            'mens-fellowship' => "Men's Fellowship",
            'children-service' => 'Children Service',
            'singing-band' => 'Singing Band',
            'ark-of-praise' => 'Ark of Praise'
        ];

        foreach ($communityPages as $pageKey => $pageTitle):
        ?>
            <div id="tab-<?php echo $pageKey; ?>" class="tab-content">
                <h3><?php echo $pageTitle; ?></h3>
                <?php if (isset($bannersByPage[$pageKey]) && count($bannersByPage[$pageKey]) > 0): ?>
                    <?php renderBannerTable($bannersByPage[$pageKey]); ?>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">No <?php echo strtolower($pageTitle); ?> banners yet. Click "Add New Banner" to create one.</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="bannerModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 id="modalTitle">Add New Banner</h2>
            <button onclick="closeModal()" class="modal-close">&times;</button>
        </div>

        <form method="POST" action="" id="bannerForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="bannerId">

            <div class="form-group">
                <label>Page Location *</label>
                <select name="page_location" id="page_location" class="form-control" required>
                    <optgroup label="Main Pages">
                        <option value="homepage">Homepage</option>
                        <option value="about">About Page</option>
                        <option value="sermons">Sermons Page</option>
                        <option value="events">Events Page</option>
                        <option value="contact">Contact Page</option>
                    </optgroup>
                    <optgroup label="Community Pages">
                        <option value="communities">All Community Pages</option>
                        <option value="audio-visual-team">Audio Visual Team</option>
                        <option value="choir">Choir</option>
                        <option value="israel">Israel</option>
                        <option value="junior-youth">Junior Youth</option>
                        <option value="youth-fellowship">Youth Fellowship</option>
                        <option value="womens-fellowship">Women's Fellowship</option>
                        <option value="mens-fellowship">Men's Fellowship</option>
                        <option value="children-service">Children Service</option>
                        <option value="singing-band">Singing Band</option>
                        <option value="ark-of-praise">Ark of Praise</option>
                    </optgroup>
                </select>
            </div>

            <div class="form-group">
                <label>Banner Text *</label>
                <textarea name="banner_text" id="banner_text" class="form-control" rows="3" required placeholder="Enter your banner message..."></textarea>
                <small style="color: #666;">Keep it concise for better readability</small>
            </div>

            <div class="form-group">
                <label>Link URL (Optional)</label>
                <input type="url" name="link_url" id="link_url" class="form-control" placeholder="https://example.com">
                <small style="color: #666;">If provided, banner will be clickable</small>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Text Color</label>
                    <input type="color" name="text_color" id="text_color" class="form-control" value="#ffffff">
                </div>

                <div class="form-group">
                    <label>Background Color</label>
                    <input type="color" name="background_color" id="background_color" class="form-control" value="#5DADEC">
                </div>

                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" id="display_order" class="form-control" value="1" min="0">
                </div>
            </div>

            <div class="form-group" id="statusGroup" style="display: none;">
                <label>Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="form-group">
                <label>Preview</label>
                <div id="bannerPreview" style="background: #5DADEC; color: #ffffff; padding: 15px; text-align: center; border-radius: 5px;">
                    Your banner text will appear here...
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Banner
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

.banner-tabs {
    display: flex;
    gap: 10px;
    border-bottom: 2px solid #e0e0e0;
}

.tab-btn {
    padding: 8px 16px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    transition: all 0.3s;
    font-size: 0.9rem;
    white-space: nowrap;
}

.tab-btn:hover {
    color: #5DADEC;
}

.tab-btn.active {
    color: #5DADEC;
    border-bottom-color: #5DADEC;
}

.tab-content {
    display: none;
    padding: 20px 0;
}

.tab-content.active {
    display: block;
}
</style>

<script>
let currentTab = 'homepage';

function showTab(tab) {
    currentTab = tab;
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

    event.target.classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
}

function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New Banner';
    document.getElementById('formAction').value = 'add';
    document.getElementById('bannerForm').reset();
    document.getElementById('page_location').value = currentTab;
    document.getElementById('statusGroup').style.display = 'none';
    updatePreview();
    document.getElementById('bannerModal').style.display = 'block';
}

function editBanner(banner) {
    document.getElementById('modalTitle').textContent = 'Edit Banner';
    document.getElementById('formAction').value = 'update';
    document.getElementById('bannerId').value = banner.id;
    document.getElementById('page_location').value = banner.page_location;
    document.getElementById('banner_text').value = banner.banner_text;
    document.getElementById('link_url').value = banner.link_url || '';
    document.getElementById('text_color').value = banner.text_color;
    document.getElementById('background_color').value = banner.background_color;
    document.getElementById('display_order').value = banner.display_order;
    document.getElementById('status').value = banner.status;
    document.getElementById('statusGroup').style.display = 'block';
    updatePreview();
    document.getElementById('bannerModal').style.display = 'block';
}

function deleteBanner(id) {
    if (!confirm('Are you sure you want to delete this banner? This action cannot be undone.')) {
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
    document.getElementById('bannerModal').style.display = 'none';
}

function updatePreview() {
    const text = document.getElementById('banner_text').value || 'Your banner text will appear here...';
    const textColor = document.getElementById('text_color').value;
    const bgColor = document.getElementById('background_color').value;
    const preview = document.getElementById('bannerPreview');

    preview.textContent = text;
    preview.style.color = textColor;
    preview.style.background = bgColor;
}

// Update preview on input change
document.getElementById('banner_text').addEventListener('input', updatePreview);
document.getElementById('text_color').addEventListener('input', updatePreview);
document.getElementById('background_color').addEventListener('input', updatePreview);

// Close modal when clicking outside
document.getElementById('bannerModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
