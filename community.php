<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/block_renderer.php';
require_once __DIR__ . '/includes/get_settings.php';

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get the slug from the URL or an override set by per-community files
$slug = isset($communitySlug) ? sanitizeInput($communitySlug) : (isset($_GET['slug']) ? sanitizeInput($_GET['slug']) : '');
$slug = strtolower($slug);
if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    exit('Community slug is missing.');
}

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

$currentPage = 'community';
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
$contentSource = $community['full_content'] ?: $community['description'];
$renderedBlocks = $contentSource ? render_blocks($contentSource) : '';

// Community-specific posts
$communityPosts = [];
try {
    $stmt = $conn->prepare("SELECT title, slug, excerpt, image_url, published_at, created_at FROM posts WHERE status = 'published' AND community_id = ? ORDER BY COALESCE(published_at, created_at) DESC");
    $stmt->execute([$community['id']]);
    $communityPosts = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Community posts fetch error: ' . $e->getMessage());
}
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
    <link rel="icon" type="image/x-icon" href="/images/favicon.ico">
    <style>
        .community-hero {
            position: relative;
            padding: 70px 0;
            background: linear-gradient(135deg, rgba(15,31,68,0.9), rgba(39,88,155,0.88)), url('images/1825375401006796.jpg') center/cover no-repeat;
            color: #fff;
            overflow: hidden;
        }
        .community-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at 20% 20%, rgba(226,179,77,0.22), transparent 35%),
                        radial-gradient(circle at 80% 10%, rgba(93,173,236,0.2), transparent 30%);
        }
        .community-hero .container { position: relative; z-index: 1; }
        .community-hero h1 { margin: 0 0 10px 0; color: #fff; }
        .community-hero p { max-width: 780px; color: #e5e7eb; }

        .community-layout {
            display: grid;
            grid-template-columns: minmax(0, 2.6fr) minmax(320px, 1fr);
            gap: 38px;
            align-items: start;
        }
        .community-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 14px 34px rgba(15,23,42,0.1);
            line-height: 1.75;
            max-width: 820px;
        }
        .community-card h2,
        .community-card h3,
        .community-card h4 {
            color: #12345c;
            margin-top: 0;
            margin-bottom: 10px;
            position: relative;
            display: inline-block;
            padding-bottom: 4px;
        }
        .community-card h2::after,
        .community-card h3::after,
        .community-card h4::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 42%;
            height: 3px;
            background: linear-gradient(90deg, #1f4e8c, #e2b34d);
            border-radius: 999px;
        }
        .community-card p {
            color: #1f2937;
            margin: 0 0 1rem;
        }
        .community-card ul {
            margin: 0 0 1.2rem 1.2rem;
            color: #1f2937;
        }
        .community-card ul li {
            margin-bottom: 0.5rem;
        }
        .community-card blockquote {
            margin: 14px 0;
            padding: 14px 16px;
            border-left: 4px solid #1f4e8c;
            background: #f6f8fb;
            color: #12345c;
            border-radius: 10px;
        }
        .community-card > *:not(:last-child) {
            margin-bottom: 1.2rem;
        }
        .community-card a {
            color: #1f4e8c;
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s ease;
        }
        .community-card a:hover {
            border-color: #e2b34d;
        }
        .community-sidebar {
            background: #f8f9fb;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 14px 16px;
            box-shadow: 0 10px 24px rgba(15,23,42,0.08);
            max-height: 1040px;
            overflow-y: auto;
        }
        .community-article-card {
            text-decoration: none;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            background: #fff;
            box-shadow: 0 6px 16px rgba(15,23,42,0.06);
            color: #0f172a;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .community-article-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 26px rgba(15,23,42,0.12);
            border-color: rgba(93,173,236,0.35);
        }
        .community-article-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 8px;
        }
        .community-article-meta {
            font-size: 0.85rem;
            color: #4b5563;
            margin-bottom: 4px;
        }
        .community-article-title {
            font-weight: 700;
            margin-bottom: 4px;
            color: #0f172a;
        }
        .community-article-excerpt {
            color: #4b5563;
            font-size: 0.95rem;
        }
        @media (max-width: 900px) {
            .community-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="<?php echo htmlspecialchars($community['slug']); ?>">
        <div class="scrolling-banner"><span class="banner-item">Loading announcements...</span></div>
    </div>

    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <section class="community-hero">
        <div class="container">
            <h1><?php echo htmlspecialchars($community['name']); ?></h1>
            <p><?php echo htmlspecialchars($community['description']); ?></p>
        </div>
    </section>

    <section class="custom-page-content" style="padding: 60px 0;">
        <div class="container">
            <div class="page-content-wrapper community-layout">
                <div class="community-card">
                    <?php if ($renderedBlocks): ?>
                        <div class="community-full-content">
                            <?php echo $renderedBlocks; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (count($communityPosts) > 0): ?>
                    <div>
                        <h3 style="margin:0 0 10px 0;">Latest Articles</h3>
                        <aside class="community-sidebar">
                        <div style="display:flex; flex-direction:column; gap:12px;">
                            <?php foreach ($communityPosts as $post): ?>
                                <a class="community-article-card" href="post.php?slug=<?php echo urlencode($post['slug']); ?>">
                                    <?php if (!empty($post['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                    <?php endif; ?>
                                    <div class="community-article-meta"><?php echo date('M j, Y', strtotime($post['published_at'] ?: $post['created_at'])); ?></div>
                                    <div class="community-article-title"><?php echo htmlspecialchars($post['title']); ?></div>
                                    <div class="community-article-excerpt"><?php echo htmlspecialchars($post['excerpt'] ?: 'Read more'); ?></div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        </aside>
                    </div>
                <?php endif; ?>
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
