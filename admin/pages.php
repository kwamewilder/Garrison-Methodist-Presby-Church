<?php
$pageTitle = 'Manage Pages';
require_once 'includes/header.php';

$conn = getDBConnection();
$success = '';
$error = '';
$targetSlug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        $content_json = $_POST['content_json'] ?? '';

        if ($action === 'create') {
            if (!hasRole('admin')) {
                $error = 'Only admins can create pages.';
            }

            $title = sanitizeInput($_POST['title']);
            $page_name = $title; // Use title as page_name
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $title)));
            $content = $content_json ?: $_POST['content'];
            $meta_description = sanitizeInput($_POST['meta_description']);
            $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
            $menu_order = (int)$_POST['menu_order'];

            if (!$error) {
                try {
                    $stmt = $conn->prepare("INSERT INTO pages (page_name, slug, title, content, meta_description, show_in_menu, menu_order, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)");
                    $stmt->execute([$page_name, $slug, $title, $content, $meta_description, $show_in_menu, $menu_order, getCurrentAdmin()['id']]);
                    $newId = $conn->lastInsertId();
                    log_audit('create', 'page', $newId, "Created page {$title}");
                    $success = 'Page created successfully!';
                } catch(PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = 'A page with this slug already exists.';
                    } else {
                        $error = 'Error creating page.';
                    }
                    error_log($e->getMessage());
                }
            }
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            requirePagePermission($id);
            $title = sanitizeInput($_POST['title']);
            $page_name = $title; // Use title as page_name
            $content = $content_json ?: $_POST['content'];
            $meta_description = sanitizeInput($_POST['meta_description']);
            $show_in_menu = isset($_POST['show_in_menu']) ? 1 : 0;
            $menu_order = (int)$_POST['menu_order'];

            try {
                $stmt = $conn->prepare("UPDATE pages SET page_name = ?, title = ?, content = ?, meta_description = ?, show_in_menu = ?, menu_order = ? WHERE id = ?");
                $stmt->execute([$page_name, $title, $content, $meta_description, $show_in_menu, $menu_order, $id]);
                log_audit('update', 'page', $id, "Updated page {$title}");
                $success = 'Page updated successfully!';
            } catch(PDOException $e) {
                $error = 'Error updating page.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'archive') {
            $id = (int)$_POST['id'];
            requirePagePermission($id);
            try {
                $stmt = $conn->prepare("UPDATE pages SET status = 'inactive' WHERE id = ?");
                $stmt->execute([$id]);
                log_audit('archive', 'page', $id, 'Archived page');
                $success = 'Page archived successfully!';
            } catch(PDOException $e) {
                $error = 'Error archiving page.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'restore') {
            $id = (int)$_POST['id'];
            requirePagePermission($id);
            try {
                $stmt = $conn->prepare("UPDATE pages SET status = 'active' WHERE id = ?");
                $stmt->execute([$id]);
                log_audit('restore', 'page', $id, 'Restored page');
                $success = 'Page restored successfully!';
            } catch(PDOException $e) {
                $error = 'Error restoring page.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            requirePagePermission($id);
            try {
                $stmt = $conn->prepare("DELETE FROM pages WHERE id = ?");
                $stmt->execute([$id]);
                log_audit('delete', 'page', $id, 'Deleted page');
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

<?php if (!$targetSlug): ?>
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
                                    <?php $canEdit = canEditPage($page['id']); ?>
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
                                            <?php if ($isProtected): ?>
                                                <span class="badge badge-info">Protected</span>
                                            <?php elseif (!$canEdit): ?>
                                                <span class="badge badge-secondary">No access</span>
                                            <?php else: ?>
                                                <button onclick="editPage(<?php echo $page['id']; ?>)" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button onclick="archivePage(<?php echo $page['id']; ?>, '<?php echo htmlspecialchars($page['title']); ?>')" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-archive"></i> Archive
                                                </button>
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
                                    <?php $canEdit = canEditPage($page['id']); ?>
                                    <tr style="opacity: 0.7;">
                                        <td><strong><?php echo htmlspecialchars($page['title']); ?></strong></td>
                                        <td><code><?php echo htmlspecialchars($page['slug']); ?></code></td>
                                        <td><?php echo date('M d, Y', strtotime($page['updated_at'])); ?></td>
                                        <td>
                                            <?php if ($canEdit): ?>
                                                <button onclick="restorePage(<?php echo $page['id']; ?>, '<?php echo htmlspecialchars($page['title']); ?>')" class="btn btn-sm btn-success">
                                                    <i class="fas fa-undo"></i> Restore
                                                </button>
                                                <button onclick="deletePage(<?php echo $page['id']; ?>, '<?php echo htmlspecialchars($page['title']); ?>')" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i> Delete Permanently
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
                    <p style="text-align: center; color: #999; padding: 40px;">No archived pages.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

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
                <div class="block-editor">
                    <div class="block-editor-toolbar">
                        <div class="toolbar-left">
                            <span class="badge badge-soft">Drag & reorder blocks</span>
                        </div>
                        <div class="toolbar-right">
                            <select id="pageBlockTypeSelect" class="form-control" style="width: 200px;">
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
                                <option value="sermon">Sermon</option>
                                <option value="location">Location / Map</option>
                                <option value="divider">Divider</option>
                                <option value="spacer">Spacer</option>
                            </select>
                            <button type="button" class="btn btn-primary" id="pageAddBlockBtn"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </div>

                    <div class="block-canvas" id="pageBlockCanvas">
                        <!-- blocks injected by JS -->
                    </div>
                </div>
                <input type="hidden" name="content_json" id="page_content_json">
                <textarea name="content" id="page_content" class="form-control" rows="6" style="display:none;"></textarea>
                <small style="color: #666;">Build with blocks. Drag to reorder, click to edit, upload images with previews.</small>
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
const targetSlug = "<?php echo $targetSlug; ?>";
const pageBlockCanvas = document.getElementById('pageBlockCanvas');
const pageContentJsonField = document.getElementById('page_content_json');
const pageAddBlockBtn = document.getElementById('pageAddBlockBtn');
const pageBlockTypeSelect = document.getElementById('pageBlockTypeSelect');
let pageBlocks = [];

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
    pageBlocks = [
        { type: 'heading', data: { level: 2, text: 'Page heading' } },
        { type: 'paragraph', data: { text: 'Start writing your content...' } }
    ];
    renderPageBlocks();
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
    hydratePageBlocks(page.content || '');
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

// Auto-open editor if slug is provided in query
if (targetSlug) {
    const targetPage = [...activePages, ...archivedPages].find(p => p.slug === targetSlug);
    if (targetPage) {
        editPage(targetPage.id);
    }
}

// Close modal when clicking outside
document.getElementById('pageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

const pageDefaultBlockData = {
    heading: { level: 2, text: 'Section heading', align: 'left' },
    paragraph: { text: 'Start writing your content here...', align: 'left' },
    list: { style: 'unordered', items: ['List item 1', 'List item 2'], align: 'left' },
    quote: { text: 'A meaningful quote goes here.', caption: 'Author', align: 'left' },
    image: { url: '', caption: '' },
    video: { url: '', caption: '' },
    button: { text: 'Learn More', url: '#', style: 'primary' },
    scripture: { reference: 'John 3:16', text: 'For God so loved the world...', version: 'NIV' },
    event: { title: 'Upcoming Event', date: '', time: '', location: '', description: '' },
    sermon: { title: 'Sermon Title', preacher: 'Preacher Name', date: '', audioUrl: '', videoUrl: '', description: '' },
    location: { address: '123 Church St, City', mapUrl: '' },
    divider: {},
    spacer: { height: 24 },
};

function hydratePageBlocks(raw) {
    try {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
            pageBlocks = parsed;
        } else if (parsed && Array.isArray(parsed.blocks)) {
            pageBlocks = parsed.blocks;
        } else {
            pageBlocks = [];
        }
    } catch (e) {
        pageBlocks = raw ? [{ type: 'paragraph', data: { text: raw } }] : [];
    }
    renderPageBlocks();
}

function addPageBlock(type) {
    if (!type) return;
    const clone = JSON.parse(JSON.stringify(pageDefaultBlockData[type] || {}));
    pageBlocks.push({ type, data: clone });
    renderPageBlocks();
    pageBlockTypeSelect.value = '';
}

function renderPageBlocks() {
    pageBlockCanvas.innerHTML = '';
    pageBlocks.forEach((block, index) => {
        const card = document.createElement('div');
        card.className = 'block-card';
        card.draggable = true;
        card.dataset.index = index;
        card.addEventListener('dragstart', onPageDragStart);
        card.addEventListener('dragover', onPageDragOver);
        card.addEventListener('drop', onPageDrop);

        const handle = document.createElement('div');
        handle.className = 'block-handle';
        handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';

        const content = document.createElement('div');
        content.className = 'block-content';
        const title = document.createElement('div');
        title.className = 'block-title';
        title.innerHTML = `<span class="block-tag">${block.type}</span>`;
        content.appendChild(title);
        content.appendChild(renderPageBlockFields(block, index));

        const actions = document.createElement('div');
        actions.className = 'block-actions';
        actions.innerHTML = `
            <button type="button" aria-label="Move up" onclick="movePageBlock(${index}, -1)"><i class="fas fa-arrow-up"></i></button>
            <button type="button" aria-label="Move down" onclick="movePageBlock(${index}, 1)"><i class="fas fa-arrow-down"></i></button>
            <button type="button" aria-label="Duplicate" onclick="duplicatePageBlock(${index})"><i class="fas fa-copy"></i></button>
            <button type="button" aria-label="Delete" onclick="deletePageBlock(${index})"><i class="fas fa-trash"></i></button>
        `;
        content.appendChild(actions);

        card.appendChild(handle);
        card.appendChild(content);
        pageBlockCanvas.appendChild(card);
    });
    syncPageJsonField();
}

function createPageAlignControl(block, index) {
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
        pageBlocks[index].data.align = e.target.value;
        syncPageJsonField();
    });
    wrap.append(label, select);
    return wrap;
}

function renderPageBlockFields(block, index) {
    const wrapper = document.createElement('div');
    wrapper.className = 'block-fields';
    const bindInput = (input, path) => {
        input.addEventListener('input', (e) => {
            setPageNested(pageBlocks[index], path, e.target.value);
            syncPageJsonField();
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
            wrapper.append(level, text, createPageAlignControl(block, index));
            break;
        }
        case 'paragraph': {
            const area = document.createElement('textarea');
            area.className = 'block-textarea';
            area.placeholder = 'Rich text (plain/Markdown style)';
            area.value = block.data.text || '';
            bindInput(area, ['data','text']);
            wrapper.append(area, createPageAlignControl(block, index));
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
            area.value = (block.data.items || []).join('\n');
            area.addEventListener('input', (e) => {
                pageBlocks[index].data.items = e.target.value.split('\n').filter(Boolean);
                syncPageJsonField();
            });
            wrapper.append(style, area, createPageAlignControl(block, index));
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
            caption.placeholder = 'Caption / author';
            caption.value = block.data.caption || '';
            bindInput(caption, ['data','caption']);
            wrapper.append(quote, caption, createPageAlignControl(block, index));
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
            file.addEventListener('change', (e) => handlePageMediaUpload(e, index));
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
            file.addEventListener('change', (e) => handlePageMediaUpload(e, index));
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
                pageBlocks[index].data.height = Number(e.target.value || 0);
                syncPageJsonField();
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
        case 'sermon': {
            const title = document.createElement('input');
            title.className = 'block-input';
            title.placeholder = 'Sermon title';
            title.value = block.data.title || '';
            bindInput(title, ['data','title']);
            const preacher = document.createElement('input');
            preacher.className = 'block-input';
            preacher.placeholder = 'Preacher';
            preacher.value = block.data.preacher || '';
            bindInput(preacher, ['data','preacher']);
            const date = document.createElement('input');
            date.type = 'date';
            date.className = 'block-input';
            date.value = block.data.date || '';
            bindInput(date, ['data','date']);
            const audio = document.createElement('input');
            audio.className = 'block-input';
            audio.placeholder = 'Audio URL (optional)';
            audio.value = block.data.audioUrl || '';
            bindInput(audio, ['data','audioUrl']);
            const video = document.createElement('input');
            video.className = 'block-input';
            video.placeholder = 'Video URL (optional)';
            video.value = block.data.videoUrl || '';
            bindInput(video, ['data','videoUrl']);
            const desc = document.createElement('textarea');
            desc.className = 'block-textarea';
            desc.placeholder = 'Description';
            desc.value = block.data.description || '';
            bindInput(desc, ['data','description']);
            wrapper.append(title, preacher, date, audio, video, desc);
            break;
        }
        default:
            wrapper.textContent = 'Unknown block';
    }
    return wrapper;
}

function setPageNested(obj, path, value) {
    let target = obj;
    for (let i = 0; i < path.length - 1; i++) {
        const key = path[i];
        if (!target[key]) target[key] = {};
        target = target[key];
    }
    target[path[path.length - 1]] = value;
}

function movePageBlock(index, delta) {
    const newIndex = index + delta;
    if (newIndex < 0 || newIndex >= pageBlocks.length) return;
    const [moved] = pageBlocks.splice(index, 1);
    pageBlocks.splice(newIndex, 0, moved);
    renderPageBlocks();
}

function deletePageBlock(index) {
    pageBlocks.splice(index, 1);
    renderPageBlocks();
}

function duplicatePageBlock(index) {
    const clone = JSON.parse(JSON.stringify(pageBlocks[index]));
    pageBlocks.splice(index + 1, 0, clone);
    renderPageBlocks();
}

function handlePageMediaUpload(event, index) {
    const file = event.target.files[0];
    if (!file) return;
    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

    fetch('api/upload.php', {
        method: 'POST',
        body: formData
    })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.file && data.file.url) {
                pageBlocks[index].data.url = data.file.url;
                renderPageBlocks();
            } else {
                alert('Upload failed');
            }
        })
        .catch(() => alert('Upload failed'));
}

function onPageDragStart(e) {
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.index);
    e.dropEffect = 'move';
}

function onPageDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function onPageDrop(e) {
    e.preventDefault();
    const from = Number(e.dataTransfer.getData('text/plain'));
    const to = Number(e.currentTarget.dataset.index);
    if (isNaN(from) || isNaN(to) || from === to) return;
    const [moved] = pageBlocks.splice(from, 1);
    pageBlocks.splice(to, 0, moved);
    renderPageBlocks();
}

function syncPageJsonField() {
    pageContentJsonField.value = JSON.stringify(pageBlocks);
}

pageAddBlockBtn.addEventListener('click', () => addPageBlock(pageBlockTypeSelect.value));

document.getElementById('pageForm').addEventListener('submit', () => {
    syncPageJsonField();
});

// Initialize
renderPageBlocks();
</script>

<?php require_once 'includes/footer.php'; ?>
