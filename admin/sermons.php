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
            $description = $_POST['content_json'] ?? sanitizeInput($_POST['description']);
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
            $description = $_POST['content_json'] ?? sanitizeInput($_POST['description']);
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
                <label>Sermon Content</label>
                <div class="block-editor">
                    <div class="block-editor-toolbar">
                        <div class="toolbar-left">
                            <span class="badge badge-soft">Drag & reorder blocks</span>
                        </div>
                        <div class="toolbar-right">
                            <select id="sermonBlockTypeSelect" class="form-control" style="width: 200px;">
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
                            <button type="button" class="btn btn-primary" id="sermonAddBlockBtn"><i class="fas fa-plus"></i> Add</button>
                        </div>
                    </div>

                    <div class="block-canvas" id="sermonBlockCanvas"></div>
                </div>
                <input type="hidden" name="content_json" id="sermon_content_json">
                <textarea name="description" id="sermon_description" class="form-control" rows="3" style="display:none;"></textarea>
                <small style="color: #666;">Build your sermon summary/notes with blocks.</small>
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
</style>

<script>
const allSermons = <?php echo json_encode($sermons); ?>;
const sermonBlockCanvas = document.getElementById('sermonBlockCanvas');
const sermonContentJsonField = document.getElementById('sermon_content_json');
const sermonAddBlockBtn = document.getElementById('sermonAddBlockBtn');
const sermonBlockTypeSelect = document.getElementById('sermonBlockTypeSelect');
let sermonBlocks = [];
const sermonDefaultBlockData = {
    heading: { level: 2, text: 'Sermon heading', align: 'left' },
    paragraph: { text: 'Add your sermon notes here...', align: 'left' },
    list: { style: 'unordered', items: ['Point 1', 'Point 2'], align: 'left' },
    quote: { text: 'Key quote', caption: '', align: 'left' },
    image: { url: '', caption: '' },
    video: { url: '', caption: '' },
    button: { text: 'Watch Video', url: '#', style: 'primary' },
    scripture: { reference: 'John 3:16', text: 'For God so loved the world...', version: 'NIV' },
    event: { title: 'Upcoming Event', date: '', time: '', location: '', description: '' },
    sermon: { title: 'Sermon', preacher: '', date: '', audioUrl: '', videoUrl: '', description: '' },
    location: { address: '123 Church St, City', mapUrl: '' },
    divider: {},
    spacer: { height: 24 },
};

function showCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create New Sermon';
    document.getElementById('formAction').value = 'create';
    document.getElementById('submitBtnText').textContent = 'Create Sermon';
    document.getElementById('sermonForm').reset();
    sermonBlocks = [
        { type: 'heading', data: { level: 2, text: 'Sermon Title' } },
        { type: 'paragraph', data: { text: 'Add your main sermon summary here.' } }
    ];
    renderSermonBlocks();
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
    hydrateSermonBlocks(sermon.description || '');
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

function hydrateSermonBlocks(raw) {
    try {
        const parsed = JSON.parse(raw);
        if (Array.isArray(parsed)) {
            sermonBlocks = parsed;
        } else if (parsed && Array.isArray(parsed.blocks)) {
            sermonBlocks = parsed.blocks;
        } else {
            sermonBlocks = [];
        }
    } catch (e) {
        sermonBlocks = raw ? [{ type: 'paragraph', data: { text: raw } }] : [];
    }
    renderSermonBlocks();
}

function addSermonBlock(type) {
    if (!type) return;
    const clone = JSON.parse(JSON.stringify(sermonDefaultBlockData[type] || {}));
    sermonBlocks.push({ type, data: clone });
    renderSermonBlocks();
    sermonBlockTypeSelect.value = '';
}

function renderSermonBlocks() {
    sermonBlockCanvas.innerHTML = '';
    sermonBlocks.forEach((block, index) => {
        const card = document.createElement('div');
        card.className = 'block-card';
        card.draggable = true;
        card.dataset.index = index;
        card.addEventListener('dragstart', onSermonDragStart);
        card.addEventListener('dragover', onSermonDragOver);
        card.addEventListener('drop', onSermonDrop);

        const handle = document.createElement('div');
        handle.className = 'block-handle';
        handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';

        const content = document.createElement('div');
        content.className = 'block-content';
        const title = document.createElement('div');
        title.className = 'block-title';
        title.innerHTML = `<span class="block-tag">${block.type}</span>`;
        content.appendChild(title);
        content.appendChild(renderSermonBlockFields(block, index));

        const actions = document.createElement('div');
        actions.className = 'block-actions';
        actions.innerHTML = `
            <button type="button" aria-label="Move up" onclick="moveSermonBlock(${index}, -1)"><i class="fas fa-arrow-up"></i></button>
            <button type="button" aria-label="Move down" onclick="moveSermonBlock(${index}, 1)"><i class="fas fa-arrow-down"></i></button>
            <button type="button" aria-label="Duplicate" onclick="duplicateSermonBlock(${index})"><i class="fas fa-copy"></i></button>
            <button type="button" aria-label="Delete" onclick="deleteSermonBlock(${index})"><i class="fas fa-trash"></i></button>
        `;
        content.appendChild(actions);

        card.appendChild(handle);
        card.appendChild(content);
        sermonBlockCanvas.appendChild(card);
    });
    syncSermonJsonField();
}

function createSermonAlignControl(block, index) {
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
        sermonBlocks[index].data.align = e.target.value;
        syncSermonJsonField();
    });
    wrap.append(label, select);
    return wrap;
}

function renderSermonBlockFields(block, index) {
    const wrapper = document.createElement('div');
    wrapper.className = 'block-fields';
    const bindInput = (input, path) => {
        input.addEventListener('input', (e) => {
            setSermonNested(sermonBlocks[index], path, e.target.value);
            syncSermonJsonField();
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
            wrapper.append(level, text, createSermonAlignControl(block, index));
            break;
        }
        case 'paragraph': {
            const area = document.createElement('textarea');
            area.className = 'block-textarea';
            area.placeholder = 'Rich text';
            area.value = block.data.text || '';
            bindInput(area, ['data','text']);
            wrapper.append(area, createSermonAlignControl(block, index));
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
            area.value = (block.data.items || []).join('\\n');
            area.addEventListener('input', (e) => {
                sermonBlocks[index].data.items = e.target.value.split('\\n').filter(Boolean);
                syncSermonJsonField();
            });
            wrapper.append(style, area, createSermonAlignControl(block, index));
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
            caption.placeholder = 'Caption';
            caption.value = block.data.caption || '';
            bindInput(caption, ['data','caption']);
            wrapper.append(quote, caption, createSermonAlignControl(block, index));
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
            file.addEventListener('change', (e) => handleSermonMediaUpload(e, index));
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
            file.addEventListener('change', (e) => handleSermonMediaUpload(e, index));
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
                sermonBlocks[index].data.height = Number(e.target.value || 0);
                syncSermonJsonField();
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

function setSermonNested(obj, path, value) {
    let target = obj;
    for (let i = 0; i < path.length - 1; i++) {
        const key = path[i];
        if (!target[key]) target[key] = {};
        target = target[key];
    }
    target[path[path.length - 1]] = value;
}

function moveSermonBlock(index, delta) {
    const newIndex = index + delta;
    if (newIndex < 0 || newIndex >= sermonBlocks.length) return;
    const [moved] = sermonBlocks.splice(index, 1);
    sermonBlocks.splice(newIndex, 0, moved);
    renderSermonBlocks();
}

function deleteSermonBlock(index) {
    sermonBlocks.splice(index, 1);
    renderSermonBlocks();
}

function duplicateSermonBlock(index) {
    const clone = JSON.parse(JSON.stringify(sermonBlocks[index]));
    sermonBlocks.splice(index + 1, 0, clone);
    renderSermonBlocks();
}

function handleSermonMediaUpload(event, index) {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

    fetch('api/upload.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.file && data.file.url) {
                sermonBlocks[index].data.url = data.file.url;
                renderSermonBlocks();
            } else {
                alert('Upload failed');
            }
        })
        .catch(() => alert('Upload failed'));
}

function onSermonDragStart(e) {
    e.dataTransfer.setData('text/plain', e.currentTarget.dataset.index);
    e.dropEffect = 'move';
}

function onSermonDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
}

function onSermonDrop(e) {
    e.preventDefault();
    const from = Number(e.dataTransfer.getData('text/plain'));
    const to = Number(e.currentTarget.dataset.index);
    if (isNaN(from) || isNaN(to) || from === to) return;
    const [moved] = sermonBlocks.splice(from, 1);
    sermonBlocks.splice(to, 0, moved);
    renderSermonBlocks();
}

function syncSermonJsonField() {
    sermonContentJsonField.value = JSON.stringify(sermonBlocks);
}

sermonAddBlockBtn.addEventListener('click', () => addSermonBlock(sermonBlockTypeSelect.value));

document.getElementById('sermonForm').addEventListener('submit', () => {
    syncSermonJsonField();
});

// Initialize
renderSermonBlocks();
</script>

<?php require_once 'includes/footer.php'; ?>
