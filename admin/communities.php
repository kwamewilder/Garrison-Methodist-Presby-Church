<?php
$pageTitle = 'Manage Communities';
require_once 'includes/header.php';

$conn = getDBConnection();
$success = '';
$error = '';

/**
 * Allow rich text while stripping dangerous content.
 */
function cleanRichText($html) {
    // Remove script/style tags outright
    $html = preg_replace('#<(script|style)[^>]*>.*?</\\1>#is', '', $html);
    // Allow a curated set of tags similar to a basic CMS editor
    $allowed = '<p><br><h1><h2><h3><h4><h5><h6><strong><em><b><i><u><blockquote><ul><ol><li><a><span><div><img><figure><figcaption><hr><small><table><thead><tbody><tr><th><td>';
    $html = strip_tags($html, $allowed);
    // Strip event handlers and javascript: URLs
    $html = preg_replace('/on[a-zA-Z]+\\s*=\\s*"[^"]*"/i', '', $html);
    $html = preg_replace("/on[a-zA-Z]+\\s*=\\s*'[^']*'/i", '', $html);
    $html = preg_replace('/javascript:/i', '', $html);
    return trim($html);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        $full_content_json = $_POST['full_content_json'] ?? '';

        if ($action === 'create') {
            $name = sanitizeInput($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $name)));
            $description = sanitizeInput($_POST['description'] ?? '');
            $full_content = $full_content_json ?: cleanRichText($_POST['full_content'] ?? '');
            $meeting_day = '';
            $meeting_time = '';
            $meeting_location = '';
            $contact_person = '';
            $contact_email = '';

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
            $description = sanitizeInput($_POST['description'] ?? '');
            $full_content = $full_content_json ?: cleanRichText($_POST['full_content'] ?? '');
            $meeting_day = '';
            $meeting_time = '';
            $meeting_location = '';
            $contact_person = '';
            $contact_email = '';

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
                                <th>Media</th>
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
                                    <td>
                                        <a class="btn btn-sm btn-primary" href="community_media.php?community=<?php echo urlencode($community['slug']); ?>">
                                            <i class="fas fa-photo-video"></i> Manage Media
                                        </a>
                                    </td>
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
            <input type="hidden" name="description" id="community_description">

            <div class="form-group">
                <label>Community Name *</label>
                <input type="text" name="name" id="community_name" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Block Editor</label>
                <div class="block-editor">
                    <div class="block-editor-toolbar">
                        <div class="toolbar-left">
                            <span class="badge badge-soft">Drag & reorder blocks</span>
                        </div>
                        <div class="toolbar-right">
                            <select id="blockTypeSelect" class="form-control" style="width: 180px;">
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
                            <button type="button" class="btn btn-primary" id="addBlockBtn"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </div>

                    <div class="block-canvas" id="blockCanvas">
                        <!-- blocks injected by JS -->
                    </div>
                </div>
                <input type="hidden" name="full_content_json" id="full_content_json">
                <small style="color: #666;">Use blocks to build the page. Drag to reorder, click text to edit, and upload images with previews.</small>
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

.rich-editor {
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
    background: #fafafa;
}

.rich-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    padding: 10px;
    background: #f1f4f8;
    border-bottom: 1px solid #e0e0e0;
}

.rich-toolbar button {
    border: 1px solid #d0d7e2;
    background: white;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
    color: #333;
    transition: all 0.2s ease;
    font-weight: 600;
}

.rich-toolbar button:hover {
    background: #e8f3ff;
    border-color: #bcd7ff;
    color: #1b6bd7;
}

.toolbar-divider {
    width: 1px;
    background: #dce1e9;
    margin: 0 6px;
}

.rich-editor-area {
    min-height: 220px;
    padding: 14px;
    background: white;
    line-height: 1.6;
}

.rich-editor-area:empty:before {
    content: attr(data-placeholder);
    color: #999;
}

.rich-editor-area img {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 10px 0;
}

.block-editor {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 18px 45px rgba(10, 10, 51, 0.08);
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

.block-tag {
    display: inline-block;
    padding: 2px 8px;
    background: #e2e8f0;
    border-radius: 999px;
    font-size: 0.85rem;
    color: #475569;
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
</style>

<script>
const activeCommunities = <?php echo json_encode($activeCommunities); ?>;
const archivedCommunities = <?php echo json_encode($archivedCommunities); ?>;
const blockCanvas = document.getElementById('blockCanvas');
const fullContentJsonField = document.getElementById('full_content_json');
const addBlockBtn = document.getElementById('addBlockBtn');
const blockTypeSelect = document.getElementById('blockTypeSelect');

let blocks = [];

const defaultBlockData = {
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
    document.getElementById('community_description').value = '';
    blocks = [
        { type: 'heading', data: { level: 2, text: 'Welcome to our community' } },
        { type: 'paragraph', data: { text: 'Use this space to tell members what your community is about.' } }
    ];
    renderBlocks();
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
    document.getElementById('community_description').value = community.description || '';
    hydrateBlocksFromSaved(community.full_content || '');

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

function addBlock(type) {
    if (!type) return;
    const clone = JSON.parse(JSON.stringify(defaultBlockData[type] || {}));
    blocks.push({ type, data: clone });
    renderBlocks();
    blockTypeSelect.value = '';
}

function hydrateBlocksFromSaved(raw) {
    try {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
            blocks = parsed;
        } else if (parsed && Array.isArray(parsed.blocks)) {
            blocks = parsed.blocks;
        } else {
            blocks = [];
        }
    } catch (e) {
        // fallback: treat legacy HTML as paragraph
        blocks = raw ? [{ type: 'paragraph', data: { text: raw } }] : [];
    }
    renderBlocks();
}

function renderBlocks() {
    blockCanvas.innerHTML = '';
    blocks.forEach((block, index) => {
        const card = document.createElement('div');
        card.className = 'block-card';
        card.draggable = true;
        card.dataset.index = index;

        card.addEventListener('dragstart', onDragStart);
        card.addEventListener('dragover', onDragOver);
        card.addEventListener('drop', onDrop);

        const handle = document.createElement('div');
        handle.className = 'block-handle';
        handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';

        const content = document.createElement('div');
        content.className = 'block-content';

        const title = document.createElement('div');
        title.className = 'block-title';
        title.innerHTML = `<span class="block-tag">${block.type}</span>`;
        content.appendChild(title);

        const fields = renderBlockFields(block, index);
        content.appendChild(fields);

        const actions = document.createElement('div');
        actions.className = 'block-actions';
        actions.innerHTML = `
            <button type="button" aria-label="Move up" onclick="moveBlock(${index}, -1)"><i class="fas fa-arrow-up"></i></button>
            <button type="button" aria-label="Move down" onclick="moveBlock(${index}, 1)"><i class="fas fa-arrow-down"></i></button>
            <button type="button" aria-label="Duplicate" onclick="duplicateBlock(${index})"><i class="fas fa-copy"></i></button>
            <button type="button" aria-label="Delete" onclick="deleteBlock(${index})"><i class="fas fa-trash"></i></button>
        `;
        content.appendChild(actions);

        card.appendChild(handle);
        card.appendChild(content);
        blockCanvas.appendChild(card);
    });

    syncJsonField();
}

function createAlignControl(block, index) {
    const alignWrap = document.createElement('div');
    alignWrap.className = 'block-input';
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
        blocks[index].data.align = e.target.value;
        syncJsonField();
    });
    alignWrap.append(label, select);
    return alignWrap;
}

function renderBlockFields(block, index) {
    const wrapper = document.createElement('div');
    wrapper.className = 'block-fields';

    const bindInput = (input, path) => {
        input.addEventListener('input', (e) => {
            setNested(blocks[index], path, e.target.value);
            if (input.type === 'file') return;
            syncJsonField();
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
            wrapper.append(level, text, createAlignControl(block, index));
            break;
        }
        case 'paragraph': {
            const area = document.createElement('textarea');
            area.className = 'block-textarea';
            area.placeholder = 'Rich text (Markdown-style or plain text)';
            area.value = block.data.text || '';
            bindInput(area, ['data','text']);
            wrapper.append(area, createAlignControl(block, index));
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
                blocks[index].data.items = e.target.value.split('\n').filter(Boolean);
                syncJsonField();
            });
            wrapper.append(style, area, createAlignControl(block, index));
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
            wrapper.append(quote, caption, createAlignControl(block, index));
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
            file.addEventListener('change', (e) => handleMediaUpload(e, index));

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
            file.addEventListener('change', (e) => handleMediaUpload(e, index));

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
                blocks[index].data.height = Number(e.target.value || 0);
                syncJsonField();
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

function setNested(obj, path, value) {
    let target = obj;
    for (let i = 0; i < path.length - 1; i++) {
        const key = path[i];
        if (!target[key]) target[key] = {};
        target = target[key];
    }
    target[path[path.length - 1]] = value;
}

function moveBlock(index, delta) {
    const newIndex = index + delta;
    if (newIndex < 0 || newIndex >= blocks.length) return;
    const [moved] = blocks.splice(index, 1);
    blocks.splice(newIndex, 0, moved);
    renderBlocks();
}

function deleteBlock(index) {
    blocks.splice(index, 1);
    renderBlocks();
}

function duplicateBlock(index) {
    const clone = JSON.parse(JSON.stringify(blocks[index]));
    blocks.splice(index + 1, 0, clone);
    renderBlocks();
}

function handleMediaUpload(event, index) {
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
                blocks[index].data.url = data.file.url;
                renderBlocks();
            } else {
                alert('Upload failed');
            }
        })
        .catch(() => alert('Upload failed'));
}

function onDragStart(e) {
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.index);
    e.dropEffect = 'move';
}

function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function onDrop(e) {
    e.preventDefault();
    const from = Number(e.dataTransfer.getData('text/plain'));
    const to = Number(e.currentTarget.dataset.index);
    if (isNaN(from) || isNaN(to) || from === to) return;
    const [moved] = blocks.splice(from, 1);
    blocks.splice(to, 0, moved);
    renderBlocks();
}

function syncJsonField() {
    fullContentJsonField.value = JSON.stringify(blocks);
}

addBlockBtn.addEventListener('click', () => addBlock(blockTypeSelect.value));

document.getElementById('communityForm').addEventListener('submit', () => {
    syncJsonField();
});

// Initialize with empty blocks
renderBlocks();
</script>

<?php require_once 'includes/footer.php'; ?>
