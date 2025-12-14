<?php
$pageTitle = 'My Profile';
require_once 'includes/header.php';

$conn = getDBConnection();
$success = '';
$error = '';

// Load current admin fresh
try {
    $stmt = $conn->prepare("SELECT id, username, email, full_name, role, avatar_url, phone, appointment FROM admins WHERE id = ?");
    $stmt->execute([$currentAdmin['id']]);
    $adminRow = $stmt->fetch();
} catch (PDOException $e) {
    $error = 'Could not load profile.';
    error_log($e->getMessage());
    $adminRow = $currentAdmin;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $avatarUrl = $adminRow['avatar_url'] ?? null;
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $appointment = sanitizeInput($_POST['appointment'] ?? '');

        if ($password && $password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            // Handle avatar upload
            if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $allowed = ['image/jpeg'=>'.jpg','image/png'=>'.png','image/gif'=>'.gif','image/webp'=>'.webp'];
                $mime = mime_content_type($file['tmp_name']);
                if (!isset($allowed[$mime])) {
                    $error = 'Invalid avatar type. Use JPG, PNG, GIF, or WEBP.';
                } elseif ($file['size'] > 3 * 1024 * 1024) {
                    $error = 'Avatar too large (max 3MB).';
                } else {
                    $safeName = 'avatar_' . $currentAdmin['id'] . '_' . bin2hex(random_bytes(4)) . $allowed[$mime];
                    $uploadDir = UPLOAD_PATH . 'images/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $target = $uploadDir . $safeName;
                    if (!move_uploaded_file($file['tmp_name'], $target)) {
                        $error = 'Failed to save avatar.';
                    } else {
                        $avatarUrl = rtrim(UPLOAD_URL, '/') . '/images/' . $safeName;
                    }
                }
            }
        }

        if (!$error) {
            try {
                if ($password) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE admins SET full_name = ?, email = ?, password = ?, avatar_url = ?, phone = ?, appointment = ? WHERE id = ?");
                    $stmt->execute([$fullName, $email, $hash, $avatarUrl, $phone, $appointment, $currentAdmin['id']]);
                } else {
                    $stmt = $conn->prepare("UPDATE admins SET full_name = ?, email = ?, avatar_url = ?, phone = ?, appointment = ? WHERE id = ?");
                    $stmt->execute([$fullName, $email, $avatarUrl, $phone, $appointment, $currentAdmin['id']]);
                }

                // Refresh session
                $_SESSION['admin_email'] = $email;
                $_SESSION['admin_name'] = $fullName;
                $_SESSION['admin_avatar'] = $avatarUrl;

                log_audit('profile_update', 'admin', $currentAdmin['id'], 'Updated profile details');
                $success = 'Profile updated successfully.';

                // Reload row
                $adminRow['full_name'] = $fullName;
                $adminRow['email'] = $email;
                $adminRow['avatar_url'] = $avatarUrl;
                $adminRow['phone'] = $phone;
                $adminRow['appointment'] = $appointment;
            } catch (PDOException $e) {
                $error = 'Failed to update profile.';
                error_log($e->getMessage());
            }
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-circle"></i> My Profile</h2>
    </div>
    <div class="card-body">
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: center;">
                <div>
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($adminRow['full_name'] ?? ''); ?>" required>
                </div>
                <div>
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($adminRow['email'] ?? ''); ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                <div>
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($adminRow['phone'] ?? ''); ?>">
                </div>
                <div>
                    <label>Appointment / Role Title</label>
                    <input type="text" name="appointment" class="form-control" value="<?php echo htmlspecialchars($adminRow['appointment'] ?? ''); ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                <div>
                    <label>New Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div>
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; gap: 20px; align-items: center;">
                <div>
                    <label>Avatar</label>
                    <input type="file" name="avatar" accept="image/*" class="form-control">
                    <small style="color:#666;">JPG/PNG/GIF/WEBP up to 3MB.</small>
                </div>
                <div>
                    <?php if (!empty($adminRow['avatar_url'])): ?>
                        <img src="<?php echo htmlspecialchars($adminRow['avatar_url']); ?>" alt="Avatar" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #eee;">
                    <?php else: ?>
                        <div style="width:64px;height:64px;border-radius:50%;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#888;">No avatar</div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
