<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = "Welcome to HIMAMAT";

// Initialize variables
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$language = isset($_GET['lang']) ? $_GET['lang'] : 'en';
$baptism_name = isset($_POST['baptism_name']) ? trim($_POST['baptism_name']) : '';
$error = '';

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Validate baptism name
    if (empty($baptism_name)) {
        $error = ($language === 'am') ? 'የክርስትና ስም አስፈላጊ ነው።' : 'Baptism name is required.';
    } else {
        // Check if a user with this baptism name exists
        $stmt = $conn->prepare("SELECT id, baptism_name, unique_id, role FROM users WHERE baptism_name = ?");
        $stmt->bind_param("s", $baptism_name);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // User exists, just log them in
            $user = $result->fetch_assoc();
            createUserSession($user['id'], $user['baptism_name'], $user['unique_id'], $user['role']);
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            // Create a new user with default password (000000)
            $default_password = password_hash('000000', PASSWORD_DEFAULT);
            $unique_id = bin2hex(random_bytes(16)); // Generate unique ID
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $device_info = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt = $conn->prepare("INSERT INTO users (baptism_name, password, unique_id, last_ip, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssss", $baptism_name, $default_password, $unique_id, $ip, $device_info);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                createUserSession($user_id, $baptism_name, $unique_id);
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $error = ($language === 'am') ? 'ይቅርታ፣ የተፈጠረ ስህተት። እባክዎ ዳግም ይሞክሩ።' : 'Sorry, there was an error. Please try again.';
            }
        }
        $stmt->close();
    }
}

// Include minimal header (without the user login check)
?>
<!DOCTYPE html>
<html lang="<?php echo $language === 'am' ? 'am' : 'en'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $language === 'am' ? 'እንኳን ወደ ሕማማት ደህና መጡ' : 'Welcome to HIMAMAT'; ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .welcome-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 1rem;
        }
        .language-btn {
            width: 120px;
            margin: 0.5rem;
            border: 2px solid #ddd;
            background: white;
            border-radius: 5px;
            padding: 0.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .language-btn:hover, .language-btn.active {
            border-color: #6c757d;
            background-color: #f8f9fa;
        }
        .progress-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .progress-step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            color: #6c757d;
        }
        .progress-step.active {
            background-color: #6c757d;
            color: white;
        }
        .progress-line {
            height: 3px;
            width: 60px;
            background-color: #dee2e6;
            margin-top: 15px;
        }
        .progress-line.active {
            background-color: #6c757d;
        }
        .step-content {
            display: none;
        }
        .step-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .btn-nav {
            min-width: 100px;
        }
        .logo-img {
            max-width: 100px;
            margin-bottom: 1rem;
        }
        .welcome-title {
            margin-bottom: 1.5rem;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container welcome-container">
        <div class="text-center mb-4">
            <img src="../assets/images/logo.png" alt="HIMAMAT Logo" class="logo-img">
            <h1 class="welcome-title">
                <?php echo $language === 'am' ? 'እንኳን ወደ ሕማማት ደህና መጡ' : 'Welcome to HIMAMAT'; ?>
            </h1>
        </div>
        
        <div class="progress-indicator">
            <div class="progress-step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
            <div class="progress-line <?php echo $step >= 2 ? 'active' : ''; ?>"></div>
            <div class="progress-step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
            <div class="progress-line <?php echo $step >= 3 ? 'active' : ''; ?>"></div>
            <div class="progress-step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Step 1: Language Selection -->
                <div class="step-content <?php echo $step === 1 ? 'active' : ''; ?>" id="step1">
                    <h2 class="card-title mb-4 text-center">
                        <?php echo $language === 'am' ? 'ቋንቋ ይምረጡ' : 'Select Your Language'; ?>
                    </h2>
                    
                    <div class="d-flex justify-content-center mb-4">
                        <button class="language-btn <?php echo $language === 'en' ? 'active' : ''; ?>" onclick="setLanguage('en')">
                            English
                        </button>
                        <button class="language-btn <?php echo $language === 'am' ? 'active' : ''; ?>" onclick="setLanguage('am')">
                            አማርኛ
                        </button>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="welcome.php?step=2&lang=<?php echo $language; ?>" class="btn btn-nav">
                            <?php echo $language === 'am' ? 'ቀጣይ' : 'Next'; ?> →
                        </a>
                    </div>
                </div>
                
                <!-- Step 2: About HIMAMAT -->
                <div class="step-content <?php echo $step === 2 ? 'active' : ''; ?>" id="step2">
                    <h2 class="card-title mb-4 text-center">
                        <?php echo $language === 'am' ? 'ስለ ሕማማት' : 'About HIMAMAT'; ?>
                    </h2>
                    
                    <div class="mb-4">
                        <?php if ($language === 'am'): ?>
                            <p>ሕማማት (የመከራ ሳምንት) በታላቁ የኢትዮጵያ ፆም ውስጥ የመጨረሻው ሳምንት ነው፣ የሆሳዕና ዕለት ጀምሮ እስከ ፋሲካ ድረስ ይቆያል።</p>
                            <p>ይህ የድር መተግበሪያ በሕማማት ሳምንት ውስጥ የመንፈሳዊ እንቅስቃሴዎን እንዲከታተሉ ይረዳዎታል፡-</p>
                            <ul>
                                <li>በየቀኑ ጸሎቶችን እና ምንባቦችን ይከታተሉ</li>
                                <li>የቤተክርስቲያን አገልግሎቶች ጊዜያት እና ቦታዎችን ይመልከቱ</li>
                                <li>በየቀኑ የተጠናቀቁ ተግባራትዎን ይመዝግቡ</li>
                                <li>ከሌሎች ምእመናን ጋር በማወዳደሪያ (ሊደርቦርድ) ይሳተፉ</li>
                            </ul>
                        <?php else: ?>
                            <p>HIMAMAT (Passion Week) is the final week of the Great Ethiopian Lent, spanning from Palm Sunday (Hosanna) to Easter Sunday.</p>
                            <p>This web application helps you track your spiritual activities during HIMAMAT:</p>
                            <ul>
                                <li>Follow daily prayers and readings</li>
                                <li>Find church service times and locations</li>
                                <li>Record your daily completed activities</li>
                                <li>Participate in a community leaderboard</li>
                            </ul>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="welcome.php?step=1&lang=<?php echo $language; ?>" class="btn btn-nav btn-outline">
                            ← <?php echo $language === 'am' ? 'ኋላ' : 'Back'; ?>
                        </a>
                        <a href="welcome.php?step=3&lang=<?php echo $language; ?>" class="btn btn-nav">
                            <?php echo $language === 'am' ? 'ቀጣይ' : 'Next'; ?> →
                        </a>
                    </div>
                </div>
                
                <!-- Step 3: Registration -->
                <div class="step-content <?php echo $step === 3 ? 'active' : ''; ?>" id="step3">
                    <h2 class="card-title mb-4 text-center">
                        <?php echo $language === 'am' ? 'የክርስትና ስምዎን ያስገቡ' : 'Enter Your Baptism Name'; ?>
                    </h2>
                    
                    <form method="post" action="welcome.php?step=3&lang=<?php echo $language; ?>">
                        <div class="form-group mb-4">
                            <label for="baptism_name" class="form-label">
                                <?php echo $language === 'am' ? 'የክርስትና ስም' : 'Baptism Name'; ?>
                            </label>
                            <input type="text" class="form-control" id="baptism_name" name="baptism_name" 
                                   value="<?php echo htmlspecialchars($baptism_name); ?>" required autofocus>
                            <small class="form-text text-muted">
                                <?php echo $language === 'am' ? 'ስምዎን ለመመዝገብ የክርስትና ስምዎን ብቻ ያስገቡ።' : 'Enter only your baptism name to register.'; ?>
                            </small>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="welcome.php?step=2&lang=<?php echo $language; ?>" class="btn btn-nav btn-outline">
                                ← <?php echo $language === 'am' ? 'ኋላ' : 'Back'; ?>
                            </a>
                            <button type="submit" name="register" class="btn btn-nav">
                                <?php echo $language === 'am' ? 'መዝግብ' : 'Register'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function setLanguage(lang) {
            window.location.href = 'welcome.php?step=1&lang=' + lang;
        }
    </script>
</body>
</html> 