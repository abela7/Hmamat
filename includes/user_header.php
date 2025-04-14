<?php
// Get current page for active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Get user information if logged in
$user_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$baptism_name = $user_logged_in ? $_SESSION['baptism_name'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo USER_URL; ?>/css/style.css">
</head>
<body>
    <!-- Off-canvas Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasSidebarLabel"><?php echo APP_NAME; ?></h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <?php if ($user_logged_in): ?>
            <div class="user-info mb-4">
                <div class="user-name"><?php echo htmlspecialchars($baptism_name); ?></div>
                <small class="text-muted">Welcome back!</small>
            </div>
            <?php endif; ?>
            
            <ul class="mobile-menu">
                <?php if ($user_logged_in): ?>
                <li class="mobile-menu-item">
                    <a href="<?php echo USER_URL; ?>/dashboard.php" class="mobile-menu-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt menu-icon"></i> Dashboard
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="<?php echo USER_URL; ?>/leaderboard.php" class="mobile-menu-link <?php echo $current_page == 'leaderboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-trophy menu-icon"></i> Leaderboard
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="<?php echo USER_URL; ?>/logout.php" class="mobile-menu-link">
                        <i class="fas fa-sign-out-alt menu-icon"></i> Logout
                    </a>
                </li>
                <?php else: ?>
                <li class="mobile-menu-item">
                    <a href="<?php echo USER_URL; ?>/login.php" class="mobile-menu-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
                        <i class="fas fa-sign-in-alt menu-icon"></i> Login
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="<?php echo USER_URL; ?>/register.php" class="mobile-menu-link <?php echo $current_page == 'register.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus menu-icon"></i> Register
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <!-- Header with fixed Navbar -->
    <header class="header">
        <div class="container">
            <div class="navbar-container">
                <div class="logo"><?php echo APP_NAME; ?></div>
                
                <!-- Mobile Toggle Button - Always Visible -->
                <button class="navbar-toggle d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSidebar" aria-controls="offcanvasSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                
                <!-- Desktop Navigation -->
                <nav class="nav d-none d-md-flex">
                    <?php if ($user_logged_in): ?>
                    <a href="<?php echo USER_URL; ?>/dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a>
                    <a href="<?php echo USER_URL; ?>/leaderboard.php" class="nav-link <?php echo $current_page == 'leaderboard.php' ? 'active' : ''; ?>">Leaderboard</a>
                    <a href="<?php echo USER_URL; ?>/logout.php" class="nav-link">Logout</a>
                    <?php else: ?>
                    <a href="<?php echo USER_URL; ?>/login.php" class="nav-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">Login</a>
                    <a href="<?php echo USER_URL; ?>/register.php" class="nav-link <?php echo $current_page == 'register.php' ? 'active' : ''; ?>">Register</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container"> 