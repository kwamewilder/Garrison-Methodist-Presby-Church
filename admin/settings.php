<?php
$pageTitle = 'Site Settings';
require_once 'includes/header.php';

$conn = getDBConnection();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        try {
            foreach ($_POST as $key => $value) {
                if ($key !== 'csrf_token' && $key !== 'action') {
                    $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                    $stmt->execute([sanitizeInput($value), $key]);
                }
            }
            $success = 'Settings updated successfully!';
        } catch(PDOException $e) {
            $error = 'Error updating settings.';
            error_log($e->getMessage());
        }
    }
}

// Get all settings
try {
    $stmt = $conn->query("SELECT * FROM settings ORDER BY setting_key ASC");
    $settingsArray = $stmt->fetchAll();
    $settings = [];
    foreach ($settingsArray as $setting) {
        $settings[$setting['setting_key']] = $setting['setting_value'];
    }
} catch(PDOException $e) {
    $error = 'Error loading settings.';
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
        <h2><i class="fas fa-cog"></i> General Settings</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <h3 style="margin-bottom: 20px; color: #0A0A33;">Church Information</h3>

            <div class="form-group">
                <label>Church Name</label>
                <input type="text" name="church_name" class="form-control"
                       value="<?php echo htmlspecialchars($settings['church_name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Church Address</label>
                <input type="text" name="church_address" class="form-control"
                       value="<?php echo htmlspecialchars($settings['church_address'] ?? ''); ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="church_phone" class="form-control"
                           value="<?php echo htmlspecialchars($settings['church_phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="church_email" class="form-control"
                           value="<?php echo htmlspecialchars($settings['church_email'] ?? ''); ?>">
                </div>
            </div>

            <hr style="margin: 30px 0;">

            <h3 style="margin-bottom: 20px; color: #0A0A33;">Service Times</h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Sunday Service Time</label>
                    <input type="text" name="sunday_service_time" class="form-control"
                           value="<?php echo htmlspecialchars($settings['sunday_service_time'] ?? ''); ?>"
                           placeholder="e.g., 09:00 AM">
                </div>

                <div class="form-group">
                    <label>Tuesday Service Time</label>
                    <input type="text" name="tuesday_service_time" class="form-control"
                           value="<?php echo htmlspecialchars($settings['tuesday_service_time'] ?? ''); ?>"
                           placeholder="e.g., 9:00 AM">
                </div>

                <div class="form-group">
                    <label>Counselling Time</label>
                    <input type="text" name="counselling_time" class="form-control"
                           value="<?php echo htmlspecialchars($settings['counselling_time'] ?? ''); ?>"
                           placeholder="e.g., 14:00 AM">
                </div>
            </div>

            <hr style="margin: 30px 0;">

            <h3 style="margin-bottom: 20px; color: #0A0A33;">Social Media Links</h3>

            <div class="form-group">
                <label>Facebook URL</label>
                <input type="url" name="facebook_url" class="form-control"
                       value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>"
                       placeholder="https://facebook.com/yourpage">
            </div>

            <div class="form-group">
                <label>YouTube URL</label>
                <input type="url" name="youtube_url" class="form-control"
                       value="<?php echo htmlspecialchars($settings['youtube_url'] ?? ''); ?>"
                       placeholder="https://youtube.com/@yourchannel">
            </div>

            <div class="form-group">
                <label>TikTok URL</label>
                <input type="url" name="tiktok_url" class="form-control"
                       value="<?php echo htmlspecialchars($settings['tiktok_url'] ?? ''); ?>"
                       placeholder="https://tiktok.com/@yourpage">
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
