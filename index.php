<?php
require_once 'config.php';
require_once 'includes/get_communities.php';
require_once 'includes/get_menu_pages.php';
require_once 'includes/block_renderer.php';
require_once 'includes/get_settings.php';

$currentPage = 'home';
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
$youtubeSubscribeUrl = $youtubeUrl
    ? $youtubeUrl . (strpos($youtubeUrl, '?') === false ? '?sub_confirmation=1' : '&sub_confirmation=1')
    : 'https://www.youtube.com/@gmpcburmacamp3405?sub_confirmation=1';

$serviceDetails = [
    [
        'title' => 'Sunday Service',
        'time' => $serviceSunday ?: 'TBD',
        'meta' => 'Every Sunday • Main Auditorium',
        'description' => 'Traditional and contemporary worship with prayer and the Word.'
    ],
    [
        'title' => 'Tuesday Service',
        'time' => $serviceTuesday ?: 'TBD',
        'meta' => 'Every Tuesday • Main Auditorium',
        'description' => 'Midweek fellowship, hymns, and encouragement for the journey.'
    ],
    [
        'title' => 'Counselling Session',
        'time' => $serviceCounselling ?: 'By appointment',
        'meta' => 'Book through church office',
        'description' => 'Personalised spiritual guidance, prayer, and support.'
    ],
];

$homepageEvents = [];
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM events WHERE status = 'published' AND event_date >= CURDATE() ORDER BY event_date ASC, event_time ASC LIMIT 3");
    $stmt->execute();
    $homepageEvents = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Homepage events fetch error: ' . $e->getMessage());
}

$latestArticles = [];
try {
    // reuse connection if available
    if (!isset($conn)) {
        $conn = getDBConnection();
    }
    $stmt = $conn->query("SELECT title, slug, excerpt, image_url, published_at, created_at FROM posts WHERE status = 'published' ORDER BY COALESCE(published_at, created_at) DESC LIMIT 6");
    $latestArticles = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Homepage articles fetch error: ' . $e->getMessage());
}

function homepageEventDay($date) {
    return date('d', strtotime($date));
}
function homepageEventMonth($date) {
    return date('M', strtotime($date));
}
function homepageEventTime($time) {
    return $time ? date('g:i A', strtotime($time)) : 'TBD';
}
function homepageEventTextFromBlocks($blocks) {
    $parts = [];
    foreach ($blocks as $block) {
        $data = $block['data'] ?? [];
        switch ($block['type'] ?? '') {
            case 'heading':
            case 'paragraph':
                $parts[] = $data['text'] ?? '';
                break;
            case 'list':
                if (!empty($data['items']) && is_array($data['items'])) {
                    $parts = array_merge($parts, $data['items']);
                }
                break;
            case 'quote':
                if (!empty($data['text'])) {
                    $parts[] = $data['text'];
                }
                if (!empty($data['caption'])) {
                    $parts[] = $data['caption'];
                }
                break;
            default:
                if (!empty($data['text'])) {
                    $parts[] = $data['text'];
                }
        }
    }
    $plain = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts))));
    return $plain;
}
function homepageEventSummary($description, $limit = 140) {
    $summary = '';

    if (is_array($description)) {
        $summary = homepageEventTextFromBlocks($description);
    } elseif (is_string($description) && $description !== '') {
        $decoded = json_decode($description, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $decoded = json_decode(html_entity_decode($description, ENT_QUOTES, 'UTF-8'), true);
        }
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($decoded['blocks']) && is_array($decoded['blocks'])) {
                $summary = homepageEventTextFromBlocks($decoded['blocks']);
            } elseif (is_array($decoded)) {
                $summary = homepageEventTextFromBlocks($decoded);
            }
        }
        if ($summary === '') {
            $summary = strip_tags($description);
        }
    }

    $summary = trim(preg_replace('/\s+/', ' ', $summary));
    if ($summary === '') {
        $summary = 'We look forward to seeing you there.';
    }

    if (strlen($summary) > $limit) {
        $summary = rtrim(substr($summary, 0, $limit), " \t\n\r\0\x0B.,;") . '...';
    }

    return $summary;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Welcome to Garrison Methodist Presbyterian Church. Join us for worship, fellowship, and spiritual growth. A community of faith, hope, and love serving God together.">
    <title>Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }
        .article-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 14px 32px rgba(15,23,42,0.1);
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            text-decoration: none;
            color: #0f172a;
        }
        .article-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 40px rgba(15,23,42,0.12);
            border-color: rgba(93,173,236,0.35);
        }
        .article-thumb {
            position: relative;
            height: 210px;
            overflow: hidden;
        }
        .article-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .article-date-badge {
            position: absolute;
            bottom: 12px;
            left: 12px;
            background: var(--gradient-primary);
            color: #fff;
            padding: 8px 12px;
            border-radius: 10px;
            font-weight: 800;
            display: inline-flex;
            gap: 10px;
            align-items: center;
            letter-spacing: 0.5px;
            box-shadow: 0 10px 24px rgba(15,23,42,0.25);
        }
        .article-date-badge .day { font-size: 1.2rem; }
        .article-date-badge .month { font-size: 0.8rem; text-transform: uppercase; }
        .article-body {
            padding: 18px;
        }
        .article-body h3 {
            margin: 0 0 10px 0;
            font-size: 1rem;
            color: #0f172a;
            line-height: 1.4;
            font-weight: 700;
        }
        .article-body p {
            margin: 0;
            color: #4b5563;
            font-size: 0.94rem;
        }
    </style>

</head>
<body>
    <!-- Scrolling Banner -->
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="homepage">
        <div class="scrolling-banner">
            <span class="banner-item">Loading announcements...</span>
        </div>
    </div>

    <!-- Header -->
    <?php include 'includes/navbar.php'; ?>
    <!-- Events Section -->

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Welcome to Garrison Methodist Presbyterian Church</h1>
            <p class="hero-subtitle">A community of faith, hope, and love serving God together</p>
            <div class="hero-buttons">
                <a href="contact.php" class="btn btn-primary">Join Us Sunday</a>
                <a href="about.php" class="btn btn-secondary">Learn More</a>
            </div>
        </div>
        <div class="hero-scroll">
            <i class="fas fa-chevron-down"></i>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <div class="section-header">
                <h2>Sunday Services</h2>
                <p>Join us for worship, fellowship, and spiritual growth</p>
            </div>
            <div class="services-grid">
                <?php foreach ($serviceDetails as $service): ?>
                    <div class="service-card">
                        <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                        <p class="service-time"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($service['time']); ?></p>
                        <p class="service-meta"><?php echo htmlspecialchars($service['meta']); ?></p>
                        <p><?php echo htmlspecialchars($service['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Welcome Section -->
    <section class="welcome">
        <div class="container">
            <div class="welcome-content">
                <div class="welcome-text">
                    <h2>Welcome Home</h2>
                    <p>At Garrison Methodist Presbyterian Church, we believe that everyone is welcome in God's house. Whether you're seeking spiritual growth, community connection, or simply a place to belong, you'll find a warm welcome here.</p>
                    <p>Our church family is committed to loving God, loving others, and serving our community with open hearts and hands.</p>
                    <div class="welcome-features">
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>All are welcome</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Biblical teaching</span>
                        </div>
                        <div class="feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Community focused</span>
                        </div>
                    </div>
                    <a href="about.php" class="btn btn-primary">Our Story</a>
                </div>
                <div class="welcome-image">
                    <img src="./images/1825375401006796.jpg" alt="Church Interior - a view of the pews and stained glass windows">
                </div>
            </div>
        </div>
    </section>

    <!-- Articles Section -->
    <section class="ministries">
        <div class="container">
            <div class="section-header">
                <h2>Latest Articles</h2>
                <p>Stay updated with news and stories from our church</p>
            </div>
            <?php if (count($latestArticles) > 0): ?>
                <div class="articles-grid">
                    <?php foreach ($latestArticles as $article): 
                        $date = $article['published_at'] ?: $article['created_at'];
                        $day = date('d', strtotime($date));
                        $month = date('M', strtotime($date));
                        $img = !empty($article['image_url']) ? $article['image_url'] : 'images/placeholder-avatar.png';
                    ?>
                    <a class="article-card" href="post.php?slug=<?php echo urlencode($article['slug']); ?>">
                        <div class="article-thumb">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                            <div class="article-date-badge">
                                <span class="day"><?php echo $day; ?></span>
                                <span class="month"><?php echo strtoupper($month); ?></span>
                            </div>
                        </div>
                        <div class="article-body">
                            <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                            <p><?php echo htmlspecialchars($article['excerpt'] ?: 'Read more'); ?></p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align:center; color:#666; padding:24px 0;">No articles yet. Check back soon.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Events Section -->
    <section class="events">
        <div class="container">
            <div class="section-header">
                <h2>Upcoming Events</h2>
                <p>Join us for these special gatherings</p>
            </div>
            <?php if (count($homepageEvents) > 0): ?>
                <div class="events-grid">
                    <?php foreach ($homepageEvents as $event): 
                        $eventImage = !empty($event['image_url'])
                            ? $event['image_url']
                            : 'https://images.pexels.com/photos/1587927/pexels-photo-1587927.jpeg?auto=compress&cs=tinysrgb&w=1200';
                        $eventSummaryText = homepageEventSummary($event['description'] ?? '', 160);
                    ?>
                        <div class="event-card">
                            <div class="event-thumb">
                                <img src="<?php echo htmlspecialchars($eventImage); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <div class="event-date-badge">
                                    <span class="day"><?php echo homepageEventDay($event['event_date']); ?></span>
                                    <span class="month"><?php echo homepageEventMonth($event['event_date']); ?></span>
                                </div>
                            </div>
                            <div class="event-content">
                                <p class="event-eyebrow">Upcoming gathering</p>
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <div class="event-meta-chips">
                                    <span class="event-chip"><i class="fas fa-clock"></i> <?php echo homepageEventTime($event['event_time']); ?></span>
                                    <?php if (!empty($event['location'])): ?>
                                        <span class="event-chip"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p class="event-summary"><?php echo htmlspecialchars($eventSummaryText); ?></p>
                                <div class="event-footer">
                                    <span class="event-tag">All are welcome</span>
                                    <a class="event-cta-link" href="events.php">See details <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align:center; color:#666; padding:30px 0;">No upcoming events at the moment. Please check back soon.</p>
            <?php endif; ?>
            <div class="events-cta">
                <a href="events.php" class="btn btn-primary">View All Events</a>
            </div>
        </div>
    </section>

    <!-- YouTube CTA -->
    <section class="youtube-cta">
        <div class="container">
            <div class="youtube-card">
                <div class="youtube-card__content">
                    <div class="youtube-card__icon">
                        <i class="fab fa-youtube"></i>
                    </div>
                    <div class="youtube-card__text">
                        <p class="eyebrow">Watch & Connect</p>
                        <h3>Join us on YouTube</h3>
                        <p>Livestreams, sermons, and highlights from <?php echo htmlspecialchars($churchName); ?>.</p>
                    </div>
                </div>
                <div class="youtube-card__actions">
                    <a class="btn btn-secondary btn-light" href="<?php echo htmlspecialchars($youtubeUrl); ?>" target="_blank" rel="noopener">Visit Channel</a>
                    <a class="btn btn-subscribe" href="<?php echo htmlspecialchars($youtubeSubscribeUrl); ?>" target="_blank" rel="noopener">Subscribe</a>
                    <a class="btn btn-facebook" href="<?php echo htmlspecialchars($facebookUrl ?: 'https://www.facebook.com/gmpcburmacamp'); ?>" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i> Follow</a>
                    <a class="btn btn-tiktok" href="<?php echo htmlspecialchars($tiktokUrl ?: 'https://www.tiktok.com/@gmpcburmacamp'); ?>" target="_blank" rel="noopener"><i class="fab fa-tiktok"></i> Follow</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-preview">
        <div class="container">
            <div class="contact-content">
                <div class="contact-info">
                    <h2>Visit Us</h2>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Address</h4>
                            <p>Garrison Methodist Presby Church<br>Burma Camp, Accra</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Phone</h4>
                            <p>(+233) 244-3689</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>info@garrisonmpc.org</p>
                        </div>
                    </div>
                </div>
                <div class="contact-cta">
                    <h3>We'd Love to Meet You</h3>
                    <p>Come as you are and experience the love of Christ in our community</p>
                    <a href="contact.php" class="btn btn-secondary">Get Directions</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
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
