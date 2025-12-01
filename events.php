<?php
require_once 'config.php';
require_once "includes/get_communities.php";
require_once "includes/get_menu_pages.php";

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get events from database
$upcomingEvents = [];
$pastEvents = [];
$menuPages = [];

try {
    $conn = getDBConnection();
    
    // Get all published events
    $stmt = $conn->prepare("SELECT * FROM events WHERE status = 'published' ORDER BY event_date DESC, event_time DESC");
    $stmt->execute();
    $allEvents = $stmt->fetchAll();
    
    // Separate upcoming and past
    $today = date('Y-m-d');
    foreach ($allEvents as $event) {
        if ($event['event_date'] >= $today) {
            $upcomingEvents[] = $event;
        } else {
            $pastEvents[] = $event;
        }
    }
    
    // Get menu pages
    try {
        $stmt = $conn->query("SELECT title, page_slug FROM pages WHERE status = 'published' ORDER BY id ASC LIMIT 5");
        $menuPages = $stmt->fetchAll();
    } catch(PDOException $e) {
        $menuPages = [];
    }
    
} catch(PDOException $e) {
    error_log("Error fetching events: " . $e->getMessage());
}

function formatEventDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatEventTime($time) {
    return $time ? date('g:i A', strtotime($time)) : 'TBD';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
</head>
<body>
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="events">
        <div class="scrolling-banner"><span class="banner-item">Loading announcements...</span></div>
    </div>

    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <img src="images/logo.png" alt="GMPC Logo" class="logo-img">
                    <span>Garrison Methodist Presbyterian Church</span>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="index.php" class="nav-link">Home</a></li>
                    <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
                    <li class="nav-item"><a href="sermons.php" class="nav-link">Sermons</a></li>
                    <li class="nav-item"><a href="events.php" class="nav-link active">Events</a></li>
                    <li class="nav-item"><a href="media.php" class="nav-link">Media</a></li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link">Communities <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <?php foreach ($communities as $community): ?>
                                <li><a href="community.php?slug=<?php echo urlencode($community['slug']); ?>"><?php echo htmlspecialchars($community['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                    <?php foreach ($menuPages as $menuPage): ?>
                        <li class="nav-item"><a href="page.php?slug=<?php echo urlencode($menuPage['slug']); ?>" class="nav-link"><?php echo htmlspecialchars($menuPage['title']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <div class="hamburger"><span class="bar"></span><span class="bar"></span><span class="bar"></span></div>
            </div>
        </nav>
    </header>

    <section class="page-header">
        <div class="container">
            <h1>Church Events</h1>
            <p>Join us for fellowship, worship, and community activities</p>
        </div>
    </section>

    <section class="sermon-series" style="padding: 60px 0;">
        <div class="container">
            <h2 style="margin-bottom: 30px;">Upcoming Events</h2>
            <?php if (count($upcomingEvents) > 0): ?>
                <div class="series-grid">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <div class="sermon-card">
                            <div class="sermon-thumbnail">
                                <?php if ($event['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <?php else: ?>
                                    <img src="https://images.pexels.com/photos/1587927/pexels-photo-1587927.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Event">
                                <?php endif; ?>
                            </div>
                            <div class="sermon-details">
                                <span class="sermon-date"><?php echo formatEventDate($event['event_date']); ?></span>
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <p class="sermon-meta-item"><i class="fas fa-clock"></i> <?php echo formatEventTime($event['event_time']); ?></p>
                                <?php if ($event['location']): ?>
                                    <p class="sermon-meta-item"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></p>
                                <?php endif; ?>
                                <?php if ($event['description']): ?>
                                    <p><?php echo htmlspecialchars(substr($event['description'], 0, 120)) . (strlen($event['description']) > 120 ? '...' : ''); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No upcoming events at this time.</p>
            <?php endif; ?>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <img src="images/logo.png" alt="GMPC Logo" class="footer-logo-img">
                        <span>Garrison Methodist Presbyterian Church</span>
                    </div>
                    <p>A community of faith, hope, and love serving God together</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/gmpcburmacamp" target="_blank"><i class="fab fa-facebook"></i></a>
                        <a href="https://www.youtube.com/@gmpcburmacamp3405" target="_blank"><i class="fab fa-youtube"></i></a>
                        <a href="https://www.tiktok.com/@gmpcburmacamp" target="_blank"><i class="fab fa-tiktok"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="sermons.php">Sermons</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Service Times</h4>
                    <ul>
                        <li>Sunday School: 09:00 AM</li>
                        <li>Morning Worship: 9:00 AM</li>
                        <li>Counselling Session: 14:00 AM</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <ul>
                        <li>Garrison Methodist Presby Church</li>
                        <li>Burma Camp, Accra</li>
                        <li>(+233) 243-3689</li>
                        <li>info@garrisonmpc.org</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Garrison Methodist Presbyterian Church. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/scrolling-banner.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
