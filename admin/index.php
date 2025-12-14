<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$conn = getDBConnection();

// Get statistics
try {
    $stats = [];

    // Count pages
    $stmt = $conn->query("SELECT COUNT(*) as count FROM pages WHERE status = 'published'");
    $stats['pages'] = $stmt->fetch()['count'];

    // Count events
    $stmt = $conn->query("SELECT COUNT(*) as count FROM events WHERE status = 'published' AND event_date >= CURDATE()");
    $stats['events'] = $stmt->fetch()['count'];

    // Count sermons
    $stmt = $conn->query("SELECT COUNT(*) as count FROM sermons WHERE status = 'published'");
    $stats['sermons'] = $stmt->fetch()['count'];

    // Count communities
    $stmt = $conn->query("SELECT COUNT(*) as count FROM communities WHERE status = 'active'");
    $stats['communities'] = $stmt->fetch()['count'];

    // Trend data: last 6 months
    $stmt = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt FROM pages GROUP BY ym ORDER BY ym DESC LIMIT 6");
    $pageTrend = array_reverse($stmt->fetchAll());
    $stmt = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt FROM sermons GROUP BY ym ORDER BY ym DESC LIMIT 6");
    $sermonTrend = array_reverse($stmt->fetchAll());
    $stmt = $conn->query("SELECT DATE_FORMAT(event_date, '%Y-%m') as ym, COUNT(*) as cnt FROM events GROUP BY ym ORDER BY ym DESC LIMIT 6");
    $eventTrend = array_reverse($stmt->fetchAll());

    // Get recent events
    $stmt = $conn->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 5");
    $recentEvents = $stmt->fetchAll();

    // Get recent sermons
    $stmt = $conn->query("SELECT * FROM sermons WHERE status = 'published' ORDER BY sermon_date DESC LIMIT 5");
    $recentSermons = $stmt->fetchAll();

} catch(PDOException $e) {
    $error = "Error loading dashboard data.";
    error_log($e->getMessage());
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fas fa-file-alt"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $stats['pages'] ?? 0; ?></h3>
            <p>Published Pages</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fas fa-calendar"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $stats['events'] ?? 0; ?></h3>
            <p>Upcoming Events</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon orange">
            <i class="fas fa-book-bible"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $stats['sermons'] ?? 0; ?></h3>
            <p>Sermons</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $stats['communities'] ?? 0; ?></h3>
            <p>Communities</p>
        </div>
    </div>
</div>

<div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
    <!-- Charts -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-chart-line"></i> Content Activity (Last 6 Months)</h2>
        </div>
        <div class="card-body">
            <canvas id="contentChart" height="180"></canvas>
        </div>
    </div>

    <!-- Recent Events -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-calendar"></i> Upcoming Events</h2>
            <a href="events.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentEvents)): ?>
                <p style="color: #999; text-align: center; padding: 20px;">No upcoming events</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentEvents as $event): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($event['event_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $event['status'] === 'published' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($event['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Sermons -->
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-book-bible"></i> Recent Sermons</h2>
            <a href="sermons.php" class="btn btn-sm btn-primary">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($recentSermons)): ?>
                <p style="color: #999; text-align: center; padding: 20px;">No sermons yet</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Preacher</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentSermons as $sermon): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sermon['title']); ?></td>
                                    <td><?php echo htmlspecialchars($sermon['preacher'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($sermon['sermon_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="events.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Event
            </a>
            <a href="sermons.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Sermon
            </a>
            <a href="communities.php" class="btn btn-primary">
                <i class="fas fa-users"></i> Manage Communities
            </a>
            <a href="settings.php" class="btn btn-secondary">
                <i class="fas fa-cog"></i> Site Settings
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartCtx = document.getElementById('contentChart');
if (chartCtx) {
    const labels = <?php echo json_encode(array_column($pageTrend, 'ym')); ?>;
    const pageData = <?php echo json_encode(array_map('intval', array_column($pageTrend, 'cnt'))); ?>;
    const sermonData = <?php echo json_encode(array_map('intval', array_column($sermonTrend, 'cnt'))); ?>;
    const eventData = <?php echo json_encode(array_map('intval', array_column($eventTrend, 'cnt'))); ?>;
    new Chart(chartCtx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Pages', data: pageData, borderColor: '#4dabf7', backgroundColor: 'rgba(77,171,247,0.15)', tension: 0.3, fill: true },
                { label: 'Sermons', data: sermonData, borderColor: '#f59f00', backgroundColor: 'rgba(245,159,0,0.15)', tension: 0.3, fill: true },
                { label: 'Events', data: eventData, borderColor: '#2f9e44', backgroundColor: 'rgba(47,158,68,0.15)', tension: 0.3, fill: true },
            ]
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            scales: {
                y: { beginAtZero: true, ticks: { precision:0 } }
            }
        }
    });
}
</script>
