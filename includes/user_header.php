<?php
// Get current page for active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Get user information if logged in
$user_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$baptism_name = $user_logged_in ? $_SESSION['baptism_name'] : '';

// Get language preference from cookie or database
$language = isset($_COOKIE['user_language']) ? $_COOKIE['user_language'] : 'am';

// If user is logged in, try to get language preference from database
if ($user_logged_in) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT language FROM user_preferences WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Update cookie to match database preference
        if (isset($row['language']) && $row['language'] !== $language) {
            $language = $row['language'];
            setcookie('user_language', $language, time() + (86400 * 90), "/");
        }
    }
    $stmt->close();
}

// --- START SEO & META TAG LOGIC ---
$page_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$site_name = APP_NAME;
$default_description = APP_FULL_NAME; // Use full name as default description
$default_image_url = BASE_URL . '/assets/favicon_io/android-chrome-512x512.png'; // Default image

$meta_title = (isset($page_title) ? $page_title . ' - ' : '') . $site_name;
$meta_description = $default_description;
$og_image_url = $default_image_url;
$canonical_url = $page_url;

// Specific tags for welcome.php
if ($current_page == 'welcome.php') {
    $meta_description = 'በየቀኑ የሚደረጉ ምስባክ፣ ወንጌል እንዲሁም ምንባቦችን ይከታተሉ ፣ የቤተክርስቲያን አገልግሎቶች ጊዜያት እና ቦታዎችን ይመልከቱ እንዲሁም በየቀኑ የተጠናቀቁ ተግባራትዎን ይመዝግቡ';
    $og_image_url = BASE_URL . '/assets/img/Eyesus Krstos.jpg'; // ASSUMED PATH - Please verify
    $canonical_url = USER_URL . '/welcome.php'; // Set specific canonical URL
    // Title can remain the default or be set specifically if $page_title is set in welcome.php
}
// --- END SEO & META TAG LOGIC ---

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($meta_title); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url); // Use canonical for consistency ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image_url); ?>">
    <?php 
    // Optional: Add image dimensions if known for better preview rendering
    // list($width, $height) = getimagesize($og_image_url); // Requires image path on server
    // if ($width && $height) {
    //     echo '<meta property="og:image:width" content="' . $width . '">';
    //     echo '<meta property="og:image:height" content="' . $height . '">';
    // }
    ?>

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo htmlspecialchars($canonical_url); // Use canonical for consistency ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($meta_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image_url); // Use same image as OG ?>">

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
                        <?php echo $language === 'am' ? 'የምዕመናን ሰሌዳ' : 'Leaderboard'; ?>
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
                        <?php echo $language === 'am' ? 'ግባ' : 'ግባ'; ?>
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
                        <?php echo $language === 'am' ? 'ዋና ገጽ' : 'Dashboard'; ?>
                    </a>
                    <a href="../user/leaderboard.php" class="nav-link <?php echo $current_page == 'leaderboard.php' ? 'active' : ''; ?>">
                        <?php echo $language === 'am' ? 'የምዕመናን ሰሌዳ' : 'Leaderboard'; ?>
                    </a>
                    <a href="../user/settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                        <?php echo $language === 'am' ? 'ማስተካከያ' : 'Settings'; ?>
                    </a>
                    <a href="../user/logout.php" class="nav-link">
                        <?php echo $language === 'am' ? 'ውጣ' : 'Logout'; ?>
                    </a>
                    <?php else: ?>
                    <a href="../user/login.php" class="nav-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>">
                        <?php echo $language === 'am' ? 'ግባ' : 'ግባ'; ?>
                    </a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="<?php echo isset($full_width_page) && $full_width_page ? 'full-width-container' : 'container'; ?>"> 
        </div>
    </main>

<!-- Script for language switching -->
<script>
$(document).ready(function() {
    // Function to set cookie
    function setCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }
    
    // We've removed the language toggles from the interface
    // but keeping the cookie function for potential future use
});
</script>
</body>
</html> 