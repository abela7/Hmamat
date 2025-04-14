<?php
// Get current page for active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Get user information if logged in
$user_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$baptism_name = $user_logged_in ? $_SESSION['baptism_name'] : '';

// Force Amharic language
$language = 'am';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="../assets/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../assets/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="../assets/favicon_io/site.webmanifest">
    <link rel="shortcut icon" href="../assets/favicon_io/favicon.ico">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../user/css/style.css">
    <!-- Mobile specific meta -->
    <meta name="theme-color" content="#DAA520">
    <!-- jQuery (load before Bootstrap) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="user-body">
    <!-- Off-canvas Sidebar -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasSidebar" aria-labelledby="offcanvasSidebarLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasSidebarLabel">
                <i class="fas fa-cross me-2"></i> <?php echo APP_NAME; ?>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <?php if ($user_logged_in): ?>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($baptism_name); ?></div>
                <small class="text-muted">Welcome back!</small>
            </div>
            <?php endif; ?>
            
            <ul class="mobile-menu">
                <?php if ($user_logged_in): ?>
                <li class="mobile-menu-item">
                    <a href="../user/dashboard.php" class="mobile-menu-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt menu-icon"></i> 
                        <?php echo $language === 'am' ? 'ዳሽቦርድ' : 'Dashboard'; ?>
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="../user/leaderboard.php" class="mobile-menu-link <?php echo $current_page == 'leaderboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-trophy menu-icon"></i> 
                        <?php echo $language === 'am' ? 'የአሸናፊዎች ሰሌዳ' : 'Leaderboard'; ?>
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="../user/settings.php" class="mobile-menu-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog menu-icon"></i> 
                        <?php echo $language === 'am' ? 'ቅንብሮች' : 'Settings'; ?>
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="../user/logout.php" class="mobile-menu-link">
                        <i class="fas fa-sign-out-alt menu-icon"></i> 
                        <?php echo $language === 'am' ? 'ውጣ' : 'Logout'; ?>
                    </a>
                </li>
                <?php else: ?>
                <li class="mobile-menu-item">
                    <a href="../user/login.php" class="mobile-menu-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
                        <i class="fas fa-sign-in-alt menu-icon"></i> 
                        <?php echo $language === 'am' ? 'ግባ' : 'Login'; ?>
                    </a>
                </li>
                <li class="mobile-menu-item">
                    <a href="../user/register.php" class="mobile-menu-link <?php echo $current_page == 'register.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus menu-icon"></i> 
                        <?php echo $language === 'am' ? 'ይመዝገቡ' : 'Register'; ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="offcanvas-footer mt-auto p-3 text-center">
                <small>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?></small>
            </div>
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
                    <a href="../user/dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <?php echo $language === 'am' ? 'ዳሽቦርድ' : 'Dashboard'; ?>
                    </a>
                    <a href="../user/leaderboard.php" class="nav-link <?php echo $current_page == 'leaderboard.php' ? 'active' : ''; ?>">
                        <?php echo $language === 'am' ? 'የአሸናፊዎች ሰሌዳ' : 'Leaderboard'; ?>
                    </a>
                    <a href="../user/settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                        <?php echo $language === 'am' ? 'ቅንብሮች' : 'Settings'; ?>
                    </a>
                    <a href="../user/logout.php" class="nav-link">
                        <?php echo $language === 'am' ? 'ውጣ' : 'Logout'; ?>
                    </a>
                    <?php else: ?>
                    <a href="../user/login.php" class="nav-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
                        <?php echo $language === 'am' ? 'ግባ' : 'Login'; ?>
                    </a>
                    <a href="../user/register.php" class="nav-link <?php echo $current_page == 'register.php' ? 'active' : ''; ?>">
                        <?php echo $language === 'am' ? 'ይመዝገቡ' : 'Register'; ?>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container"> 