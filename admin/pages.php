<?php
$pageTitle = 'Manage Pages';
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
            $page_name = $title; // Use title as page_name
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $title)));
            $content = $_POST['content'];
            $meta_description = sanitizeInput($_POST['meta_description']);
            $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
            $menu_order = (int)$_POST['menu_order'];

            try {
                $stmt = $conn->prepare("INSERT INTO pages (page_name, slug, title, content, meta_description, show_in_menu, menu_order, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)");
                $stmt->execute([$page_name, $slug, $title, $content, $meta_description, $show_in_menu, $menu_order, getCurrentAdmin()['id']]);
                $success = 'Page created successfully!';
            } catch(PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'A page with this slug already exists.';
                } else {
                    $error = 'Error creating page.';
                }
                error_log($e->getMessage());
            }
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $title = sanitizeInput($_POST['title']);
            $page_name = $title; // Use title as page_name
            $content = $_POST['content'];
            $meta_description = sanitizeInput($_POST['meta_description']);
            $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
            $menu_order = (int)$_POST['menu_order'];

            try {
                $stmt = $conn->prepare("UPDATE pages SET page_name = ?, title = ?, content = ?, meta_description = ?, show_in_menu = ?, menu_order = ? WHERE id = ?");
                $stmt->execute([$page_name, $title, $content, $meta_description, $show_in_menu, $menu_order, $id]);
                $success = 'Page updated successfully!';
            } catch(PDOException $e) {
                $error = 'Error updating page.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'archive') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("UPDATE pages SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Page archived successfully!';
            } catch(PDOException $e) {
                $error = 'Error archiving page.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'restore') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("UPDATE pages SET status = 'active' WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Page restored successfully!';
            } catch(PDOException $e) {
                $error = 'Error restoring page.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            try {
                $stmt = $conn->prepare("DELETE FROM pages WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Page deleted permanently!';
            } catch(PDOException $e) {
                $error = 'Error deleting page.';
                error_log($e->getMessage());
            }
        }
    }
}

// Get active pages
try {
    $stmt = $conn->query("SELECT * FROM pages WHERE status = 'active' ORDER BY menu_order ASC, created_at DESC");
    $activePages = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Error loading pages.';
    error_log($e->getMessage());
}

// Get archived pages
try {
    $stmt = $conn->query("SELECT * FROM pages WHERE status = 'inactive' ORDER BY updated_at DESC");
    $archivedPages = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log($e->getMessage());
}

// Protected pages that cannot be edited or archived
$protectedSlugs = ['index', 'home', 'homepage'];
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
        <h2><i class="fas fa-file-alt"></i> Manage Pages</h2>
        <button onclick="showCreateModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Page
        </button>
    </div>
    <div class="card-body">
        <p style="margin-bottom: 20px; color: #666;">
            <i class="fas fa-info-circle"></i> Create custom pages for your website. Pages marked "Show in Menu" will appear in the navigation.
        </p>

        <!-- Tabs -->
        <div class="page-tabs" style="margin-bottom: 30px; display: flex; gap: 10px; border-bottom: 2px solid #e0e0e0;">
            <button class="page-tab-btn active" onclick="showPageTab('active')">Active Pages (<?php echo count($activePages); ?>)</button>
            <button class="page-tab-btn" onclick="showPageTab('archived')">Archived (<?php echo count($archivedPages); ?>)</button>
        </div>

        <!-- Active Pages -->
        <div id="tab-active-pages" class="page-tab-content active">
            <?php if (count($activePages) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Slug</th>
                                <th>In Menu</th>
                                <th>Menu Order</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activePages as $page): ?>
                                <?php $isProtected = in_array($page['slug'], $protectedSlugs); ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($page['title']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($page['slug']); ?></code></td>
                                    <td>
                                        <?php if ($page['show_in_menu']): ?>
                                            <span class="badge badge-success">Yes</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $page['menu_order']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($page['created_at'])); ?></td>
                                    <td>
                                        <?php if (!$isProtected): ?>
                                            <button onclick="editPage(<?php echo $page['id']; ?>)" class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button onclick="archivePage(<?php echo $page['id']; ?>, '<?php echo htmlspecialchars($page['title']); ?>')" class="btn btn-sm btn-warning">
                                                <i class="fas fa-archive"></i> Archive
                                            </button>
                                        <?php else: ?>
                                            <span class="badge badge-info">Protected</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No active pages yet. Click "Create New Page" to add one.</p>
            <?php endif; ?>
        </div>

        <!-- Archived Pages -->
        <div id="tab-archived-pages" class="page-tab-content">
            <?php if (count($archivedPages) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Slug</th>
                                <th>Archived On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archivedPages as $page): ?>
                                <tr style="opacity: 0.7;">
                                    <td><strong><?php echo htmlspecialchars($page['title']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($page['slug']); ?></code></td>
                                    <td><?php echo date('M d, Y', strtotime($page['updated_at'])); ?></td>
                                    <td>
                                        <button onclick="restorePage(<?php echo $page['id']; ?>, '<?php echo htmlspecialchars($page['title']); ?>')" class="btn btn-sm btn-success">
                                            <i class="fas fa-undo"></i> Restore
                                        </button>
                                        <button onclick="deletePage(<?php echo $page['id']; ?>, '<?php echo htmlspecialchars($page['title']); ?>')" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete Permanently
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px;">No archived pages.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="pageModal" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 900px;">
        <div class="modal-header">
            <h2 id="modalTitle">Create New Page</h2>
            <button onclick="closeModal()" class="modal-close">&times;</button>
        </div>

        <form method="POST" action="" id="pageForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="id" id="page_id">

            <div class="form-group">
                <label>Page Title *</label>
                <input type="text" name="title" id="page_title" class="form-control" required>
                <small style="color: #666;">This will appear in the browser tab and menu</small>
            </div>

            <div class="form-group">
                <label>Page Content *</label>
                <textarea name="content" id="page_content" class="form-control" rows="12" required></textarea>
                <small style="color: #666;">Full HTML content of the page. You can use HTML tags for formatting.</small>
            </div>

            <div class="form-group">
                <label>Meta Description</label>
                <textarea name="meta_description" id="page_meta_description" class="form-control" rows="2"></textarea>
                <small style="color: #666;">Brief description for search engines (recommended: 150-160 characters)</small>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="show_in_menu" id="page_show_in_menu" style="width: 20px; height: 20px;">
                        <span>Show this page in the navigation menu</span>
                    </label>
                </div>

                <div class="form-group">
                    <label>Menu Order</label>
                    <input type="number" name="menu_order" id="page_menu_order" class="form-control" value="0" min="0">
                    <small style="color: #666;">Lower numbers appear first</small>
                </div>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <span id="submitBtnText">Create Page</span>
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

.page-tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #666;
    transition: all 0.3s;
}

.page-tab-btn:hover {
    color: #5DADEC;
}

.page-tab-btn.active {
    color: #5DADEC;
    border-bottom-color: #5DADEC;
}

.page-tab-content {
    display: none;
}

.page-tab-content.active {
    display: block;
}
</style>

<script>
const activePages = <?php echo json_encode($activePages); ?>;
const archivedPages = <?php echo json_encode($archivedPages); ?>;
const protectedSlugs = <?php echo json_encode($protectedSlugs); ?>;

function showPageTab(tab) {
    document.querySelectorAll('.page-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.page-tab-content').forEach(content => content.classList.remove('active'));

    event.target.classList.add('active');
    document.getElementById('tab-' + tab + '-pages').classList.add('active');
}

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create New Page';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitBtnText').textContent = 'Create Page';
    document.getElementById('pageForm').reset();
    document.getElementById('pageModal').style.display = 'block';
}

function editPage(id) {
    const page = [...activePages, ...archivedPages].find(p => p.id == id);
    if (!page) return;

    if (protectedSlugs.includes(page.slug)) {
        alert('This page is protected and cannot be edited.');
        return;
    }

    document.getElementById('modalTitle').textContent = 'Edit Page';
    document.getElementById('formAction').value = 'update';
    document.getElementById('submitBtnText').textContent = 'Save Changes';
    document.getElementById('page_id').value = page.id;
    document.getElementById('page_title').value = page.title;
    document.getElementById('page_content').value = page.content || '';
    document.getElementById('page_meta_description').value = page.meta_description || '';
    document.getElementById('page_show_in_menu').checked = page.show_in_menu == 1;
    document.getElementById('page_menu_order').value = page.menu_order;

    document.getElementById('pageModal').style.display = 'block';
}

function archivePage(id, title) {
    if (!confirm(`Archive "${title}"? You can restore it later from the Archived tab.`)) {
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

function restorePage(id, title) {
    if (!confirm(`Restore "${title}" to active pages?`)) {
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

function deletePage(id, title) {
    if (!confirm(`PERMANENTLY DELETE "${title}"? This action cannot be undone!`)) {
        return;
    }

    if (!confirm(`Are you absolutely sure? This will permanently remove "${title}".`)) {
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
    document.getElementById('pageModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('pageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
