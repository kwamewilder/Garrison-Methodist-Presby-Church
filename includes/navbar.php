<?php
// Shared navigation bar for public pages
$communities = $communities ?? [];
$menuPages = $menuPages ?? [];
$currentPage = $currentPage ?? '';
$activeMenuSlug = $activeMenuSlug ?? null;

if (!function_exists('navIsActive')) {
    function navIsActive($currentPage, $targets) {
        $targets = (array) $targets;
        return in_array($currentPage, $targets, true) ? ' active' : '';
    }
}
?>
<header class="site-header">
    <div class="nav-shell">
        <a class="nav-brand" href="index.php">
            <img src="images/logo.png" alt="GMPC Logo" class="brand-logo">
            <div class="brand-text">
                <span class="brand-name"><?php echo htmlspecialchars($churchName ?? 'Garrison Methodist Presbyterian Church'); ?></span>
                <span class="brand-tagline">Methodist Presbyterian Church</span>
            </div>
        </a>

        <nav class="nav-links" aria-label="Primary">
            <ul class="nav-list">
                <li><a href="index.php" class="nav-link<?php echo navIsActive($currentPage, 'home'); ?>">Home</a></li>
                <li><a href="sermons.php" class="nav-link<?php echo navIsActive($currentPage, 'sermons'); ?>">Sermons</a></li>
                <li><a href="events.php" class="nav-link<?php echo navIsActive($currentPage, 'events'); ?>">Events</a></li>
                <li><a href="blog.php" class="nav-link<?php echo navIsActive($currentPage, 'blog'); ?>">Articles</a></li>
                <li><a href="media.php" class="nav-link<?php echo navIsActive($currentPage, 'media'); ?>">Media</a></li>
                <li class="nav-item-dropdown">
                    <button class="nav-link nav-link-dropdown" type="button">
                        Communities <i class="fas fa-chevron-down nav-chevron"></i>
                    </button>
                    <ul class="dropdown-panel">
                        <?php foreach ($communities as $navCommunity): ?>
                            <li><a href="community.php?slug=<?php echo urlencode($navCommunity['slug']); ?>"><?php echo htmlspecialchars($navCommunity['name']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li class="nav-item-dropdown">
                    <button class="nav-link nav-link-dropdown" type="button">
                        About <i class="fas fa-chevron-down nav-chevron"></i>
                    </button>
                    <ul class="dropdown-panel">
                        <li><a href="church-history.php">Church History</a></li>
                        <li><a href="elders.php">Elders</a></li>
                        <li><a href="pastoral-team.php">Pastoral Team</a></li>
                    </ul>
                </li>
                <li><a href="contact.php" class="nav-link<?php echo navIsActive($currentPage, 'contact'); ?>">Contact</a></li>
                <?php foreach ($menuPages as $menuPage): ?>
                    <li>
                        <a href="page.php?slug=<?php echo urlencode($menuPage['slug']); ?>" class="nav-link<?php echo ($activeMenuSlug === $menuPage['slug']) ? ' active' : ''; ?>">
                            <?php echo htmlspecialchars($menuPage['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="nav-drawer">
            <span class="sr-only">Toggle menu</span>
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
            <span class="nav-toggle-bar"></span>
        </button>
    </div>

    <div class="nav-drawer" id="nav-drawer" data-nav-drawer aria-hidden="true">
        <div class="nav-drawer-header">
            <div class="brand-inline">
                <img src="images/logo.png" alt="GMPC Logo" class="brand-logo">
                <div class="brand-text">
                    <span class="brand-name"><?php echo htmlspecialchars($churchName ?? 'Garrison Methodist Presbyterian Church'); ?></span>
                </div>
            </div>
            <button class="nav-close" type="button" data-nav-close aria-label="Close menu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="drawer-list">
            <li><a href="index.php" class="drawer-link<?php echo navIsActive($currentPage, 'home'); ?>">Home</a></li>
            <li><a href="sermons.php" class="drawer-link<?php echo navIsActive($currentPage, 'sermons'); ?>">Sermons</a></li>
            <li><a href="events.php" class="drawer-link<?php echo navIsActive($currentPage, 'events'); ?>">Events</a></li>
            <li><a href="blog.php" class="drawer-link<?php echo navIsActive($currentPage, 'blog'); ?>">Articles</a></li>
            <li><a href="media.php" class="drawer-link<?php echo navIsActive($currentPage, 'media'); ?>">Media</a></li>
            <li class="drawer-item has-children">
                <button type="button" class="drawer-trigger" data-nav-accordion>
                    <span>Communities</span><i class="fas fa-chevron-down"></i>
                </button>
                <ul class="drawer-submenu">
                    <?php foreach ($communities as $navCommunity): ?>
                        <li><a href="community.php?slug=<?php echo urlencode($navCommunity['slug']); ?>"><?php echo htmlspecialchars($navCommunity['name']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </li>
            <li class="drawer-item has-children">
                <button type="button" class="drawer-trigger" data-nav-accordion>
                    <span>About</span><i class="fas fa-chevron-down"></i>
                </button>
                <ul class="drawer-submenu">
                    <li><a href="church-history.php">Church History</a></li>
                    <li><a href="elders.php">Elders</a></li>
                    <li><a href="pastoral-team.php">Pastoral Team</a></li>
                </ul>
            </li>
            <li><a href="contact.php" class="drawer-link<?php echo navIsActive($currentPage, 'contact'); ?>">Contact</a></li>
            <?php foreach ($menuPages as $menuPage): ?>
                <li>
                    <a href="page.php?slug=<?php echo urlencode($menuPage['slug']); ?>" class="drawer-link<?php echo ($activeMenuSlug === $menuPage['slug']) ? ' active' : ''; ?>">
                        <?php echo htmlspecialchars($menuPage['title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="nav-overlay" data-nav-overlay aria-hidden="true"></div>
</header>
