<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$currentAdmin = getCurrentAdmin();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Dashboard'; ?> - GMPC Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../images/logo.png" alt="GMPC Logo" style="width: 40px; height: 40px; border-radius: 50%; background: white; padding: 5px;">
                <h2>GMPC ADMIN</h2>
            </div>

            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-dashboard"></i>
                    <span>Dashboard</span>
                </a>

                <a href="pages.php" class="nav-item <?php echo $currentPage === 'pages' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>Pages</span>
                </a>

                <a href="sections.php" class="nav-item <?php echo $currentPage === 'sections' ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group"></i>
                    <span>Sections</span>
                </a>

                <a href="events.php" class="nav-item <?php echo $currentPage === 'events' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar"></i>
                    <span>Events</span>
                </a>

                <a href="sermons.php" class="nav-item <?php echo $currentPage === 'sermons' ? 'active' : ''; ?>">
                    <i class="fas fa-book-bible"></i>
                    <span>Sermons</span>
                </a>

                <a href="communities.php" class="nav-item <?php echo $currentPage === 'communities' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Communities</span>
                </a>

                <a href="banners.php" class="nav-item <?php echo $currentPage === 'banners' ? 'active' : ''; ?>">
                    <i class="fas fa-scroll"></i>
                    <span>Banners</span>
                </a>

                <a href="media.php" class="nav-item <?php echo $currentPage === 'media' ? 'active' : ''; ?>">
                    <i class="fas fa-images"></i>
                    <span>Media</span>
                </a>

                <a href="settings.php" class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>

                <?php if (hasRole('super_admin')): ?>
                <a href="admins.php" class="nav-item <?php echo $currentPage === 'admins' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i>
                    <span>Admins</span>
                </a>
                <?php endif; ?>
            </nav>

            <div class="sidebar-footer">
                <a href="logout.php" class="nav-item">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <header class="top-bar">
                <div class="top-bar-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
                </div>

                <div class="top-bar-right">
                    <a href="../index.php" target="_blank" class="btn-view-site">
                        <i class="fas fa-external-link-alt"></i>
                        View Site
                    </a>

                    <div class="admin-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($currentAdmin['name']); ?></span>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="page-content">
