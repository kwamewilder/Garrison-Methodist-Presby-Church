<?php
$pageTitle = 'Audit Log';
require_once 'includes/header.php';

$conn = getDBConnection();
$logs = [];
try {
    $stmt = $conn->query("SELECT al.*, a.full_name, a.username FROM audit_logs al LEFT JOIN admins a ON al.admin_id = a.id ORDER BY al.created_at DESC LIMIT 200");
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Audit fetch failed: " . $e->getMessage());
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-clipboard-list"></i> Recent Activity</h2>
    </div>
    <div class="card-body">
        <p style="color:#666;">Showing the latest 200 actions. Super admins see everything; all admins can view this log.</p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Entity</th>
                        <th>Description</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                            <td><?php echo htmlspecialchars($log['full_name'] ?? 'Unknown'); ?> <small>(<?php echo htmlspecialchars($log['username'] ?? ''); ?>)</small></td>
                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['entity_type']); ?> #<?php echo htmlspecialchars($log['entity_id']); ?></td>
                            <td><?php echo htmlspecialchars($log['description']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (count($logs) === 0): ?>
                        <tr><td colspan="6" style="text-align:center;color:#999;">No activity logged yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
