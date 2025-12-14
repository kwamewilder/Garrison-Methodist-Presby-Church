<?php
require_once "config.php";
require_once "includes/block_renderer.php";
require_once "includes/get_communities.php";
require_once "includes/get_menu_pages.php";
require_once "includes/get_settings.php";

$currentPage = 'about';
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

// Try to load editable content from pages table (slug: about)
$aboutPageContent = '';
$aboutMetaDescription = 'Learn about the history, mission, and beliefs of Garrison Methodist Presbyterian Church. Discover our story and what makes our church family special.';
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT title, meta_description, content FROM pages WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute(['about']);
    $pageRow = $stmt->fetch();
    if ($pageRow) {
        $aboutPageContent = render_blocks($pageRow['content']);
        if (!empty($pageRow['meta_description'])) {
            $aboutMetaDescription = $pageRow['meta_description'];
        }
    }
} catch (PDOException $e) {
    error_log("About page fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($aboutMetaDescription); ?>">
    <title>About Us - Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
</head>
<body>
    <!-- Scrolling Banner -->
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="about">
        <div class="scrolling-banner">
            <span class="banner-item">Loading announcements...</span>
        </div>
    </div>

    <!-- Header -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>About Our Church</h1>
            <p>Learn about our history, mission, and the people who make our church family special</p>
        </div>
    </section>

    <?php if ($aboutPageContent): ?>
        <section class="custom-page-content" style="padding: 60px 0;">
            <div class="container">
                <div class="page-content-wrapper" style="max-width: 900px; margin: 0 auto;">
                    <?php echo $aboutPageContent; ?>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- About Content (fallback) -->
        <section class="about-content">
            <div class="container">
                <div class="about-grid">
                    <div class="about-text">
                        <h2>Our Story</h2>
                        <p>Garrison Methodist Presbyterian Church has been a cornerstone of faith in our community for over a century. Founded in 1892, our church has grown from a small gathering of believers to a vibrant community that welcomes all who seek to know God's love.</p>
                        <p>We are a unique congregation that embraces both Methodist and Presbyterian traditions, creating a rich tapestry of worship and fellowship that honors our diverse heritage while staying true to the Gospel of Jesus Christ.</p>
                        
                        <p>Garrison Methodist Presbyterian Church has been a cornerstone of faith in our community for over a century. Founded in 1892, our church has grown from a small gathering of believers to a vibrant community that welcomes all who seek to know God's love.</p>
                        <p>We are a unique congregation that embraces both Methodist and Presbyterian traditions, creating a rich tapestry of worship and fellowship that honors our diverse heritage while staying true to the Gospel of Jesus Christ.</p>
                         <p>Garrison Methodist Presbyterian Church has been a cornerstone of faith in our community for over a century. Founded in 1892, our church has grown from a small gathering of believers to a vibrant community that welcomes all who seek to know God's love.</p>
                        <p>We are a unique congregation that embraces both Methodist and Presbyterian traditions, creating a rich tapestry of worship and fellowship that honors our diverse heritage while staying true to the Gospel of Jesus Christ.</p>
                              
                   
                    </div>
                    
                    <div class="about-image">
                        <img src="https://images.pexels.com/photos/208285/pexels-photo-208285.jpeg?auto=compress&cs=tinysrgb&w=800" alt="An old, black and white photo of the church from the early 20th century.">
                    <img src="https://images.pexels.com/photos/208285/pexels-photo-208285.jpeg?auto=compress&cs=tinysrgb&w=800" alt="An old, black and white photo of the church from the early 20th century.">
                
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Staff Section -->
    <section class="staff-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Pastoral Team</h2>
                <p>Meet the dedicated leaders serving our church community</p>
            </div>
            <div class="staff-grid">
                <div class="staff-card">
                    <div class="staff-image">
                        <img src="./images/5.jpg?auto=compress&cs=tinysrgb&w=300" alt="Headshot of Pastor John Mensah, a man in his 50s with a kind smile, wearing a clerical collar.">
                    </div>
                    <div class="staff-info">
                        <h3>Pastor John Mensah</h3>
                        <p class="staff-title">Senior Pastor</p>
                        <p>Pastor John has been serving our congregation for over 15 years, bringing wisdom, compassion, and biblical teaching to our community.</p>
                        <div class="staff-contact">
                            <a href="mailto:pastor@garrisonmpc.org"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
                <div class="staff-card">
                    <div class="staff-image">
                        <img src="./images/4.jpg" alt="Headshot of Pastor Sarah Williams, a woman in her 40s with a warm smile, wearing a blazer.">
                    </div>
                    <div class="staff-info">
                        <h3>Pastor Sarah Williams</h3>
                        <p class="staff-title">Associate Pastor</p>
                        <p>Pastor Sarah leads our worship ministry and pastoral care, bringing joy and spiritual depth to our services and community outreach.</p>
                        <div class="staff-contact">
                            <a href="mailto:sarah@garrisonmpc.org"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
                <div class="staff-card">
                    <div class="staff-image">
                        <img src="./images/2.jpg?auto=compress&cs=tinysrgb&w=300" alt="Headshot of Youth Pastor Mark Thompson, a young man in his late 20s with a friendly expression, wearing a casual shirt.">
                    </div>
                    <div class="staff-info">
                        <h3>Mark Thompson</h3>
                        <p class="staff-title">Youth Pastor</p>
                        <p>Mark is passionate about helping young people grow in their faith and build strong relationships with each other and with God.</p>
                        <div class="staff-contact">
                            <a href="mailto:mark@garrisonmpc.org"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
                </div>
                <div class="staff-card">
                    <div class="staff-image">
                        <img src="./images/1.jpg" alt="Headshot of Music Director, a woman in her 30s with a joyful expression, standing in front of a piano.">
                    </div>
                    <div class="staff-info">
                        <h3>Jennifer Lee</h3>
                        <p class="staff-title">Music Director</p>
                        <p>Jennifer leads our choir and praise team, using her musical gifts to create a vibrant and worshipful atmosphere.</p>
                        <div class="staff-contact">
                            <a href="mailto:jennifer@garrisonmpc.org"><i class="fas fa-envelope"></i></a>
                        </div>
                    </div>
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
