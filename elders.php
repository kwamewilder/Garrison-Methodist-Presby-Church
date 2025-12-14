<?php
require_once "config.php";
require_once "includes/get_communities.php";
require_once "includes/get_menu_pages.php";
require_once "includes/get_settings.php";

$currentPage = 'elders';
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

$eldersMeta = "Meet the elders of Garrison Methodist Presbyterian Church who provide spiritual leadership and guidance.";
$eldersList = [];
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT meta_description FROM pages WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute(['elders']);
    $row = $stmt->fetch();
    if ($row && !empty($row['meta_description'])) {
        $eldersMeta = $row['meta_description'];
    }
    $elist = $conn->query("SELECT * FROM elders WHERE status = 'active' ORDER BY sort_order ASC, created_at DESC");
    $eldersList = $elist->fetchAll();
} catch (PDOException $e) {
    error_log("Elders page fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($eldersMeta); ?>">
    <title>Church Elders - Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
        .directory-section {
            padding: 70px 0;
            background: #f8f9fb;
        }
        .directory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 22px;
        }
        .directory-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 18px;
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 18px;
            align-items: start;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .directory-card:hover {
            transform: translateY(-4px);
            border-color: rgba(93,173,236,0.35);
            box-shadow: 0 14px 32px rgba(15, 23, 42, 0.12);
        }
        .directory-avatar {
            width: 140px;
            height: 140px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 6px 14px rgba(0,0,0,0.15);
            flex-shrink: 0;
        }
        .directory-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .directory-info h3 {
            margin: 0 0 4px 0;
            color: #0f172a;
            font-size: 1.1rem;
        }
        .directory-title {
            margin: 0 0 10px 0;
            color: #5DADEC;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .directory-line {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin: 6px 0;
            color: #4b5563;
            font-size: 0.95rem;
            line-height: 1.4;
        }
        .directory-line i {
            color: #5DADEC;
            margin-top: 2px;
        }
        .directory-bio {
            margin-top: 8px;
            color: #4b5563;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }
        @media (min-width: 992px) {
            .directory-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 900px) {
            .directory-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
            .directory-card {
                grid-template-columns: 1fr;
            }
            .directory-avatar {
                width: 100%;
                height: 220px;
            }
        }
    </style>
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
            <h1>Church Elders</h1>
            <p>Meet the dedicated elders who provide spiritual leadership and guidance to our congregation</p>
        </div>
    </section>

    <section class="directory-section">
        <div class="container">
            <div class="section-header">
                <h2>Our Elders</h2>
                <p>Servant leaders committed to shepherding our church family</p>
            </div>
            <?php if (count($eldersList) > 0): ?>
                <div class="directory-grid">
                    <?php foreach ($eldersList as $elder): ?>
                        <div class="directory-card">
                            <div class="directory-avatar">
                                <?php if (!empty($elder['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($elder['image_url']); ?>" alt="<?php echo htmlspecialchars($elder['name']); ?>">
                                <?php else: ?>
                                    <img src="./images/placeholder-avatar.png" alt="<?php echo htmlspecialchars($elder['name']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="directory-info">
                                <h3><?php echo htmlspecialchars($elder['name']); ?></h3>
                                <?php if (!empty($elder['title'])): ?><p class="directory-title"><?php echo htmlspecialchars($elder['title']); ?></p><?php endif; ?>
                                <?php if (!empty($elder['email'])): ?><div class="directory-line"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($elder['email']); ?></div><?php endif; ?>
                                <?php if (!empty($elder['location'])): ?><div class="directory-line"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($elder['location']); ?></div><?php endif; ?>
                                <?php if (!empty($elder['phone'])): ?><div class="directory-line"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($elder['phone']); ?></div><?php endif; ?>
                                <?php if (!empty($elder['bio'])): ?><p class="directory-bio"><?php echo nl2br(htmlspecialchars($elder['bio'])); ?></p><?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align:center; color:#666; padding:30px;">No elders have been added yet.</p>
            <?php endif; ?>
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
                        <li><a href="church-history.php">Church History</a></li>
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
