<?php
require_once 'config.php';

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get the slug from the URL
$slug = isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '';

// Get the community from database
$community = null;
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM communities WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$slug]);
    $community = $stmt->fetch();

    if (!$community) {
        header('HTTP/1.0 404 Not Found');
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Community Not Found - GMPC</title>
            <link rel="stylesheet" href="../css/style.css">
        </head>
        <body>
            <div style="text-align: center; padding: 100px 20px;">
                <h1>404 - Community Not Found</h1>
                <p>The community you're looking for doesn't exist or has been archived.</p>
                <a href="../index.php" style="color: #5DADEC;">Return to Homepage</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
} catch(PDOException $e) {
    error_log("Error fetching community: " . $e->getMessage());
    die("Database error occurred.");
}

// Get all communities for navigation
require_once 'includes/get_communities.php';
require_once "includes/get_menu_pages.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($community['description']); ?>">
    <title><?php echo htmlspecialchars($community['name']); ?> - Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
</head>
<body>
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="<?php echo htmlspecialchars($community['slug']); ?>">
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
                    <li class="nav-item"><a href="events.php" class="nav-link">Events</a></li>
                    <li class="nav-item"><a href="media.php" class="nav-link">Media</a></li>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link active">Communities <i class="fas fa-chevron-down"></i></a>
                        <ul class="dropdown-menu">
                            <?php foreach ($communities as $comm): ?>
                                <li><a href="community.php?slug=<?php echo urlencode($comm['slug']); ?>" <?php echo ($comm['slug'] === $community['slug']) ? 'style="font-weight: bold;"' : ''; ?>><?php echo htmlspecialchars($comm['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>
                </ul>
                    <?php foreach ($menuPages as $menuPage): ?>
                        <li class="nav-item">
                            <a href="page.php?slug=<?php echo urlencode($menuPage['slug']); ?>" class="nav-link"><?php echo htmlspecialchars($menuPage['title']); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="hamburger"><span class="bar"></span><span class="bar"></span><span class="bar"></span></div>
            </div>
        </nav>
    </header>

    <section class="page-header">
        <div class="container">
            <h1><?php echo htmlspecialchars($community['name']); ?></h1>
            <p><?php echo htmlspecialchars($community['description']); ?></p>
        </div>
    </section>

    <section class="custom-page-content" style="padding: 60px 0;">
        <div class="container">
            <div class="page-content-wrapper" style="max-width: 900px; margin: 0 auto;">
                
                <?php if ($community['full_content']): ?>
                    <div class="community-full-content" style="margin-bottom: 40px;">
                        <?php echo $community['full_content']; ?>
                    </div>
                <?php endif; ?>

                <div class="community-details" style="background: #f9f9f9; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
                    <h2 style="margin-top: 0;">Meeting Information</h2>
                    
                    <?php if ($community['meeting_day']): ?>
                        <p><strong><i class="fas fa-calendar"></i> Meeting Day:</strong> <?php echo htmlspecialchars($community['meeting_day']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($community['meeting_time']): ?>
                        <p><strong><i class="fas fa-clock"></i> Time:</strong> <?php echo htmlspecialchars($community['meeting_time']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($community['meeting_location']): ?>
                        <p><strong><i class="fas fa-map-marker-alt"></i> Location:</strong> <?php echo htmlspecialchars($community['meeting_location']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($community['contact_person']): ?>
                        <p><strong><i class="fas fa-user"></i> Contact Person:</strong> <?php echo htmlspecialchars($community['contact_person']); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($community['contact_email']): ?>
                        <p><strong><i class="fas fa-envelope"></i> Email:</strong> <a href="mailto:<?php echo htmlspecialchars($community['contact_email']); ?>"><?php echo htmlspecialchars($community['contact_email']); ?></a></p>
                    <?php endif; ?>
                </div>

                <div style="text-align: center; margin-top: 40px;">
                    <a href="contact.php" class="btn btn-primary">Get Involved</a>
                </div>
            </div>
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
