<?php
require_once 'config.php';
require_once "includes/get_communities.php";
require_once "includes/get_menu_pages.php";

// Sanitize input helper
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get sermons from database
$featuredSermon = null;
$sermons = [];
$menuPages = [];

try {
    $conn = getDBConnection();

    // Get featured sermon, or most recent if no featured sermon is set
    $featuredStmt = $conn->prepare("SELECT * FROM sermons WHERE status = 'published' AND featured = 1 LIMIT 1");
    $featuredStmt->execute();
    $featuredSermon = $featuredStmt->fetch();

    // If no featured sermon, get the most recent one
    if (!$featuredSermon) {
        $featuredStmt = $conn->prepare("SELECT * FROM sermons WHERE status = 'published' ORDER BY sermon_date DESC LIMIT 1");
        $featuredStmt->execute();
        $featuredSermon = $featuredStmt->fetch();
    }

    // Get category filter if set (series for now, can be expanded)
    $series = isset($_GET['series']) ? sanitizeInput($_GET['series']) : 'all';

    // Get all published sermons
    if ($series === 'all') {
        $sermonsStmt = $conn->prepare("SELECT * FROM sermons WHERE status = 'published' ORDER BY sermon_date DESC");
        $sermonsStmt->execute();
    } else {
        $sermonsStmt = $conn->prepare("SELECT * FROM sermons WHERE series = ? AND status = 'published' ORDER BY sermon_date DESC");
        $sermonsStmt->execute([$series]);
    }
    $sermons = $sermonsStmt->fetchAll();

    // Get unique series for filtering
    $seriesStmt = $conn->query("SELECT DISTINCT series FROM sermons WHERE series IS NOT NULL AND series != '' AND status = 'published' ORDER BY series");
    $seriesList = $seriesStmt->fetchAll();

    // Get menu pages for navigation
    try {
        $stmt = $conn->query("SELECT title, page_slug FROM pages WHERE status = 'published' ORDER BY id ASC LIMIT 5");
        $menuPages = $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching menu pages: " . $e->getMessage());
        $menuPages = [];
    }

} catch(PDOException $e) {
    error_log("Error fetching sermons: " . $e->getMessage());
    $featuredSermon = null;
    $sermons = [];
    $seriesList = [];
}

// Helper function to format date
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

// Helper function to extract YouTube video ID
function getYouTubeId($url) {
    if (empty($url)) return null;
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $url, $match);
    return isset($match[1]) ? $match[1] : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Listen to inspiring sermons and messages from the pastoral team at Garrison Methodist Presbyterian Church. Catch up on recent messages and series.">
    <title>Sermons - Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 30px;
            justify-content: center;
        }
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #5DADEC;
            background: white;
            color: #5DADEC;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 500;
        }
        .filter-btn:hover,
        .filter-btn.active {
            background: #5DADEC;
            color: white;
        }
        .sermon-meta {
            display: flex;
            gap: 15px;
            margin: 10px 0;
            flex-wrap: wrap;
        }
        .sermon-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
            color: #666;
        }
        .sermon-meta-item i {
            color: #5DADEC;
        }
        .media-links {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .media-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            background: #5DADEC;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .media-link:hover {
            background: #4a9dd8;
            transform: translateY(-2px);
        }
        .no-sermons {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .no-sermons i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }

        /* Modal Styles */
        .sermon-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .sermon-modal.active {
            display: flex;
        }
        .sermon-modal-content {
            background: #000;
            padding: 10px;
            border-radius: 0;
            max-width: 100%;
            max-height: 100%;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .sermon-modal-content h3 {
            margin: 0 0 15px 0;
            color: #fff;
            font-size: 1.5rem;
            text-align: center;
        }
        .sermon-modal-close {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 2.5rem;
            color: white;
            cursor: pointer;
            background: rgba(255,255,255,0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            z-index: 10001;
        }
        .sermon-modal-close:hover {
            background: rgba(255,255,255,0.2);
        }
        #videoContainer {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #videoContainer iframe,
        #videoContainer video {
            width: 95vw;
            height: 90vh;
            max-width: 95vw;
            max-height: 90vh;
            border: none;
        }

        /* Fix video thumbnail size in sermon cards */
        .sermon-thumbnail {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        .sermon-thumbnail:hover {
            transform: scale(1.02);
        }
        .sermon-thumbnail iframe {
            width: 100% !important;
            height: 100% !important;
            border-radius: 10px 10px 0 0;
        }
        .play-button {
            transition: all 0.3s ease;
        }
        .sermon-thumbnail:hover .play-button {
            transform: translate(-50%, -50%) scale(1.1);
            background: rgba(93, 173, 236, 1) !important;
        }
        .sermon-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 10px 0;
        }

        /* Featured sermon hover effects */
        .sermon-video:hover .play-button {
            transform: translate(-50%, -50%) scale(1.1) !important;
            background: rgba(93, 173, 236, 1) !important;
        }
        .sermon-video:hover img {
            opacity: 0.95;
        }
    </style>
</head>
<body>
    <!-- Scrolling Banner -->
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="sermons">
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
                        <a href="sermons.php" class="nav-link active">Sermons</a>
                    </li>
                    <li class="nav-item">
                        <a href="events.php" class="nav-link">Events</a>
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
                    <?php if (count($menuPages) > 0): ?>
                        <?php foreach ($menuPages as $menuPage): ?>
                            <li class="nav-item">
                                <a href="page.php?slug=<?php echo urlencode($menuPage['page_slug']); ?>" class="nav-link">
                                    <?php echo htmlspecialchars($menuPage['title']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
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
            <h1>Sermons & Messages</h1>
            <p>Listen to inspiring messages from our pastoral team and guest speakers</p>
        </div>
    </section>

    <?php if ($featuredSermon): ?>
    <!-- Featured Sermon -->
    <section class="featured-sermon">
        <div class="container">
            <div class="featured-content" style="display: block; max-width: 900px; margin: 0 auto;">
                <div class="sermon-video" style="position: relative; cursor: pointer; margin-bottom: 30px;"
                     onclick="<?php
                        if ($featuredSermon['video_url']) {
                            echo "openSermonVideo('" . htmlspecialchars($featuredSermon['video_url'], ENT_QUOTES) . "', '" . htmlspecialchars($featuredSermon['title'], ENT_QUOTES) . "')";
                        } elseif ($featuredSermon['audio_url']) {
                            echo "openSermonAudio('" . htmlspecialchars($featuredSermon['audio_url'], ENT_QUOTES) . "', '" . htmlspecialchars($featuredSermon['title'], ENT_QUOTES) . "')";
                        }
                     ?>">
                    <?php
                    $videoId = getYouTubeId($featuredSermon['video_url']);
                    if ($videoId):
                    ?>
                        <img src="https://img.youtube.com/vi/<?php echo $videoId; ?>/maxresdefault.jpg"
                             alt="<?php echo htmlspecialchars($featuredSermon['title']); ?>"
                             style="width: 100%; border-radius: 10px; display: block;">
                        <div class="play-button" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(93, 173, 236, 0.9); width: 90px; height: 90px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                            <i class="fas fa-play" style="font-size: 2.5rem; color: white; margin-left: 8px;"></i>
                        </div>
                    <?php elseif ($featuredSermon['audio_url']): ?>
                        <img src="https://images.pexels.com/photos/8923734/pexels-photo-8923734.jpeg?auto=compress&cs=tinysrgb&w=1200"
                             alt="<?php echo htmlspecialchars($featuredSermon['title']); ?>"
                             style="width: 100%; border-radius: 10px; display: block;">
                        <div class="play-button" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(93, 173, 236, 0.9); width: 90px; height: 90px; border-radius: 50%; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                            <i class="fas fa-play" style="font-size: 2.5rem; color: white; margin-left: 8px;"></i>
                        </div>
                    <?php else: ?>
                        <img src="https://images.pexels.com/photos/8923734/pexels-photo-8923734.jpeg?auto=compress&cs=tinysrgb&w=1200"
                             alt="<?php echo htmlspecialchars($featuredSermon['title']); ?>"
                             style="width: 100%; border-radius: 10px; display: block;">
                    <?php endif; ?>
                </div>
                <div class="sermon-info">
                    <span class="sermon-date" style="display: block; margin-bottom: 10px;"><?php echo formatDate($featuredSermon['sermon_date']); ?></span>
                    <h2 style="margin-bottom: 10px;"><?php echo htmlspecialchars($featuredSermon['title']); ?></h2>
                    <p class="sermon-speaker" style="margin-bottom: 15px;"><?php echo htmlspecialchars($featuredSermon['preacher']); ?></p>
                    <?php if (!empty($featuredSermon['scripture_reference'])): ?>
                        <p class="sermon-meta-item" style="margin-bottom: 15px;"><i class="fas fa-book-open"></i> <?php echo htmlspecialchars($featuredSermon['scripture_reference']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($featuredSermon['description'])): ?>
                        <p class="sermon-description" style="margin-bottom: 20px;"><?php echo htmlspecialchars($featuredSermon['description']); ?></p>
                    <?php endif; ?>
                    <div class="media-links">
                        <?php if ($featuredSermon['audio_url']): ?>
                            <button onclick="openSermonAudio('<?php echo htmlspecialchars($featuredSermon['audio_url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($featuredSermon['title'], ENT_QUOTES); ?>')" class="media-link" style="border: none; cursor: pointer;">
                                <i class="fas fa-microphone"></i> Listen
                            </button>
                        <?php endif; ?>
                        <?php if ($featuredSermon['video_url']): ?>
                            <button onclick="openSermonVideo('<?php echo htmlspecialchars($featuredSermon['video_url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($featuredSermon['title'], ENT_QUOTES); ?>')" class="media-link" style="border: none; cursor: pointer;">
                                <i class="fas fa-video"></i> Watch
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sermons List -->
    <section class="sermon-series">
        <div class="container">
            <div class="section-header">
                <h2>All Sermons</h2>
                <p>Browse our collection of sermons and messages</p>
            </div>

            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <a href="sermons.php?series=all" class="filter-btn <?php echo $series === 'all' ? 'active' : ''; ?>">All Sermons</a>
                <?php if (!empty($seriesList)): ?>
                    <?php foreach ($seriesList as $seriesItem): ?>
                        <a href="sermons.php?series=<?php echo urlencode($seriesItem['series']); ?>"
                           class="filter-btn <?php echo $series === $seriesItem['series'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($seriesItem['series']); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (count($sermons) > 0): ?>
            <div class="series-grid">
                <?php foreach ($sermons as $sermon): ?>
                <div class="sermon-card" style="display: flex; flex-direction: column; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Thumbnail at Top -->
                    <div class="sermon-thumbnail" style="cursor: pointer; position: relative; width: 100%; height: 220px; overflow: hidden;"
                         onclick="<?php
                            if ($sermon['video_url']) {
                                echo "openSermonVideo('" . htmlspecialchars($sermon['video_url'], ENT_QUOTES) . "', '" . htmlspecialchars($sermon['title'], ENT_QUOTES) . "')";
                            } elseif ($sermon['audio_url']) {
                                echo "openSermonAudio('" . htmlspecialchars($sermon['audio_url'], ENT_QUOTES) . "', '" . htmlspecialchars($sermon['title'], ENT_QUOTES) . "')";
                            }
                         ?>">
                        <?php
                        $videoId = getYouTubeId($sermon['video_url']);
                        if ($videoId):
                        ?>
                            <img src="https://img.youtube.com/vi/<?php echo $videoId; ?>/maxresdefault.jpg"
                                 alt="<?php echo htmlspecialchars($sermon['title']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                            <div class="play-button" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(93, 173, 236, 0.9); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-play" style="font-size: 2.2rem; color: white; margin-left: 6px;"></i>
                            </div>
                        <?php elseif ($sermon['audio_url']): ?>
                            <img src="https://images.pexels.com/photos/8923734/pexels-photo-8923734.jpeg?auto=compress&cs=tinysrgb&w=600"
                                 alt="<?php echo htmlspecialchars($sermon['title']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                            <div class="play-button" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(93, 173, 236, 0.9); width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-play" style="font-size: 2.2rem; color: white; margin-left: 6px;"></i>
                            </div>
                        <?php else: ?>
                            <img src="https://images.pexels.com/photos/8923734/pexels-photo-8923734.jpeg?auto=compress&cs=tinysrgb&w=600"
                                 alt="<?php echo htmlspecialchars($sermon['title']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        <?php endif; ?>
                    </div>

                    <!-- All Details Below -->
                    <div class="sermon-details" style="padding: 20px;">
                        <span class="sermon-date" style="display: block; color: #5DADEC; font-size: 0.9rem; font-weight: 600; margin-bottom: 8px;"><?php echo formatDate($sermon['sermon_date']); ?></span>

                        <h3 style="margin: 0 0 12px 0; font-size: 1.25rem; color: #222; line-height: 1.4;"><?php echo htmlspecialchars($sermon['title']); ?></h3>

                        <p class="sermon-speaker" style="color: #666; font-size: 1rem; font-weight: 500; margin: 0 0 12px 0;">
                            <i class="fas fa-user" style="color: #5DADEC; margin-right: 5px;"></i><?php echo htmlspecialchars($sermon['preacher']); ?>
                        </p>

                        <?php if (!empty($sermon['scripture_reference'])): ?>
                            <p class="sermon-meta-item" style="margin: 0 0 12px 0; color: #555; font-size: 0.95rem;">
                                <i class="fas fa-book-open" style="color: #5DADEC; margin-right: 5px;"></i> <?php echo htmlspecialchars($sermon['scripture_reference']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($sermon['series'])): ?>
                            <p style="color: #5DADEC; font-size: 0.9rem; margin: 0 0 12px 0; font-weight: 500;">
                                <i class="fas fa-folder"></i> Series: <?php echo htmlspecialchars($sermon['series']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($sermon['description'])): ?>
                            <p class="sermon-description" style="color: #666; font-size: 0.95rem; line-height: 1.6; margin: 15px 0;"><?php echo htmlspecialchars($sermon['description']); ?></p>
                        <?php endif; ?>

                        <div class="media-links" style="margin-top: 20px; display: flex; gap: 10px;">
                            <?php if ($sermon['video_url']): ?>
                                <button onclick="event.stopPropagation(); openSermonVideo('<?php echo htmlspecialchars($sermon['video_url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($sermon['title'], ENT_QUOTES); ?>')" class="media-link" style="border: none; cursor: pointer;">
                                    <i class="fas fa-video"></i> Watch
                                </button>
                            <?php endif; ?>
                            <?php if ($sermon['audio_url']): ?>
                                <button onclick="event.stopPropagation(); openSermonAudio('<?php echo htmlspecialchars($sermon['audio_url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($sermon['title'], ENT_QUOTES); ?>')" class="media-link" style="border: none; cursor: pointer;">
                                    <i class="fas fa-microphone"></i> Listen
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-sermons">
                <i class="fas fa-microphone"></i>
                <h3>No sermons found</h3>
                <p>There are no sermons available in this category yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Video Modal -->
    <div class="sermon-modal" id="videoModal" onclick="closeVideoModal()">
        <span class="sermon-modal-close">&times;</span>
        <div class="sermon-modal-content" onclick="event.stopPropagation()">
            <h3 id="videoModalTitle"></h3>
            <div id="videoContainer"></div>
        </div>
    </div>

    <!-- Audio Modal -->
    <div class="sermon-modal" id="audioModal" onclick="closeAudioModal()">
        <span class="sermon-modal-close">&times;</span>
        <div class="sermon-modal-content" onclick="event.stopPropagation()">
            <h3 id="audioModalTitle"></h3>
            <audio id="audioPlayer" controls style="width: 100%; margin-top: 20px;">
                <source src="" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        </div>
    </div>

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
    <script>
        // Video Modal Functions
        function openSermonVideo(videoUrl, title) {
            const modal = document.getElementById('videoModal');
            const container = document.getElementById('videoContainer');
            const titleEl = document.getElementById('videoModalTitle');

            titleEl.textContent = title;

            // Check if it's a YouTube video
            if (videoUrl.includes('youtube.com') || videoUrl.includes('youtu.be')) {
                const videoId = extractYouTubeId(videoUrl);
                container.innerHTML = `<iframe src="https://www.youtube.com/embed/${videoId}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
            } else {
                // Regular video file
                container.innerHTML = `<video controls><source src="${videoUrl}" type="video/mp4">Your browser does not support the video tag.</video>`;
            }

            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const container = document.getElementById('videoContainer');

            container.innerHTML = ''; // Stop video playback
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function extractYouTubeId(url) {
            let videoId = '';
            const patterns = [
                /youtube\.com\/watch\?v=([^\&\?\/]+)/,
                /youtu\.be\/([^\&\?\/]+)/,
                /youtube\.com\/embed\/([^\&\?\/]+)/
            ];

            for (let pattern of patterns) {
                const match = url.match(pattern);
                if (match && match[1]) {
                    videoId = match[1];
                    break;
                }
            }

            return videoId;
        }

        // Audio Modal Functions
        function openSermonAudio(audioUrl, title) {
            const modal = document.getElementById('audioModal');
            const player = document.getElementById('audioPlayer');
            const titleEl = document.getElementById('audioModalTitle');

            player.src = audioUrl;
            titleEl.textContent = title;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeAudioModal() {
            const modal = document.getElementById('audioModal');
            const player = document.getElementById('audioPlayer');

            player.pause();
            player.currentTime = 0;
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modals on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideoModal();
                closeAudioModal();
            }
        });
    </script>
</body>
</html>
