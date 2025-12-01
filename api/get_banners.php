<?php
/**
 * API Endpoint: Get Scrolling Banners
 * Usage: /api/get_banners.php?page=homepage
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'homepage';

// Sanitize page parameter
$allowed_pages = [
    'homepage', 'about', 'sermons', 'events', 'contact',
    'communities', 'audio-visual-team', 'choir', 'israel',
    'junior-youth', 'youth-fellowship', 'womens-fellowship',
    'mens-fellowship', 'children-service', 'singing-band', 'ark-of-praise'
];
if (!in_array($page, $allowed_pages)) {
    $page = 'homepage';
}

try {
    $conn = getDBConnection();

    // Check if this is a specific community page
    $community_pages = ['audio-visual-team', 'choir', 'israel', 'junior-youth',
                        'youth-fellowship', 'womens-fellowship', 'mens-fellowship',
                        'children-service', 'singing-band', 'ark-of-praise'];

    if (in_array($page, $community_pages)) {
        // For individual community pages, fetch both specific page banners AND general "communities" banners
        $stmt = $conn->prepare("SELECT banner_text, link_url, text_color, background_color
                                FROM scrolling_banners
                                WHERE (page_location = ? OR page_location = 'communities')
                                AND status = 'active'
                                ORDER BY display_order ASC");
        $stmt->execute([$page]);
    } else {
        // For other pages, just fetch their specific banners
        $stmt = $conn->prepare("SELECT banner_text, link_url, text_color, background_color
                                FROM scrolling_banners
                                WHERE page_location = ? AND status = 'active'
                                ORDER BY display_order ASC");
        $stmt->execute([$page]);
    }

    $banners = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'page' => $page,
        'banners' => $banners,
        'count' => count($banners)
    ]);

} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch banners',
        'message' => $e->getMessage()
    ]);
}
?>
