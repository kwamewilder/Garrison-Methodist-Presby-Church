<?php
// Get active communities from database
$communities = [];
try {
    if (!isset($conn)) {
        require_once __DIR__ . '/../config.php';
        $conn = getDBConnection();
    }
    $stmt = $conn->query("SELECT name, slug FROM communities WHERE status = 'active' ORDER BY display_order ASC");
    $communities = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log("Error fetching communities: " . $e->getMessage());
    $communities = [];
}
?>
