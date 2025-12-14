<?php
require_once 'config.php';
require_once "includes/get_communities.php";
require_once "includes/get_menu_pages.php";
require_once "includes/block_renderer.php";
require_once "includes/get_settings.php";

$currentPage = 'sermons';
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

// Sanitize input helper
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get sermons from database
$featuredSermon = null;
$sermons = [];

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
        /* Page polish */
        .featured-sermon {
            background: linear-gradient(135deg, rgba(10,10,51,0.05), rgba(93,173,236,0.06));
        }
        .featured-content {
            display: grid !important;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
            gap: 24px;
            padding: 28px;
            border-radius: 18px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12);
            position: relative;
            overflow: hidden;
        }
        .featured-sermon .featured-content {
            max-width: 1100px;
            margin: 0 auto;
        }
        .featured-content::before {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: 18px;
            padding: 1px;
            background: linear-gradient(135deg, rgba(93,173,236,0.4), rgba(10,10,51,0.2));
            mask:
                linear-gradient(#fff 0 0) content-box,
                linear-gradient(#fff 0 0);
            mask-composite: exclude;
            pointer-events: none;
        }
        .sermon-video {
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.18);
            transition: transform 0.45s ease, box-shadow 0.45s ease;
            cursor: pointer;
            margin-bottom: 14px;
        }
        .sermon-video:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 42px rgba(15, 23, 42, 0.22);
        }
        .sermon-info {
            align-self: center;
            animation: fadeUp 0.8s ease;
        }
        .sermon-info h2 {
            font-size: 2rem;
            line-height: 1.3;
            margin-bottom: 10px;
        }
        .sermon-info .sermon-speaker {
            color: #4b5563;
            font-weight: 600;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 30px;
            justify-content: center;
        }
        .filter-btn {
            padding: 12px 22px;
            border: 1px solid #d5deeb;
            background: #f7fafc;
            color: #0A0A33;
            border-radius: 999px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-weight: 500;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        }
        .filter-btn:hover,
        .filter-btn.active {
            background: linear-gradient(135deg, #5DADEC, #73b7ea);
            color: white;
            border-color: transparent;
            box-shadow: 0 14px 28px rgba(93, 173, 236, 0.24);
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
            flex-wrap: wrap;
        }
        .media-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            background: #0A0A33;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 12px 26px rgba(10, 10, 51, 0.25);
            border: 1px solid rgba(255,255,255,0.05);
        }
        .media-link:hover {
            background: #5DADEC;
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 14px 30px rgba(93, 173, 236, 0.28);
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
            transition: transform 0.35s ease;
            height: 220px;
            cursor: pointer;
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
            transition: all 0.35s ease;
            box-shadow: 0 14px 30px rgba(93, 173, 236, 0.35);
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

        /* Cards */
        .series-grid {
            gap: 24px;
        }
        .sermon-card {
            background: #ffffff;
            border-radius: 14px !important;
            border: 1px solid #e5e7eb;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.1) !important;
            overflow: hidden;
            transition: transform 0.35s ease, box-shadow 0.35s ease, border-color 0.35s ease;
            animation: fadeUp 0.7s ease;
        }
        .sermon-card.is-hidden {
            display: none;
        }
        .sermon-card:hover {
            transform: translateY(-6px) scale(1.005);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.16);
            border-color: rgba(93,173,236,0.35);
        }
        .sermon-details {
            padding: 20px !important;
        }
        .sermon-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .sermon-date {
            color: #5DADEC !important;
        }
        .sermon-details h3 {
            margin: 0 0 12px 0;
            font-size: 1.35rem;
            color: #0f172a;
            line-height: 1.45;
        }
        .sermon-speaker {
            color: #4b5563;
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 12px 0;
        }
        .sermon-meta-item {
            margin: 0 0 12px 0;
            color: #555;
            font-size: 0.95rem;
        }
        .sermon-series-label {
            color: #5DADEC;
            font-size: 0.92rem;
            margin: 0 0 12px 0;
            font-weight: 600;
        }
        .media-links button.media-link {
            border: none;
            cursor: pointer;
        }

        /* Modal polish */
        .sermon-modal {
            backdrop-filter: blur(4px);
        }
        .sermon-modal-content {
            background: #0b1024;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 20px 50px rgba(0,0,0,0.35);
        }

        /* Animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
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
    <?php include 'includes/navbar.php'; ?>

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
            <div class="featured-content">
                <div class="sermon-video"
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
                        <div class="sermon-description" style="margin-bottom: 20px;">
                            <?php echo render_blocks($featuredSermon['description']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="media-links">
                        <?php if ($featuredSermon['audio_url']): ?>
                            <button onclick="openSermonAudio('<?php echo htmlspecialchars($featuredSermon['audio_url'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($featuredSermon['title'], ENT_QUOTES); ?>')" class="media-link" style="border: none; cursor: pointer;">
                                <i class="fas fa-microphone"></i> Listen
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
            <div class="filter-buttons" id="filterButtons">
                <button type="button" class="filter-btn <?php echo $series === 'all' ? 'active' : ''; ?>" data-series="all">All Sermons</button>
                <?php if (!empty($seriesList)): ?>
                    <?php foreach ($seriesList as $seriesItem): ?>
                        <button type="button"
                           class="filter-btn <?php echo $series === $seriesItem['series'] ? 'active' : ''; ?>"
                           data-series="<?php echo htmlspecialchars($seriesItem['series']); ?>">
                            <?php echo htmlspecialchars($seriesItem['series']); ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (count($sermons) > 0): ?>
            <div class="series-grid">
                <?php foreach ($sermons as $sermon): ?>
                <div class="sermon-card" data-series="<?php echo htmlspecialchars($sermon['series'] ?? ''); ?>">
                    <!-- Thumbnail at Top -->
                    <div class="sermon-thumbnail"
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
                            <p class="sermon-meta-item">
                                <i class="fas fa-book-open" style="color: #5DADEC; margin-right: 5px;"></i> <?php echo htmlspecialchars($sermon['scripture_reference']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($sermon['series'])): ?>
                            <p class="sermon-series-label">
                                <i class="fas fa-folder"></i> Series: <?php echo htmlspecialchars($sermon['series']); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($sermon['description'])): ?>
                            <div class="sermon-description">
                                <?php echo render_blocks($sermon['description']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="media-links">
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

    <script>
        // Client-side filtering to avoid page jump
        (function() {
            const buttons = document.querySelectorAll('#filterButtons .filter-btn');
            const cards = Array.from(document.querySelectorAll('.sermon-card'));
            const initial = "<?php echo htmlspecialchars($series ?? 'all'); ?>".toLowerCase();

            function applyFilter(target) {
                const key = (target || 'all').toLowerCase();
                buttons.forEach(btn => {
                    btn.classList.toggle('active', (btn.dataset.series || '').toLowerCase() === key);
                });
                cards.forEach(card => {
                    const cardSeries = (card.dataset.series || '').toLowerCase();
                    const show = key === 'all' || cardSeries === key;
                    card.classList.toggle('is-hidden', !show);
                });
            }

            buttons.forEach(btn => {
                btn.addEventListener('click', () => applyFilter(btn.dataset.series));
            });

            applyFilter(initial || 'all');
        })();
    </script>

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
