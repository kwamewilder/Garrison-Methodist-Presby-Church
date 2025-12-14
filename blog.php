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

$filterCommunity = isset($_GET['community']) ? trim($_GET['community']) : '';
$posts = [];
$communitiesList = [];

try {
    $conn = getDBConnection();
    $communitiesList = $conn->query("SELECT id, name, slug FROM communities WHERE status = 'active' ORDER BY name")->fetchAll();

    if ($filterCommunity) {
        $stmt = $conn->prepare("SELECT p.*, c.slug as cslug, c.name as cname FROM posts p LEFT JOIN communities c ON p.community_id = c.id WHERE p.status = 'published' AND c.slug = ? ORDER BY COALESCE(p.published_at, p.created_at) DESC");
        $stmt->execute([$filterCommunity]);
    } else {
        $stmt = $conn->query("SELECT p.*, c.slug as cslug, c.name as cname FROM posts p LEFT JOIN communities c ON p.community_id = c.id WHERE p.status = 'published' ORDER BY COALESCE(p.published_at, p.created_at) DESC");
    }
    $posts = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Articles fetch error: ' . $e->getMessage());
}

function postDate($row) {
    $dt = $row['published_at'] ?: $row['created_at'];
    return date('M j, Y', strtotime($dt));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Articles - Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
        .blog-grid {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .blog-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 14px 30px rgba(15,23,42,0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            display: grid;
            grid-template-columns: minmax(0, 1fr);
        }
        .blog-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 40px rgba(15,23,42,0.12);
            border-color: rgba(93,173,236,0.35);
        }
        .blog-thumb {
            height: 220px;
            overflow: hidden;
        }
        .blog-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .blog-body {
            padding: 16px 18px 18px;
        }
        .blog-meta {
            display: flex;
            gap: 12px;
            align-items: center;
            color: #4b5563;
            font-size: 0.9rem;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e8f2ff;
            color: #1f4e8c;
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        .blog-body h3 {
            margin: 0 0 8px 0;
            font-size: 1.1rem;
            color: #0f172a;
        }
        .blog-body p {
            margin: 0 0 10px 0;
            color: #4b5563;
        }
        .blog-filters {
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .filter-chip {
            border: 1px solid #e5e7eb;
            padding: 8px 14px;
            border-radius: 999px;
            background: #fff;
            cursor: pointer;
            text-decoration: none;
            color: #0f172a;
        }
        @media (min-width: 900px) {
            .blog-card {
                grid-template-columns: 280px 1fr;
                align-items: stretch;
            }
            .blog-thumb {
                height: 100%;
            }
        }
        .filter-chip.active {
            background: #1f4e8c;
            color: #fff;
            border-color: #1f4e8c;
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
            <h1>Articles</h1>
            <p>Stories and updates from our church and communities</p>
        </div>
    </section>

    <section style="padding: 60px 0;">
        <div class="container">
            <?php if (count($communitiesList) > 0): ?>
            <div class="blog-filters">
                <a class="filter-chip <?php echo $filterCommunity ? '' : 'active'; ?>" href="blog.php">All</a>
                <?php foreach ($communitiesList as $c): ?>
                    <a class="filter-chip <?php echo ($filterCommunity === $c['slug']) ? 'active' : ''; ?>" href="blog.php?community=<?php echo urlencode($c['slug']); ?>">
                        <?php echo htmlspecialchars($c['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (count($posts) > 0): ?>
                <div class="blog-grid">
                    <?php foreach ($posts as $post): ?>
                        <a class="blog-card" href="post.php?slug=<?php echo urlencode($post['slug']); ?>">
                            <?php if (!empty($post['image_url'])): ?>
                                <div class="blog-thumb">
                                    <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                </div>
                            <?php endif; ?>
                            <div class="blog-body">
                                <div class="blog-meta">
                                    <span class="pill"><i class="fas fa-calendar"></i> <?php echo postDate($post); ?></span>
                                    <?php if (!empty($post['cname'])): ?><span class="pill"><i class="fas fa-users"></i> <?php echo htmlspecialchars($post['cname']); ?></span><?php endif; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                                <p><?php echo htmlspecialchars($post['excerpt'] ?: 'Read more'); ?></p>
                                <span class="pill" style="background:#eef2f7; color:#0f172a;"><i class="fas fa-arrow-right"></i> Read</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align:center; color:#666; padding:30px 0;">No posts yet.</p>
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
