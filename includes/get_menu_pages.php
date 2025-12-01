<?php
// Get menu pages from database
$menuPages = [];
try {
    if (!isset($conn)) {
        require_once __DIR__ . '/../config.php';
        $conn = getDBConnection();
    }
    $stmt = $conn->query("SELECT title, slug, menu_order FROM pages WHERE show_in_menu = 1 AND status = 'active' ORDER BY menu_order ASC");
    $menuPages = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching menu pages: " . $e->getMessage());
    $menuPages = [];
}
?>
