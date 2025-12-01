<?php
// Dynamic Page Template
require_once 'config.php';

// Get the slug from the URL
$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Get the page from database
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM pages WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$slug]);
    $page = $stmt->fetch();

    if (!$page) {
        // Page not found
        header('HTTP/1.0 404 Not Found');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Page Not Found - Garrison Methodist Presbyterian Church</title>
            <link rel="stylesheet" href="css/style.css">
        </head>
        <body>
            <div style="text-align: center; padding: 100px 20px;">
                <h1>404 - Page Not Found</h1>
                <p>The page you are looking for does not exist.</p>
                <a href="index.php" class="btn btn-primary">Go to Homepage</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    // Get communities for navigation
    require_once 'includes/get_communities.php';

    // Get menu pages for navigation
    require_once 'includes/get_menu_pages.php';

} catch(PDOException $e) {
    error_log($e->getMessage());
    die('Database error occurred.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page['meta_description'] ?: $page['title']); ?>">
    <title><?php echo htmlspecialchars($page['title']); ?> - Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
</head>
<body>
    <!-- Scrolling Banner -->
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="<?php echo htmlspecialchars($page['slug']); ?>">
        <div class="scrolling-banner">
            <span class="banner-item">Loading announcements...</span>
        </div>
    </div>

    <!-- Header -->
    <header class="header">
        <nav class="navbar">
            <div class="nav-container">
                <div class="nav-logo">
                    <img src="images/logo.png" alt="GMPC Logo" class="logo-img">
                    <span>Garrison Methodist Presbyterian Church</span>
                </div>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link">Home</a>
                    </li>
                    <li class="nav-item">
                        <a href="about.php" class="nav-link">About</a>
                    </li>
                    <li class="nav-item">
                        <a href="sermons.php" class="nav-link">Sermons</a>
                    </li>
                    <li class="nav-item">
                        <a href="events.php" class="nav-link">Events</a>
                    </li>
                    <li class="nav-item">
                        <a href="media.php" class="nav-link">Media</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link">Communities <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <?php foreach ($communities as $community): ?>
                                <li><a href="community.php?slug=<?php echo urlencode($community['slug']); ?>"><?php echo htmlspecialchars($community['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="contact.php" class="nav-link">Contact</a>
                    </li>
                    <?php foreach ($menuPages as $menuPage): ?>
                        <li class="nav-item">
                            <a href="page.php?slug=<?php echo urlencode($menuPage['slug']); ?>" class="nav-link <?php echo ($menuPage['slug'] === $page['slug']) ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($menuPage['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="hamburger">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </div>
            </div>
        </nav>
    </header>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1><?php echo htmlspecialchars($page['title']); ?></h1>
        </div>
    </section>

    <!-- Page Content -->
    <section class="custom-page-content" style="padding: 60px 0;">
        <div class="container">
            <div class="page-content-wrapper" style="max-width: 900px; margin: 0 auto;">
                <?php echo $page['content']; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h3>About Us</h3>
                <p>Garrison Methodist Presbyterian Church is a vibrant community of believers dedicated to worship, fellowship, and service.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul class="footer-links">
                    <li><a href="about.html">About</a></li>
                    <li><a href="sermons.php">Sermons</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="contact.html">Contact</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Service Times</h3>
                <p><strong>Sunday Morning:</strong> 9:00 AM</p>
                <p><strong>Sunday Evening:</strong> 6:00 PM</p>
                <p><strong>Wednesday:</strong> 7:00 PM</p>
            </div>
            <div class="footer-section">
                <h3>Connect With Us</h3>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Garrison Methodist Presbyterian Church. All rights reserved.</p>
        </div>
    </footer>

    <script src="js/scrolling-banner.js"></script>
    <script src="js/script.js"></script>
</body>
</html>
