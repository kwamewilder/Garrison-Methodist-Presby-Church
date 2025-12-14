<?php
$pageTitle = 'Manage Admins';
require_once 'includes/header.php';

if (!hasRole('super_admin')) {
    header('HTTP/1.1 403 Forbidden');
    exit('Only super admins can manage admins.');
}

$conn = getDBConnection();
$success = '';
$error = '';

// Fetch data
$admins = [];
$pages = [];
$eventsList = [];
$sermonsList = [];
$communitiesList = [];
$bannersList = [];
try {
    $admins = $conn->query("SELECT id, username, email, full_name, role, status, avatar_url FROM admins ORDER BY role DESC, full_name ASC")->fetchAll();
    $pages = $conn->query("SELECT id, title, slug FROM pages WHERE status = 'active' ORDER BY menu_order ASC, title ASC")->fetchAll();
    $eventsList = $conn->query("SELECT id, title FROM events ORDER BY event_date DESC")->fetchAll();
    $sermonsList = $conn->query("SELECT id, title FROM sermons ORDER BY sermon_date DESC")->fetchAll();
    $communitiesList = $conn->query("SELECT id, name AS title FROM communities WHERE status = 'active' ORDER BY display_order ASC, name ASC")->fetchAll();
    $bannersList = $conn->query("SELECT id, title FROM scrolling_banners ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $error = 'Failed to load admins/pages.';
    error_log($e->getMessage());
}

// Load permissions
function getAdminPermissions($conn, $adminId) {
    try {
        $stmt = $conn->prepare("SELECT resource_type, resource_id FROM admin_permissions WHERE admin_id = ? AND can_edit = 1");
        $stmt->execute([$adminId]);
        $rows = $stmt->fetchAll();
        $perms = [];
        foreach ($rows as $r) {
            $perms[$r['resource_type']][] = $r['resource_id'];
        }
        return $perms;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        return [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'update_role') {
            $adminId = (int)$_POST['admin_id'];
            $role = $_POST['role'] ?? 'editor';
            try {
                $stmt = $conn->prepare("UPDATE admins SET role = ? WHERE id = ?");
                $stmt->execute([$role, $adminId]);
                log_audit('role_change', 'admin', $adminId, "Role set to {$role}");
                $success = 'Role updated.';
            } catch (PDOException $e) {
                $error = 'Failed to update role.';
                error_log($e->getMessage());
            }
        } elseif ($action === 'update_permissions') {
            $adminId = (int)$_POST['admin_id'];
            $allowedPages = $_POST['allowed_pages'] ?? [];
            $allowedEvents = $_POST['allowed_events'] ?? [];
            $allowedSermons = $_POST['allowed_sermons'] ?? [];
            $allowedCommunities = $_POST['allowed_communities'] ?? [];
            $allowedBanners = $_POST['allowed_banners'] ?? [];

            if (!is_array($allowedPages)) $allowedPages = [];
            if (!is_array($allowedEvents)) $allowedEvents = [];
            if (!is_array($allowedSermons)) $allowedSermons = [];
            if (!is_array($allowedCommunities)) $allowedCommunities = [];
            if (!is_array($allowedBanners)) $allowedBanners = [];

            try {
                $conn->beginTransaction();
                $stmt = $conn->prepare("DELETE FROM admin_permissions WHERE admin_id = ?");
                $stmt->execute([$adminId]);
                $insert = $conn->prepare("INSERT INTO admin_permissions (admin_id, resource_type, resource_id, can_edit) VALUES (?, ?, ?, 1)");

                foreach ($allowedPages as $pid) {
                    $insert->execute([$adminId, 'page', (int)$pid]);
                }
                foreach ($allowedEvents as $eid) {
                    $insert->execute([$adminId, 'event', (int)$eid]);
                }
                foreach ($allowedSermons as $sid) {
                    $insert->execute([$adminId, 'sermon', (int)$sid]);
                }
                foreach ($allowedCommunities as $cid) {
                    $insert->execute([$adminId, 'community', (int)$cid]);
                }
                foreach ($allowedBanners as $bid) {
                    $insert->execute([$adminId, 'banner', (int)$bid]);
                }

                $conn->commit();
                log_audit('permission_update', 'admin', $adminId, 'Updated edit permissions');
                $success = 'Permissions updated.';
            } catch (PDOException $e) {
                $conn->rollBack();
                $error = 'Failed to update permissions.';
                error_log($e->getMessage());
            }
        }
        // refresh data
        try {
            $admins = $conn->query("SELECT id, username, email, full_name, role, status, avatar_url FROM admins ORDER BY role DESC, full_name ASC")->fetchAll();
        } catch (PDOException $e) {}
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-shield"></i> Admins Management</h2>
    </div>
    <div class="card-body">
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <p style="color:#666;">Set roles and per-page edit permissions. Super admins can edit everything and bypass permissions.</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Admin</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Permissions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <?php $permList = getAdminPermissions($conn, $admin['id']); ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($admin['full_name']); ?></strong><br>
                                <small><?php echo htmlspecialchars($admin['username']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td>
                                <?php if ($admin['username'] === 'kwame_wilder'): ?>
                                    <span class="badge badge-soft">Super Admin</span>
                                <?php else: ?>
                                    <form method="POST" style="display:flex; gap:8px; align-items:center;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                        <select name="role" class="form-control">
                                            <option value="super_admin" <?php echo $admin['role']==='super_admin'?'selected':''; ?>>Super Admin</option>
                                            <option value="admin" <?php echo $admin['role']==='admin'?'selected':''; ?>>Admin</option>
                                            <option value="editor" <?php echo $admin['role']==='editor'?'selected':''; ?>>Editor</option>
                                        </select>
                                        <button class="btn btn-small btn-primary" type="submit">Save</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(ucfirst($admin['status'])); ?></td>
                            <td>
                                <details>
                                    <summary>Manage access</summary>
                                    <form method="POST" style="margin-top:10px;">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="update_permissions">
                                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">

                                        <p class="perm-heading">Pages</p>
                                        <div class="permission-grid">
                                            <?php foreach ($pages as $page): ?>
                                                <label class="perm-item">
                                                    <input type="checkbox" name="allowed_pages[]" value="<?php echo $page['id']; ?>" <?php echo in_array($page['id'], $permList['page'] ?? []) ? 'checked' : ''; ?>>
                                                    <span><?php echo htmlspecialchars($page['title']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <p class="perm-heading">Events</p>
                                        <div class="permission-grid">
                                            <?php foreach ($eventsList as $row): ?>
                                                <label class="perm-item">
                                                    <input type="checkbox" name="allowed_events[]" value="<?php echo $row['id']; ?>" <?php echo in_array($row['id'], $permList['event'] ?? []) ? 'checked' : ''; ?>>
                                                    <span><?php echo htmlspecialchars($row['title']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <p class="perm-heading">Sermons</p>
                                        <div class="permission-grid">
                                            <?php foreach ($sermonsList as $row): ?>
                                                <label class="perm-item">
                                                    <input type="checkbox" name="allowed_sermons[]" value="<?php echo $row['id']; ?>" <?php echo in_array($row['id'], $permList['sermon'] ?? []) ? 'checked' : ''; ?>>
                                                    <span><?php echo htmlspecialchars($row['title']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <p class="perm-heading">Communities</p>
                                        <div class="permission-grid">
                                            <?php foreach ($communitiesList as $row): ?>
                                                <label class="perm-item">
                                                    <input type="checkbox" name="allowed_communities[]" value="<?php echo $row['id']; ?>" <?php echo in_array($row['id'], $permList['community'] ?? []) ? 'checked' : ''; ?>>
                                                    <span><?php echo htmlspecialchars($row['title']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <p class="perm-heading">Banners</p>
                                        <div class="permission-grid">
                                            <?php foreach ($bannersList as $row): ?>
                                                <label class="perm-item">
                                                    <input type="checkbox" name="allowed_banners[]" value="<?php echo $row['id']; ?>" <?php echo in_array($row['id'], $permList['banner'] ?? []) ? 'checked' : ''; ?>>
                                                    <span><?php echo htmlspecialchars($row['title']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <button class="btn btn-small btn-primary" type="submit" style="margin-top:10px;">Save Permissions</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.permission-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
    padding: 6px 0;
}

.perm-item {
    display: flex;
    gap: 8px;
    align-items: center;
    background: #f8f9fb;
    border: 1px solid #e4e7ec;
    border-radius: 6px;
    padding: 8px 10px;
}

.perm-heading {
    font-weight: 600;
    color: #444;
    margin: 12px 0 6px;
}
</style>

<?php require_once 'includes/footer.php'; ?>
