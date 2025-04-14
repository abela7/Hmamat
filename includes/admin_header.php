<?php
// Get current page for active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Get admin information if logged in
$admin_logged_in = isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
$admin_username = $admin_logged_in ? $_SESSION['admin_username'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?> Admin</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo ADMIN_URL; ?>/css/style.css">
</head>
<body>
    <!-- Off-canvas Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasSidebarLabel"><?php echo APP_NAME; ?> Admin</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <?php if ($admin_logged_in): ?>
            <div class="admin-info mb-4">
                <div class="admin-name"><?php echo htmlspecialchars($admin_username); ?></div>
                <small class="text-muted">Administrator</small>
            </div>
            
            <ul class="mobile-menu">
                <li class="mobile-menu-item">
                    <a href="<?php echo ADMIN_URL; ?>/index.php" class="mobile-menu-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt menu-icon"></i> Dashboard
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="<?php echo ADMIN_URL; ?>/manage_activities.php" class="mobile-menu-link <?php echo $current_page == 'manage_activities.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tasks menu-icon"></i> Activities
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="<?php echo ADMIN_URL; ?>/manage_reasons.php" class="mobile-menu-link <?php echo $current_page == 'manage_reasons.php' ? 'active' : ''; ?>">
                        <i class="fas fa-question-circle menu-icon"></i> Miss Reasons
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="<?php echo ADMIN_URL; ?>/manage_messages.php" class="mobile-menu-link <?php echo $current_page == 'manage_messages.php' ? 'active' : ''; ?>">
                        <i class="fas fa-comment menu-icon"></i> Daily Messages
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="<?php echo ADMIN_URL; ?>/view_users.php" class="mobile-menu-link <?php echo $current_page == 'view_users.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users menu-icon"></i> Users
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="<?php echo ADMIN_URL; ?>/logout.php" class="mobile-menu-link">
                        <i class="fas fa-sign-out-alt menu-icon"></i> Logout
                    </a>
                </li>
            </ul>
            <?php else: ?>
            <ul class="mobile-menu">
                <li class="mobile-menu-item">
                    <a href="<?php echo ADMIN_URL; ?>/login.php" class="mobile-menu-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
                        <i class="fas fa-sign-in-alt menu-icon"></i> Login
                    </a>
                </li>
            </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Header with fixed Navbar -->
    <header class="header">
        <div class="container">
            <div class="navbar-container">
                <div class="logo"><?php echo APP_NAME; ?> Admin</div>
                
                <!-- Mobile Toggle Button - Always Visible -->
                <button class="navbar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Desktop Navigation -->
                <nav class="nav d-none d-lg-flex">
                    <?php if ($admin_logged_in): ?>
                    <a href="<?php echo ADMIN_URL; ?>/index.php" class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">Dashboard</a>
                    <a href="<?php echo ADMIN_URL; ?>/manage_activities.php" class="nav-link <?php echo $current_page == 'manage_activities.php' ? 'active' : ''; ?>">Activities</a>
                    <a href="<?php echo ADMIN_URL; ?>/manage_reasons.php" class="nav-link <?php echo $current_page == 'manage_reasons.php' ? 'active' : ''; ?>">Miss Reasons</a>
                    <a href="<?php echo ADMIN_URL; ?>/manage_messages.php" class="nav-link <?php echo $current_page == 'manage_messages.php' ? 'active' : ''; ?>">Daily Messages</a>
                    <a href="<?php echo ADMIN_URL; ?>/view_users.php" class="nav-link <?php echo $current_page == 'view_users.php' ? 'active' : ''; ?>">Users</a>
                    <a href="<?php echo ADMIN_URL; ?>/logout.php" class="nav-link">Logout</a>
                    <?php else: ?>
                    <a href="<?php echo ADMIN_URL; ?>/login.php" class="nav-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">Login</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="admin-content"> 