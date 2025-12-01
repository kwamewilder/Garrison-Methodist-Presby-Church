<!DOCTYPE html>
<html>
<head>
    <title>Banner System Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #5DADEC; }
        .test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #5DADEC; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 5px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #5DADEC; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Banner System Diagnostic Test</h1>

<?php
require_once 'config.php';

echo "<div class='test-section'>";
echo "<h2>1. Database Connection Test</h2>";
try {
    $conn = getDBConnection();
    echo "<p class='success'>✅ Successfully connected to database: <strong>" . DB_NAME . "</strong></p>";
} catch(PDOException $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "</div></div></body></html>";
    exit;
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>2. Check if 'scrolling_banners' Table Exists</h2>";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'scrolling_banners'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "<p class='success'>✅ Table 'scrolling_banners' exists</p>";

        // Get table structure
        $stmt = $conn->query("DESCRIBE scrolling_banners");
        $columns = $stmt->fetchAll();

        echo "<h3>Table Structure:</h3>";
        echo "<table><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";

    } else {
        echo "<p class='error'>❌ Table 'scrolling_banners' does NOT exist</p>";
        echo "<p class='warning'>⚠️ You need to run: <code>php add_banners_table.php</code> to create the table</p>";
        echo "</div></div></body></html>";
        exit;
    }
} catch(PDOException $e) {
    echo "<p class='error'>❌ Error checking table: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>3. Check Banner Data</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM scrolling_banners");
    $result = $stmt->fetch();
    $total = $result['total'];

    echo "<p class='success'>✅ Total banners in database: <strong>$total</strong></p>";

    if ($total > 0) {
        // Get all banners
        $stmt = $conn->query("SELECT id, page_location, banner_text, status, display_order, created_at FROM scrolling_banners ORDER BY page_location, display_order");
        $banners = $stmt->fetchAll();

        echo "<h3>Banner List:</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Page</th><th>Text</th><th>Status</th><th>Order</th><th>Created</th></tr>";
        foreach ($banners as $banner) {
            $statusClass = $banner['status'] === 'active' ? 'success' : 'error';
            echo "<tr>";
            echo "<td>{$banner['id']}</td>";
            echo "<td><strong>{$banner['page_location']}</strong></td>";
            echo "<td>" . htmlspecialchars(substr($banner['banner_text'], 0, 60)) . "...</td>";
            echo "<td class='$statusClass'>{$banner['status']}</td>";
            echo "<td>{$banner['display_order']}</td>";
            echo "<td>{$banner['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='warning'>⚠️ No banners found. Add some banners in the admin panel!</p>";
    }

} catch(PDOException $e) {
    echo "<p class='error'>❌ Error fetching banners: " . $e->getMessage() . "</p>";
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>4. Test API Endpoint</h2>";
echo "<p>Testing API endpoint: <code>api/get_banners.php</code></p>";

$pages_to_test = ['homepage', 'about', 'choir', 'communities'];
foreach ($pages_to_test as $page) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM scrolling_banners WHERE page_location = ? AND status = 'active'");
        $stmt->execute([$page]);
        $result = $stmt->fetch();
        $count = $result['count'];

        if ($count > 0) {
            echo "<p class='success'>✅ Page '<strong>$page</strong>': $count active banner(s)</p>";
        } else {
            echo "<p class='warning'>⚠️ Page '<strong>$page</strong>': No active banners</p>";
        }
    } catch(PDOException $e) {
        echo "<p class='error'>❌ Error testing $page: " . $e->getMessage() . "</p>";
    }
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>5. File System Check</h2>";

$files_to_check = [
    'api/get_banners.php' => 'API Endpoint',
    'admin/banners.php' => 'Admin Management Page',
    'js/scrolling-banner.js' => 'JavaScript File',
    'css/scrolling-banner.css' => 'CSS File',
    'add_banners_table.php' => 'Database Setup Script'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        echo "<p class='success'>✅ $description: <code>$file</code></p>";
    } else {
        echo "<p class='error'>❌ $description missing: <code>$file</code></p>";
    }
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>6. Next Steps</h2>";
echo "<ul>";

// Check if table needs to be created
$stmt = $conn->query("SHOW TABLES LIKE 'scrolling_banners'");
if (!$stmt->fetch()) {
    echo "<li><strong>Run the database setup:</strong> <code>php add_banners_table.php</code></li>";
} else {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM scrolling_banners");
    $result = $stmt->fetch();
    if ($result['total'] == 0) {
        echo "<li><strong>Add banners:</strong> Go to <a href='admin/banners.php'>Admin Panel → Banners</a> and add some banners</li>";
    } else {
        echo "<li class='success'>✅ System is ready! Visit any page to see banners in action</li>";
    }
}

echo "<li><strong>Access admin panel:</strong> <a href='admin/'>Login to Admin</a> (default: admin/admin123)</li>";
echo "<li><strong>Test a page:</strong> <a href='index.html'>View Homepage</a> to see banners</li>";
echo "</ul>";
echo "</div>";

?>

    </div>
</body>
</html>
