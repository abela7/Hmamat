<?php
// Get current page for active menu item
$current_page = basename($_SERVER['PHP_SELF']);

// Get user information if logged in
$user_logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
$baptism_name = $user_logged_in ? $_SESSION['baptism_name'] : '';

// Get language preference from cookie or database
$language = isset($_COOKIE['user_language']) ? $_COOKIE['user_language'] : 'en';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Hmamat - Holy Week Companion</title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo $language == 'am' ? 'የሐሙስ ሰሞን እንቅስቃሴዎችን የሚከታተል መተግበሪያ። አመታዊ የሐሙስ ሰሞን እንቅስቃሴዎችን ይመዝግቡ እና በማህበረሰቡ ይሳተፉ።' : 'Holy Week activity tracker application. Track your annual Holy Week activities and engage with the community.'; ?>">
    <meta name="keywords" content="Holy Week, ሐሙስ ሰሞን, Ethiopian Orthodox, Fasting, Prayer, Spiritual Activities, Tracker">
    <meta name="author" content="St. Raphael Church">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://hmamat.abunetediros.org/">
    <meta property="og:title" content="<?php echo $language == 'am' ? 'ሐሙስ ሰሞን - የሐሙስ ሰሞን ተሳትፎ መተግበሪያ' : 'Hmamat - Holy Week Companion'; ?>">
    <meta property="og:description" content="<?php echo $language == 'am' ? 'የሐሙስ ሰሞን እንቅስቃሴዎችን የሚከታተል መተግበሪያ። አመታዊ የሐሙስ ሰሞን እንቅስቃሴዎችን ይመዝግቡ እና በማህበረሰቡ ይሳተፉ።' : 'Holy Week activity tracker application. Track your annual Holy Week activities and engage with the community.'; ?>">
    <meta property="og:image" content="https://hmamat.abunetediros.org/assets/img/logo.png">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://hmamat.abunetediros.org/">
    <meta property="twitter:title" content="<?php echo $language == 'am' ? 'ሐሙስ ሰሞን - የሐሙስ ሰሞን ተሳትፎ መተግበሪያ' : 'Hmamat - Holy Week Companion'; ?>">
    <meta property="twitter:description" content="<?php echo $language == 'am' ? 'የሐሙስ ሰሞን እንቅስቃሴዎችን የሚከታተል መተግበሪያ። አመታዊ የሐሙስ ሰሞን እንቅስቃሴዎችን ይመዝግቡ እና በማህበረሰቡ ይሳተፉ።' : 'Holy Week activity tracker application. Track your annual Holy Week activities and engage with the community.'; ?>">
    <meta property="twitter:image" content="https://hmamat.abunetediros.org/assets/img/logo.png">
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/favicon_io/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon_io/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon_io/favicon-16x16.png">
    <link rel="manifest" href="/assets/favicon_io/site.webmanifest">
    <meta name="theme-color" content="#301934">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/user/css/style.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container-fluid px-0">
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container">
                <a class="navbar-brand" href="/index.php">
                    <img src="/assets/img/logo.png" alt="Hmamat Logo" height="40"> Hmamat
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                    <div class="offcanvas-header">
                        <h5 class="offcanvas-title" id="offcanvasNavbarLabel">
                            <?php echo $language == 'am' ? 'ምናሌ' : 'Menu'; ?>
                        </h5>
                        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                    <div class="offcanvas-body">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                            <?php if ($user_logged_in): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/user/dashboard.php">
                                        <i class="fas fa-tachometer-alt me-1"></i>
                                        <?php echo $language == 'am' ? 'ዳሽቦርድ' : 'Dashboard'; ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/user/leaderboard.php">
                                        <i class="fas fa-trophy me-1"></i>
                                        <?php echo $language == 'am' ? 'የደረጃ ሰንጠረዥ' : 'Leaderboard'; ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/user/settings.php">
                                        <i class="fas fa-cog me-1"></i>
                                        <?php echo $language == 'am' ? 'ቅንብሮች' : 'Settings'; ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/user/logout.php">
                                        <i class="fas fa-sign-out-alt me-1"></i>
                                        <?php echo $language == 'am' ? 'ውጣ' : 'Logout'; ?>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/user/login.php">
                                        <i class="fas fa-sign-in-alt me-1"></i>
                                        <?php echo $language == 'am' ? 'ግባ' : 'Login'; ?>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/user/register.php">
                                        <i class="fas fa-user-plus me-1"></i>
                                        <?php echo $language == 'am' ? 'ይመዝገቡ' : 'Register'; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <!-- Language selector -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-globe me-1"></i>
                                    <?php echo $language == 'am' ? 'ቋንቋ' : 'Language'; ?>
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="languageDropdown">
                                    <li><a class="dropdown-item <?php echo $language == 'en' ? 'active' : ''; ?>" href="#" onclick="setLanguage('en')">English</a></li>
                                    <li><a class="dropdown-item <?php echo $language == 'am' ? 'active' : ''; ?>" href="#" onclick="setLanguage('am')">አማርኛ</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
        <script>
            function setLanguage(lang) {
                document.cookie = "user_language=" + lang + "; path=/; max-age=" + (60 * 60 * 24 * 30); // 30 days
                location.reload();
            }
        </script>

        <!-- Main Content Container -->
        <div class="container main-content py-4">
        </div>
    </div>
</body>
</html> 