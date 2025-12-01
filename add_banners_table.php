<?php
/**
 * Add Scrolling Banners Table
 * Run this once to add the banners feature to your existing database
 */

require_once 'config.php';

try {
    $conn = getDBConnection();

    // Create scrolling_banners table
    $conn->exec("CREATE TABLE IF NOT EXISTS scrolling_banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page_location VARCHAR(100) NOT NULL,
        banner_text TEXT NOT NULL,
        link_url VARCHAR(255) DEFAULT NULL,
        text_color VARCHAR(20) DEFAULT '#ffffff',
        background_color VARCHAR(20) DEFAULT '#5DADEC',
        display_order INT DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL,
        INDEX idx_page_status (page_location, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "✅ Table 'scrolling_banners' created successfully!<br>";

    // Insert sample banners
    $sampleBanners = [
        ['homepage', 'Welcome to Garrison Methodist Presbyterian Church - A community of faith, hope, and love! 🙏', null, '#ffffff', '#5DADEC', 1],
        ['homepage', 'Join us this Sunday at 9:00 AM for worship service! 📅', null, '#ffffff', '#0A0A33', 2],
        ['homepage', 'New members are always welcome - Come grow with us in faith! ✨', null, '#ffffff', '#5DADEC', 3],
        ['communities', 'Explore our vibrant communities and find your place to serve! 👥', null, '#ffffff', '#5DADEC', 1],
        ['communities', 'Get involved in our ministries and make a difference! 🌟', null, '#ffffff', '#0A0A33', 2],
    ];

    $stmt = $conn->prepare("INSERT INTO scrolling_banners (page_location, banner_text, link_url, text_color, background_color, display_order) VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($sampleBanners as $banner) {
        $stmt->execute($banner);
    }

    echo "✅ Sample banners added!<br><br>";

    echo "<h2>✓ Scrolling Banners Feature Added Successfully!</h2>";
    echo "<p>The following has been created:</p>";
    echo "<ul>";
    echo "<li>✓ Database table: <strong>scrolling_banners</strong></li>";
    echo "<li>✓ 5 sample banners (3 for homepage, 2 for community pages)</li>";
    echo "</ul>";
    echo "<br>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Go to admin panel: <a href='admin/banners.php'>Manage Banners</a></li>";
    echo "<li>View your website to see the banners in action</li>";
    echo "<li>Delete this file for security: <code>rm add_banners_table.php</code></li>";
    echo "</ol>";

} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
