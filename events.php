<?php
require_once 'config.php';
require_once "includes/get_communities.php";
require_once "includes/get_menu_pages.php";
require_once "includes/block_renderer.php";
require_once "includes/block_renderer.php";
require_once "includes/get_settings.php";

$currentPage = 'events';
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

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Get events from database
$upcomingEvents = [];
$pastEvents = [];

try {
    $conn = getDBConnection();
    
    // Get all published events
    $stmt = $conn->prepare("SELECT * FROM events WHERE status = 'published' ORDER BY event_date DESC, event_time DESC");
    $stmt->execute();
    $allEvents = $stmt->fetchAll();
    
    // Separate upcoming and past
    $today = date('Y-m-d');
    foreach ($allEvents as $event) {
        if ($event['event_date'] >= $today) {
            $upcomingEvents[] = $event;
        } else {
            $pastEvents[] = $event;
        }
    }
    
} catch(PDOException $e) {
    error_log("Error fetching events: " . $e->getMessage());
}

function formatEventDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatEventTime($time) {
    return $time ? date('g:i A', strtotime($time)) : 'TBD';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
        .event-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 18px;
        }
        .event-card {
            background: #ffffff;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            cursor: pointer;
        }
        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.14);
            border-color: rgba(93,173,236,0.35);
        }
        .event-thumb {
            position: relative;
            height: 190px;
            overflow: hidden;
        }
        .event-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .event-thumb .event-date-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(10,10,51,0.85);
            color: #fff;
            padding: 6px 10px;
            border-radius: 9px;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(0,0,0,0.2);
        }
        .event-thumb .event-date-badge .day {
            font-size: 0.95rem;
            line-height: 1.1;
            display: block;
        }
        .event-thumb .event-date-badge .month {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .event-body {
            padding: 16px 18px 18px 18px;
        }
        .event-body h3 {
            margin: 0 0 8px 0;
            color: #0f172a;
            font-size: 1.1rem;
            line-height: 1.35;
        }
        .event-meta-line {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            font-weight: 600;
            margin: 6px 0;
        }
        .event-meta-line i {
            color: #5DADEC;
        }
        .event-card .event-cta {
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #0A0A33;
            font-weight: 600;
        }
        .event-card .event-cta i {
            color: #5DADEC;
        }
        /* Modal */
        .event-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            z-index: 10000;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 16px;
            overflow-y: auto;
            backdrop-filter: blur(3px);
        }
        .event-modal.active {
            display: flex;
        }
        .event-modal-content {
            background: #fff;
            border-radius: 16px;
            max-width: 900px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 22px 50px rgba(15,23,42,0.28);
            border: 1px solid #e5e7eb;
            animation: fadeUp 0.4s ease;
        }
        .event-modal-header {
            position: relative;
        }
        .event-modal-header img {
            width: 100%;
            height: 320px;
            object-fit: cover;
            display: block;
        }
        .event-modal-body {
            padding: 22px;
        }
        .event-modal-close {
            position: absolute;
            top: 14px;
            right: 14px;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: none;
            background: rgba(0,0,0,0.4);
            color: #fff;
            font-size: 1.4rem;
            cursor: pointer;
        }
        .event-modal-body h3 {
            margin: 0 0 8px 0;
            color: #0f172a;
        }
        .event-modal-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 12px;
            margin: 12px 0 16px 0;
        }
        .event-modal-meta div {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            font-weight: 600;
        }
        .event-modal-meta i {
            color: #5DADEC;
        }
        .event-modal-content .event-description {
            color: #4b5563;
            line-height: 1.7;
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div id="scrolling-banner" class="scrolling-banner-container loading" data-page="events">
        <div class="scrolling-banner"><span class="banner-item">Loading announcements...</span></div>
    </div>

    <?php include 'includes/navbar.php'; ?>

    <section class="page-header">
        <div class="container">
            <h1>Church Events</h1>
            <p>Join us for fellowship, worship, and community activities</p>
        </div>
    </section>

    <section class="sermon-series" style="padding: 60px 0;">
        <div class="container">
            <h2 style="margin-bottom: 20px;">Upcoming Events</h2>
            <?php if (count($upcomingEvents) > 0): ?>
                <div class="event-card-grid">
                    <?php foreach ($upcomingEvents as $event): ?>
                        <div class="event-card"
                             data-title="<?php echo htmlspecialchars($event['title']); ?>"
                             data-date="<?php echo formatEventDate($event['event_date']); ?>"
                             data-time="<?php echo formatEventTime($event['event_time']); ?>"
                             data-location="<?php echo htmlspecialchars($event['location'] ?? ''); ?>"
                             data-image="<?php echo htmlspecialchars($event['image_url'] ?: 'https://images.pexels.com/photos/1587927/pexels-photo-1587927.jpeg?auto=compress&cs=tinysrgb&w=1200'); ?>"
                             data-description="<?php echo htmlspecialchars(render_blocks($event['description'] ?? ''), ENT_QUOTES); ?>">
                            <div class="event-thumb">
                                <?php if ($event['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($event['image_url']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                                <?php else: ?>
                                    <img src="https://images.pexels.com/photos/1587927/pexels-photo-1587927.jpeg?auto=compress&cs=tinysrgb&w=400" alt="Event">
                                <?php endif; ?>
                                <div class="event-date-badge">
                                    <span class="day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                    <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                                </div>
                            </div>
                            <div class="event-body">
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <div class="event-meta-line"><i class="fas fa-calendar-day"></i> <?php echo formatEventDate($event['event_date']); ?></div>
                                <div class="event-meta-line"><i class="fas fa-clock"></i> <?php echo formatEventTime($event['event_time']); ?></div>
                                <?php if ($event['location']): ?>
                                    <div class="event-meta-line"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['location']); ?></div>
                                <?php endif; ?>
                                <div class="event-cta"><i class="fas fa-arrow-right"></i> View details</div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 40px;">No upcoming events at this time.</p>
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

    <div class="event-modal" id="eventModal">
        <div class="event-modal-content">
            <div class="event-modal-header">
                <img id="eventModalImage" src="" alt="Event">
                <button class="event-modal-close" onclick="closeEventModal()">&times;</button>
            </div>
            <div class="event-modal-body">
                <h3 id="eventModalTitle"></h3>
                <div class="event-modal-meta">
                    <div><i class="fas fa-calendar-day"></i> <span id="eventModalDate"></span></div>
                    <div><i class="fas fa-clock"></i> <span id="eventModalTime"></span></div>
                    <div><i class="fas fa-map-marker-alt"></i> <span id="eventModalLocation"></span></div>
                </div>
                <div class="event-description" id="eventModalDescription"></div>
            </div>
        </div>
    </div>

    <script src="js/scrolling-banner.js"></script>
    <script src="js/script.js"></script>
    <script>
        (function() {
            const modal = document.getElementById('eventModal');
            const imgEl = document.getElementById('eventModalImage');
            const titleEl = document.getElementById('eventModalTitle');
            const dateEl = document.getElementById('eventModalDate');
            const timeEl = document.getElementById('eventModalTime');
            const locEl = document.getElementById('eventModalLocation');
            const descEl = document.getElementById('eventModalDescription');

            document.querySelectorAll('.event-card').forEach(card => {
                card.addEventListener('click', () => {
                    imgEl.src = card.dataset.image || '';
                    titleEl.textContent = card.dataset.title || '';
                    dateEl.textContent = card.dataset.date || '';
                    timeEl.textContent = card.dataset.time || '';
                    locEl.textContent = card.dataset.location || 'TBD';
                    descEl.innerHTML = card.dataset.description || '';
                    modal.classList.add('active');
                });
            });

            window.closeEventModal = function() {
                modal.classList.remove('active');
            };
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeEventModal();
            });
        })();
    </script>
</body>
</html>
