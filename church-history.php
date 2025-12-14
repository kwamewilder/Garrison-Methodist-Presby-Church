<?php
require_once "config.php";
require_once "includes/block_renderer.php";
require_once "includes/get_communities.php";
require_once "includes/get_menu_pages.php";
require_once "includes/get_settings.php";

$currentPage = 'church-history';
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

// Pull editable content from pages table (slug: church-history)
$historyContent = '';
$historyMeta = "Learn about the rich history of Garrison Methodist Presbyterian Church and how we've grown over the years.";
try {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT meta_description, content FROM pages WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute(['church-history']);
    $row = $stmt->fetch();
    if ($row) {
        $historyContent = render_blocks($row['content']);
        if (!empty($row['meta_description'])) {
            $historyMeta = $row['meta_description'];
        }
    }
} catch (PDOException $e) {
    error_log("History page fetch error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($historyMeta); ?>">
    <title>Church History - Garrison Methodist Presbyterian Church</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/scrolling-banner.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./images/favicon.ico">
    <style>
        /* Scoped Church History theme */
        .history-theme {
            /* Match site blues + gold accent */
            --primary: #1f4e8c;
            --primary-dark: #12345c;
            --accent: #e2b34d;
            --bg: #f6f8fb;
            --bg-alt: #ffffff;
            --text: #1f2937;
            --muted: #606b80;
            --border-soft: #e3e8ef;
            --shadow-soft: 0 12px 25px rgba(15, 23, 42, 0.12);
            --radius-lg: 18px;
            --radius-md: 12px;
            --radius-sm: 8px;
            background-color: var(--bg);
            padding: 60px 0 70px;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .history-theme .container {
            width: 100%;
            max-width: 1120px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        .history-shell {
            background: var(--bg-alt);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-soft);
            border: 1px solid var(--border-soft);
            padding: 32px 32px 38px;
        }
        .history-shell h1,
        .history-shell h2,
        .history-shell h3,
        .history-shell h4 {
            color: var(--primary-dark);
            margin-top: 0;
        }
        .history-shell h2,
        .history-shell h3 {
            position: relative;
            display: inline-block;
            padding-bottom: 6px;
        }
        .history-shell h2::after,
        .history-shell h3::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 38%;
            height: 3px;
            background: linear-gradient(to right, var(--primary), var(--accent));
            border-radius: 999px;
        }
        .history-shell p {
            color: var(--text);
            line-height: 1.7;
            margin: 0 0 1.1rem;
        }
        .history-shell a {
            color: var(--primary);
            text-decoration: none;
            border-bottom: 1px solid transparent;
            transition: border-color 0.2s ease;
        }
        .history-shell a:hover {
            border-color: var(--accent);
        }
        .history-shell ul {
            padding-left: 1.2rem;
            color: var(--text);
        }
        .history-shell li {
            margin-bottom: 0.55rem;
        }
        /* Utility layouts for CMS blocks */
        .history-theme .two-column {
            display: grid;
            grid-template-columns: minmax(0, 3fr) minmax(0, 2.5fr);
            gap: 2.5rem;
            align-items: flex-start;
        }
        .history-theme .image-card {
            background: var(--bg-alt);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
        }
        .history-theme .image-card img {
            width: 100%;
            display: block;
            height: 260px;
            object-fit: cover;
        }
        .history-theme .image-caption {
            margin: 0;
            padding: 0.9rem 1.1rem 1.2rem;
            font-size: 0.9rem;
            color: var(--muted);
        }
        .history-theme .timeline {
            margin-top: 2.5rem;
            position: relative;
            padding-left: 1.2rem;
        }
        .history-theme .timeline::before {
            content: "";
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, var(--primary), var(--accent));
            border-radius: 999px;
            opacity: 0.28;
        }
        .history-theme .timeline-item {
            position: relative;
            margin-bottom: 1.9rem;
            padding-left: 2.4rem;
        }
        .history-theme .timeline-year {
            position: absolute;
            left: -2px;
            top: 0.15rem;
            transform: translateX(-100%);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primary-dark);
            background: #e4ebff;
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
        }
        .history-theme .timeline-item::before {
            content: "";
            position: absolute;
            left: 15px;
            top: 0.3rem;
            width: 11px;
            height: 11px;
            border-radius: 999px;
            background: #ffffff;
            border: 3px solid var(--primary);
            box-shadow: 0 0 0 4px rgba(52, 80, 161, 0.12);
        }
        .history-theme .timeline-content h4 {
            margin: 0 0 0.3rem;
            font-size: 1.05rem;
            color: var(--primary-dark);
        }
        .history-theme .timeline-content p {
            margin: 0;
            font-size: 0.95rem;
            color: #333333;
        }
        .history-theme .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1.8rem;
            margin-top: 2.2rem;
        }
        .history-theme .card {
            background: #ffffff;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
            display: flex;
            flex-direction: column;
        }
        .history-theme .card img {
            width: 100%;
            height: 190px;
            object-fit: cover;
            display: block;
        }
        .history-theme .card h4 {
            margin: 1rem 1.1rem 0.25rem;
            font-size: 1.05rem;
        }
        .history-theme .card p {
            margin: 0 1.1rem 1.3rem;
            font-size: 0.94rem;
            color: var(--muted);
        }
        .history-theme .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        .history-theme .gallery-grid img {
            width: 100%;
            height: 170px;
            object-fit: cover;
            border-radius: var(--radius-md);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.18);
        }
        .history-intro {
            margin-bottom: 28px;
        }
        .history-intro .intro-image-card img {
            height: 320px;
            object-fit: cover;
        }
        .history-intro .intro-image-card {
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.16);
            border: 1px solid var(--border-soft);
        }
        /* General image styling inside history body */
        .history-shell img {
            width: 100%;
            height: auto;
            border-radius: var(--radius-md);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1);
            border: 1px solid var(--border-soft);
        }
        .history-shell figure {
            margin: 0 0 1.2rem;
        }
        .history-duo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.2rem;
            margin: 1.5rem 0 2rem;
        }
        .history-duo .duo-card {
            background: var(--bg-alt);
            border-radius: var(--radius-md);
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.12);
            border: 1px solid var(--border-soft);
            padding: 10px;
        }
        .history-duo img {
            width: 100%;
            height: 260px;
            object-fit: cover;
            border-radius: var(--radius-md);
        }
        .history-theme .image-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.2rem;
        }
        .history-theme .grid-image {
            background: #ffffff;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
        }
        .history-theme .grid-image img {
            width: 100%;
            max-height: 180px;
            object-fit: cover;
            display: block;
        }
        .history-theme .grid-image figcaption {
            padding: 0.7rem 0.9rem 1rem;
            font-size: 0.9rem;
            color: var(--muted);
        }
        @media (max-width: 900px) {
            .history-theme .two-column {
                grid-template-columns: 1fr;
            }
            .history-theme .cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .history-theme .gallery-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 640px) {
            .history-shell {
                padding: 24px 22px 28px;
            }
            .history-theme .cards {
                grid-template-columns: 1fr;
            }
            .history-theme .gallery-grid {
                grid-template-columns: 1fr;
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
            <h1>Church History</h1>
            <p>Discover the rich heritage and journey of our church community</p>
        </div>
    </section>

    <?php if ($historyContent): ?>
        <section class="history-theme">
            <div class="container">
                <div class="history-shell">
                    <?php echo $historyContent; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <script>
    (function() {
        const shell = document.querySelector('.history-shell');
        if (!shell) return;

        const ensureUnique = (list) => list.filter((item, idx) => list.indexOf(item) === idx);

        // Build intro layout: move first heading/paragraphs before first image into a two-column with image on the right
        const firstImg = shell.querySelector('img');
        const firstHeading = shell.querySelector('h1, h2, h3, h4, h5, h6');
        const imgNode = firstImg ? (firstImg.closest('figure') || firstImg) : null;
        let introBlock = null;

        if (firstHeading && imgNode) {
            const intro = document.createElement('div');
            intro.className = 'history-intro two-column';
            intro.setAttribute('data-timeline-skip', 'true');
            const textCol = document.createElement('div');
            textCol.className = 'intro-text';
            const imageCol = document.createElement('div');
            imageCol.className = 'image-card intro-image-card';
            imageCol.setAttribute('data-timeline-skip', 'true');

            // Collect nodes from the first heading up to (but not including) the image node
            const toMove = [];
            let node = firstHeading;
            while (node && node !== imgNode) {
                const next = node.nextSibling;
                toMove.push(node);
                node = next;
            }
            toMove.forEach(n => textCol.appendChild(n));
            imageCol.appendChild(imgNode);

            intro.appendChild(textCol);
            intro.appendChild(imageCol);
            shell.insertBefore(intro, shell.firstChild);
            introBlock = intro;
        }

        // Build a duo row for the 3rd and 4th images (excluding the intro image) and place them side by side
        const imageContainers = ensureUnique(
            Array.from(shell.querySelectorAll('figure img, img')).map(img => img.closest('figure') || img)
        );
        const filteredImages = imageContainers.filter(el => el !== imgNode);
        const duoTargets = filteredImages.slice(2, 4); // 3rd and 4th images after intro

        if (duoTargets.length === 2) {
            const duo = document.createElement('div');
            duo.className = 'history-duo';
            duo.setAttribute('data-timeline-skip', 'true');
            duoTargets.forEach(el => {
                const card = document.createElement('div');
                card.className = 'duo-card';
                card.setAttribute('data-timeline-skip', 'true');
                card.appendChild(el);
                duo.appendChild(card);
            });
            if (introBlock && introBlock.nextSibling) {
                shell.insertBefore(duo, introBlock.nextSibling);
            } else {
                shell.insertBefore(duo, shell.firstChild);
            }
        }

        // Auto-build a timeline for year-based sections
        const children = Array.from(shell.children);
        const groups = [];
        let i = 0;

        const isYearHeading = (el) => {
            const text = (el.textContent || '').trim();
            return /^\d{4}\s*[–-]/.test(text);
        };

        const isHeadingEl = (el) => el && /^H[1-6]$/i.test(el.tagName || '');

        while (i < children.length) {
            const node = children[i];
            if (node && node.getAttribute && node.getAttribute('data-timeline-skip') === 'true') {
                i++;
                continue;
            }
            if (isYearHeading(node)) {
                const group = [node];
                i++;
                while (i < children.length) {
                    const next = children[i];
                    if (isYearHeading(next)) break;
                    if (next && next.getAttribute && next.getAttribute('data-timeline-skip') === 'true') break;
                    if (isHeadingEl(next) && !isYearHeading(next)) break;
                    group.push(next);
                    i++;
                }
                groups.push(group);
            } else {
                i++;
            }
        }

        if (!groups.length) return;

        const timeline = document.createElement('div');
        timeline.className = 'timeline';

        groups.forEach(group => {
            const first = group[0];
            const headingText = (first.textContent || '').trim();
            const match = headingText.match(/^(\d{4})\s*[–-]\s*(.*)$/);
            const year = match ? match[1] : '';
            const title = match ? match[2] : headingText;

            const item = document.createElement('div');
            item.className = 'timeline-item';
            item.innerHTML = `<div class="timeline-year">${year}</div><div class="timeline-content"><h4>${title}</h4></div>`;
            const contentWrap = item.querySelector('.timeline-content');

            // Move the rest of the group's nodes under this timeline item
            group.slice(1).forEach(node => contentWrap.appendChild(node));

            timeline.appendChild(item);
        });

        // Insert timeline before the first captured heading
        const firstTimelineHeading = groups[0][0];
        shell.insertBefore(timeline, firstTimelineHeading);

        // Remove original heading nodes now that timeline is built
        groups.forEach(group => {
            const heading = group[0];
            if (heading.parentNode) heading.parentNode.removeChild(heading);
        });
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
