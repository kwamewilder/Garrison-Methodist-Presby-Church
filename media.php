<?php
require_once 'config.php';
require_once "includes/get_communities.php";
require_once "includes/get_menu_pages.php";

// Sanitize input helper
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get media items from database
$mediaItems = [];
$menuPages = [];

try {
    $conn = getDBConnection();

    // Get type filter if set
    $type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'all';

    // Get all media items
    if ($type === 'all') {
        $mediaStmt = $conn->prepare("SELECT * FROM media ORDER BY created_at DESC");
        $mediaStmt->execute();
    } else {
        $mediaStmt = $conn->prepare("SELECT * FROM media WHERE file_type LIKE ? ORDER BY created_at DESC");
        $mediaStmt->execute([$type . '%']);
    }
    $mediaItems = $mediaStmt->fetchAll();

    // Get menu pages for navigation
    try {
        $stmt = $conn->query("SELECT title, page_slug FROM pages WHERE status = 'published' ORDER BY id ASC LIMIT 5");
        $menuPages = $stmt->fetchAll();
    } catch(PDOException $e) {
        error_log("Error fetching menu pages: " . $e->getMessage());
        $menuPages = [];
    }

} catch(PDOException $e) {
    error_log("Error fetching media: " . $e->getMessage());
    $mediaItems = [];
}

// Helper function to get file icon
function getFileIcon($type) {
    switch($type) {
        case 'image':
            return 'fa-image';
        case 'video':
            return 'fa-video';
        case 'audio':
            return 'fa-music';
        case 'document':
            return 'fa-file-pdf';
        default:
            return 'fa-file';
    }
}

// Helper function to check if URL is a YouTube video
function isYouTubeVideo($url) {
    return (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false);
}

// Helper function to get YouTube embed URL
function getYouTubeEmbedUrl($url) {
    $videoId = '';
    if (preg_match('/youtube\.com\/watch\?v=([^\&\?\/]+)/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtu\.be\/([^\&\?\/]+)/', $url, $matches)) {
        $videoId = $matches[1];
    } elseif (preg_match('/youtube\.com\/embed\/([^\&\?\/]+)/', $url, $matches)) {
        $videoId = $matches[1];
    }
    return $videoId ? "https://www.youtube.com/embed/{$videoId}" : $url;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse photos, videos, audio recordings and documents from Garrison Methodist Presbyterian Church. View our media gallery.">
    <title>Media Gallery - Garrison Methodist Presbyterian Church</title>
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
        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
        .media-item {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .media-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .media-thumbnail {
            width: 100%;
            height: 200px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .media-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .media-thumbnail .icon-placeholder {
            font-size: 4rem;
            color: #5DADEC;
        }
        .media-info {
            padding: 20px;
        }
        .media-info h3 {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
            color: #333;
        }
        .media-info p {
            margin: 0 0 15px 0;
            color: #666;
            font-size: 0.9rem;
        }
        .media-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .media-type {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background: #f0f0f0;
            border-radius: 5px;
            font-size: 0.85rem;
            color: #666;
        }
        .media-date {
            font-size: 0.85rem;
            color: #999;
        }
        .media-actions {
            display: flex;
            gap: 10px;
        }
        .media-btn {
            flex: 1;
            padding: 10px;
            background: #5DADEC;
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        .media-btn:hover {
            background: #4a9dd8;
        }
        .media-btn.secondary {
            background: #6c757d;
        }
        .media-btn.secondary:hover {
            background: #5a6268;
        }
        .no-media {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .no-media i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        .media-modal {
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
        .media-modal.active {
            display: flex;
        }
        .media-modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 90%;
            max-height: 90%;
            overflow: auto;
            position: relative;
        }
        .media-modal-content h3 {
            margin: 0 0 20px 0;
            color: #333;
        }
        .media-modal-close {
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
        .media-modal-close:hover {
            background: rgba(255,255,255,0.2);
        }
        #videoContainer iframe,
        #videoContainer video {
            width: 100%;
            max-width: 800px;
            height: 450px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <!-- Scrolling Banner -->
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="media">
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
                        <a href="sermons.php" class="nav-link">Sermons</a>
                    </li>
                    <li class="nav-item">
                        <a href="events.php" class="nav-link">Events</a>
                    </li>
                    <li class="nav-item">
                        <a href="media.php" class="nav-link active">Media</a>
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
            <h1>Media Gallery</h1>
            <p>Browse our collection of photos, videos, audio recordings and documents</p>
        </div>
    </section>

    <!-- Media Gallery -->
    <section class="sermon-series" style="padding: 60px 0;">
        <div class="container">
            <!-- Filter Buttons -->
            <div class="filter-buttons">
                <a href="media.php?type=all" class="filter-btn <?php echo $type === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-th"></i> All Media
                </a>
                <a href="media.php?type=image" class="filter-btn <?php echo $type === 'image' ? 'active' : ''; ?>">
                    <i class="fas fa-image"></i> Images
                </a>
                <a href="media.php?type=video" class="filter-btn <?php echo $type === 'video' ? 'active' : ''; ?>">
                    <i class="fas fa-video"></i> Videos
                </a>
                <a href="media.php?type=audio" class="filter-btn <?php echo $type === 'audio' ? 'active' : ''; ?>">
                    <i class="fas fa-music"></i> Audio
                </a>
                <a href="media.php?type=document" class="filter-btn <?php echo $type === 'document' ? 'active' : ''; ?>">
                    <i class="fas fa-file-pdf"></i> Documents
                </a>
            </div>

            <?php if (count($mediaItems) > 0): ?>
            <div class="media-grid">
                <?php foreach ($mediaItems as $media):
                    $fileUrl = $media['file_url'];
                    $displayName = $media['title'];
                    $description = $media['description'] ?? '';
                ?>
                <div class="media-item">
                    <div class="media-thumbnail">
                        <?php if ($media['file_type'] === 'image'): ?>
                            <img src="<?php echo htmlspecialchars($fileUrl); ?>"
                                 alt="<?php echo htmlspecialchars($displayName); ?>"
                                 onclick="openImageModal('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>')"
                                 style="cursor: pointer;">
                        <?php elseif ($media['file_type'] === 'video'): ?>
                            <div style="background: #000; color: white; height: 200px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                                 onclick="openVideoModal('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>')">
                                <i class="fas fa-play-circle" style="font-size: 4rem;"></i>
                            </div>
                        <?php elseif ($media['file_type'] === 'audio'): ?>
                            <div style="background: #5DADEC; color: white; height: 200px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                                 onclick="openAudioModal('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>')">
                                <i class="fas fa-music" style="font-size: 4rem;"></i>
                            </div>
                        <?php else: ?>
                            <i class="fas <?php echo getFileIcon($media['file_type']); ?> icon-placeholder"></i>
                        <?php endif; ?>
                    </div>
                    <div class="media-info">
                        <h3><?php echo htmlspecialchars($displayName); ?></h3>
                        <?php if ($description): ?>
                            <p><?php echo htmlspecialchars($description); ?></p>
                        <?php endif; ?>
                        <div class="media-meta">
                            <span class="media-type">
                                <i class="fas <?php echo getFileIcon($media['file_type']); ?>"></i>
                                <?php echo ucfirst($media['file_type']); ?>
                            </span>
                            <span class="media-date">
                                <?php echo date('M j, Y', strtotime($media['created_at'])); ?>
                            </span>
                        </div>
                        <div class="media-actions">
                            <?php if ($media['file_type'] === 'image'): ?>
                                <button onclick="openImageModal('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>')"
                                        class="media-btn" style="border: none; cursor: pointer;">
                                    <i class="fas fa-eye"></i> View
                                </button>
                            <?php elseif ($media['file_type'] === 'video'): ?>
                                <button onclick="openVideoModal('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>')"
                                        class="media-btn" style="border: none; cursor: pointer;">
                                    <i class="fas fa-play"></i> Play
                                </button>
                            <?php elseif ($media['file_type'] === 'audio'): ?>
                                <button onclick="openAudioModal('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>', '<?php echo htmlspecialchars($displayName, ENT_QUOTES); ?>')"
                                        class="media-btn" style="border: none; cursor: pointer;">
                                    <i class="fas fa-play"></i> Play
                                </button>
                            <?php else: ?>
                                <a href="<?php echo htmlspecialchars($fileUrl); ?>"
                                   target="_blank"
                                   class="media-btn">
                                    <i class="fas fa-external-link-alt"></i> View
                                </a>
                            <?php endif; ?>
                            <button onclick="copyUrl('<?php echo htmlspecialchars($fileUrl, ENT_QUOTES); ?>')"
                                    class="media-btn secondary"
                                    style="border: none; cursor: pointer;">
                                <i class="fas fa-copy"></i> Copy URL
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-media">
                <i class="fas fa-folder-open"></i>
                <h3>No media found</h3>
                <p>There are no media items available in this category yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Image Modal -->
    <div class="media-modal" id="imageModal" onclick="closeImageModal()">
        <span class="media-modal-close">&times;</span>
        <div class="media-modal-content" onclick="event.stopPropagation()">
            <h3 id="imageModalTitle"></h3>
            <img src="" alt="Image" id="imageModalImg" style="max-width: 100%; max-height: 80vh; border-radius: 10px;">
        </div>
    </div>

    <!-- Video Modal -->
    <div class="media-modal" id="videoModal" onclick="closeVideoModal()">
        <span class="media-modal-close">&times;</span>
        <div class="media-modal-content" onclick="event.stopPropagation()">
            <h3 id="videoModalTitle"></h3>
            <div id="videoContainer"></div>
        </div>
    </div>

    <!-- Audio Modal -->
    <div class="media-modal" id="audioModal" onclick="closeAudioModal()">
        <span class="media-modal-close">&times;</span>
        <div class="media-modal-content" onclick="event.stopPropagation()">
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
        function copyUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                alert('URL copied to clipboard!');
            }).catch(err => {
                prompt('Copy this URL:', url);
            });
        }

        // Image Modal Functions
        function openImageModal(imageUrl, title) {
            const modal = document.getElementById('imageModal');
            const img = document.getElementById('imageModalImg');
            const titleEl = document.getElementById('imageModalTitle');

            img.src = imageUrl;
            titleEl.textContent = title;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Video Modal Functions
        function openVideoModal(videoUrl, title) {
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
        function openAudioModal(audioUrl, title) {
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
                closeImageModal();
                closeVideoModal();
                closeAudioModal();
            }
        });
    </script>
</body>
</html>
