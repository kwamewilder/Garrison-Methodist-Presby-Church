<?php
require_once "config.php";
require_once "includes/block_renderer.php";
require_once "includes/get_communities.php";
require_once "includes/get_menu_pages.php";
require_once "includes/get_settings.php";

$currentPage = 'blog';
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

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$post = null;

try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT p.*, c.name as cname, c.slug as cslug FROM posts p LEFT JOIN communities c ON p.community_id = c.id WHERE p.slug = ? AND p.status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Post fetch error: ' . $e->getMessage());
}

if (!$post) {
    header("HTTP/1.0 404 Not Found");
    echo "Post not found.";
    exit;
}

function postDateFull($row) {
    $dt = $row['published_at'] ?: $row['created_at'];
    return date('F j, Y', strtotime($dt));
}

function renderPostContent($content) {
    $decoded = json_decode($content, true);
    if ($decoded && is_array($decoded)) {
        echo render_blocks($content);
    } else {
        echo '<div style="color:#374151; line-height:1.7;">' . nl2br(htmlspecialchars($content)) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($post['title']); ?> - GMPC Articles</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
        .post-hero {
            height: 320px;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(15,23,42,0.16);
            margin-bottom: 24px;
        }
        .post-hero img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .post-meta {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin: 10px 0 16px;
        }
        .post-pill {
            background: #e8f2ff;
            color: #1f4e8c;
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="articles">
        <div class="scrolling-banner"><span class="banner-item">Loading announcements...</span></div>
    </div>

    <?php include 'includes/navbar.php'; ?>

    <section class="page-header">
        <div class="container">
            <h1><?php echo htmlspecialchars($post['title']); ?></h1>
            <p><?php echo htmlspecialchars($post['excerpt'] ?? ''); ?></p>
        </div>
    </section>

    <section style="padding: 50px 0;">
        <div class="container" style="max-width: 960px;">
            <?php if (!empty($post['image_url'])): ?>
                <div class="post-hero">
                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                </div>
            <?php endif; ?>

            <div class="post-meta">
                <span class="post-pill"><i class="fas fa-calendar"></i> <?php echo postDateFull($post); ?></span>
                <?php if (!empty($post['author'])): ?><span class="post-pill"><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author']); ?></span><?php endif; ?>
                <?php if (!empty($post['cname'])): ?><span class="post-pill"><i class="fas fa-users"></i> <?php echo htmlspecialchars($post['cname']); ?></span><?php endif; ?>
            </div>

            <div class="post-content">
                <?php renderPostContent($post['content'] ?? ''); ?>
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
