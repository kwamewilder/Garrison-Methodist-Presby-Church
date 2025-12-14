<?php
require_once "config.php";
require_once "includes/block_renderer.php";
require_once "includes/get_communities.php";
require_once "includes/get_menu_pages.php";
require_once "includes/get_settings.php";

$currentPage = 'contact';
$serviceSunday = getSetting('sunday_service_time', '09:00 AM');
$serviceTuesday = getSetting('tuesday_service_time', '9:00 AM');
$serviceCounselling = getSetting('counselling_time', '14:00 AM');
$churchName = getSetting('church_name', 'Garrison Methodist Presbyterian Church');
$churchAddress = getSetting('church_address', 'Burma Camp, Accra');
$churchPhone = getSetting('church_phone', '(+233) 243-3689');
$churchEmail = getSetting('church_email', 'info@garrisonmpc.org');
$facebookUrl = getSetting('facebook_url', 'https://www.facebook.com/gmpcburmacamp');
$youtubeUrl = getSetting('youtube_url', 'https://www.youtube.com/@gmpcburmacamp3405');
$tiktokUrl = getSetting('tiktok_url', 'https://www.tiktok.com/@gmpcburmacamp');

// Try to load editable content from pages table (slug: contact)
$contactPageContent = '';
$contactMetaDescription = "Contact Garrison Methodist Presbyterian Church. Find our address, phone number, email, and service times. We'd love to hear from you.";
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT title, meta_description, content FROM pages WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute(['contact']);
    $pageRow = $stmt->fetch();
    if ($pageRow) {
        $contactPageContent = render_blocks($pageRow['content']);
        if (!empty($pageRow['meta_description'])) {
            $contactMetaDescription = $pageRow['meta_description'];
        }
    }
} catch (PDOException $e) {
    error_log("Contact page fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($contactMetaDescription); ?>">
    <title>Contact Us - Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
</head>
<body>
    <!-- Scrolling Banner -->
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="contact">
        <div class="scrolling-banner">
            <span class="banner-item">Loading announcements...</span>
        </div>
    </div>

    <!-- Header -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Contact Us</h1>
            <p>We'd love to hear from you and welcome you to our church family</p>
        </div>
    </section>

    <section class="contact-hero contact-hero--cms">
        <div class="contact-hero__overlay"></div>
        <div class="container">
            <div class="contact-hero__content contact-hero__content--single">
                <div class="contact-cms-card">
                    <?php if ($contactPageContent): ?>
                        <div class="contact-cms-body">
                            <?php echo $contactPageContent; ?>
                        </div>
                    <?php else: ?>
                        <h2>Contact content coming soon</h2>
                        <p>Please check back shortly for our latest contact details and directions.</p>
                    <?php endif; ?>
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
                        <span><?php echo htmlspecialchars($churchName); ?></span>
                    </div>
                    <p>A community of faith, hope, and love serving God together</p>
                    <div class="social-links">
                        <?php if (!empty($facebookUrl)): ?><a href="<?php echo htmlspecialchars($facebookUrl); ?>" target="_blank"><i class="fab fa-facebook"></i></a><?php endif; ?>
                        <?php if (!empty($youtubeUrl)): ?><a href="<?php echo htmlspecialchars($youtubeUrl); ?>" target="_blank"><i class="fab fa-youtube"></i></a><?php endif; ?>
                        <?php if (!empty($tiktokUrl)): ?><a href="<?php echo htmlspecialchars($tiktokUrl); ?>" target="_blank"><i class="fab fa-tiktok"></i></a><?php endif; ?>
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
                        <li>Sunday School: <?php echo htmlspecialchars($serviceSunday); ?></li>
                        <li>Tuesday Service: <?php echo htmlspecialchars($serviceTuesday); ?></li>
                        <li>Counselling Session: <?php echo htmlspecialchars($serviceCounselling); ?></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <ul>
                        <li><?php echo htmlspecialchars($churchName); ?></li>
                        <li><?php echo htmlspecialchars($churchAddress); ?></li>
                        <li><?php echo htmlspecialchars($churchPhone); ?></li>
                        <li><?php echo htmlspecialchars($churchEmail); ?></li>
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
