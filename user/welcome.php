<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Set page title
$page_title = "የሰሙነ ሕማማት የመንፈሳዊ ምግባራት መከታተያ ፕሮግራም";

// Initialize variables
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$language = 'am'; // Force Amharic
$baptism_name = isset($_POST['baptism_name']) ? trim($_POST['baptism_name']) : '';
$error = '';

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Validate baptism name
    if (empty($baptism_name)) {
        $error = 'የክርስትና ስም አስፈላጊ ነው።';
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
                
                // Set language cookie
                setcookie('user_language', 'am', time() + (86400 * 90), "/");
                
                // Redirect to dashboard
                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'ይቅርታ፣ የተፈጠረ ስህተት። እባክዎ ዳግም ይሞክሩ።';
            }
        }
        $stmt->close();
    }
}

// Include minimal header (without the user login check)
?>
<!DOCTYPE html>
<html lang="am">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ሰሙነ ሕማማት የመንፈሳዊ ምግባራት መከታተያ ፕሮግራም</title>
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
            max-width: 180px;
            margin-bottom: 1rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
            <img src="../assets/favicon_io/android-chrome-512x512.png" alt="HIMAMAT Logo" class="logo-img">
            <h1 class="welcome-title">እንኳን ደህና መጡ</h1>
            <a href="login.php" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left"></i> ወደ መግቢያ ገጽ ተመለስ
            </a>
        </div>
        
        <div class="progress-indicator">
            <div class="progress-step <?php echo $step >= 1 ? 'active' : ''; ?>">1</div>
            <div class="progress-line <?php echo $step >= 2 ? 'active' : ''; ?>"></div>
            <div class="progress-step <?php echo $step >= 2 ? 'active' : ''; ?>">2</div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Step 1: About HIMAMAT -->
                <div class="step-content <?php echo $step === 1 ? 'active' : ''; ?>" id="step1">
                    <h2 class="card-title mb-4 text-center">ስለ ድህረ ገጽ</h2>
                    
                    <div class="mb-4">
                        <h3>እዚህ ድህረገጽ ላይ የሚያደርጉት ማንኛውም አይነት እንቅስቃሴ በምንም አይነት መልኩ የእርስዎን ማንነት ለሌላ ሰው ሊያሳውቅ ሊያሳውቅ አይችልም። ይህም ማለት እዚህ ላይ የሚሞሉት መንፈሳዊ ተግባራት ክትትሎችን ከእርስዎ በቀር ማንም ሊከታተልዎ ወይምወይም ሊያውቅ አይችልም። ለመመዝገብ የክርስትና ስም ወይንም ማንኛውንም አይነት ስም መጠቀም ይችላሉ።  
የዚህ ድህረ ገጽ ዋነኛ አላማ በዐቢይ ጾም የመጨረሻ ሳምንት ሕማማት ላይ የሚያደርጉትን መንፈሳዊ እንቅስቃሴዎች በመከታታል ያሉበትን ሁኔታ ለመገምገም እንዲሁም ሌሎች ምዕመናን ያሉበትን ሁኔታ እያዩ እርስዎም እንዲበረቱ፣ ሌላ ያልበረታ ሰውንም እንዲያበረቱ ታስቦ የተዘጋጀ ነው። ሌሎች ምዕመናን በክርስትና ስምዎ ያስገቡትን መረጃ እንዳያዩት ከፈለጉ ሴቲንግ ላይ ማስተካከል ይችላሉ።
                </br><P>የትኛውም አይነት መረጃ ከበዓለ ፋሲካ በኋላ ሙሉ በ ሙሉ የሚሰረዝ ይሆናል። </p>
                </h3>
                        <ul>
                            <li>በየቀኑ የሚደረጉ ምስባክ፣ ወንጌል እንዲሁም ምንባቦችን ይከታተሉ</li>
                            <li>የቤተክርስቲያን አገልግሎቶች ጊዜያት እና ቦታዎችን ይመልከቱ</li>
                            <li>በየቀኑ የተጠናቀቁ ተግባራትዎን ይመዝግቡ</li>
                           
                        </ul>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="welcome.php?step=2" class="btn btn-nav">
                            ቀጣይ →
                        </a>
                    </div>
                </div>
                
                <!-- Step 2: Registration -->
                <div class="step-content <?php echo $step === 2 ? 'active' : ''; ?>" id="step2">
                    <h2 class="card-title mb-4 text-center">የክርስትና ስምዎን ያስገቡ</h2>
                    
                    <form method="post" action="welcome.php?step=2">
                        <div class="form-group mb-4">
                            <label for="baptism_name" class="form-label">የክርስትና ስም</label>
                            <input type="text" class="form-control" id="baptism_name" name="baptism_name" 
                                   value="<?php echo htmlspecialchars($baptism_name); ?>" required autofocus>
                            <small class="form-text text-muted">
                               የክርስትና ስምዎትትን ወይም ደስ ያልዎትን ስም ያስገቡ ነገር ግን በአለማዊ ስምዎ ሚታወቁበትን ስም አይጠቀሙ።።
                            </small>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="welcome.php?step=1" class="btn btn-nav btn-outline">
                                ← ተመለስ
                            </a>
                            <button type="submit" name="register" class="btn btn-nav">
                                መዝግብ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 