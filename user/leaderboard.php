<?php
// Include necessary files
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth_check.php';

// Check if user is logged in
requireUserLogin();

// Set page title
$page_title = "Leaderboard";

// Get user information
$user_id = $_SESSION['user_id'];
$baptism_name = $_SESSION['baptism_name'];

// Get user's language preference
$language = isset($_COOKIE['user_language']) ? $_COOKIE['user_language'] : 'en';

// Get leaderboard data
$leaderboard = array();
$stmt = $conn->prepare("SELECT u.baptism_name, SUM(ual.points_earned) as total_points
                       FROM users u
                       JOIN user_activity_log ual ON u.id = ual.user_id
                       LEFT JOIN user_preferences up ON u.id = up.user_id
                       WHERE ual.date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                       AND ual.status = 'done'
                       AND (up.show_on_leaderboard = 1 OR up.show_on_leaderboard IS NULL)
                       GROUP BY u.id
                       ORDER BY total_points DESC
                       LIMIT 20");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $leaderboard[] = $row;
}
$stmt->close();

// Get user's rank and total points
$user_rank = 0;
$user_total_points = 0;

$stmt = $conn->prepare("SELECT user_id, total_points, rank
                       FROM (
                           SELECT ual.user_id, SUM(ual.points_earned) as total_points,
                           RANK() OVER (ORDER BY SUM(ual.points_earned) DESC) as rank
                           FROM user_activity_log ual
                           JOIN users u ON ual.user_id = u.id
                           LEFT JOIN user_preferences up ON u.id = up.user_id
                           WHERE ual.date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
                           AND ual.status = 'done'
                           AND (up.show_on_leaderboard = 1 OR up.show_on_leaderboard IS NULL)
                           GROUP BY ual.user_id
                       ) as rankings
                       WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $user_rank = $row['rank'];
    $user_total_points = $row['total_points'];
} else {
    $user_rank = "-";
    $user_total_points = 0;
}
$stmt->close();

// Set full width page flag
$full_width_page = true;

// Include header
include_once '../includes/user_header.php';
?>

<div class="simple-container">
    <div class="activity-title-section">
        <h1 class="main-title"><?php echo $language === 'am' ? 'የምዕመናን ሰሌዳ' : 'Leaderboard'; ?></h1>
    </div>
    
    <div class="user-rank-card">
        <div class="user-rank-info">
            <div class="rank-label"><?php echo $language === 'am' ? 'የእርስዎ ደረጃ:' : 'Your Rank:'; ?></div>
            <div class="rank-number"><?php echo $user_rank; ?></div>
        </div>
        <div class="user-points-info">
            <div class="points-label"><?php echo $language === 'am' ? 'ነጥቦች:' : 'Points:'; ?></div>
            <div class="points-number"><?php echo $user_total_points; ?></div>
        </div>
    </div>
    
    <?php if (empty($leaderboard)): ?>
    <div class="empty-state">
        <p><?php echo $language === 'am' ? 'ለጊዜውጊዜው ምንም የምዕመናንመናን መረጃ የለም።' : 'No data available for the leaderboard yet.'; ?></p>
    </div>
    <?php else: ?>
    <div class="leaderboard-container">
        <?php foreach ($leaderboard as $index => $user): ?>
        <div class="leaderboard-item <?php echo ($user['baptism_name'] === $baptism_name) ? 'current-user' : ''; ?>">
            <div class="rank-badge"><?php echo $index + 1; ?></div>
            <div class="user-name"><?php echo htmlspecialchars($user['baptism_name']); ?></div>
            <div class="user-points"><?php echo $user['total_points']; ?> <?php echo $language === 'am' ? 'ነጥቦች' : 'points'; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="back-link">
        <a href="dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> <?php echo $language === 'am' ? 'ወደ ዳሽቦርድ ተመለስ' : 'Back to Dashboard'; ?>
        </a>
    </div>
</div>

<style>
.simple-container {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
    padding: 20px;
    background-color: #f8f5f0;
}

.activity-title-section {
    margin-bottom: 30px;
    text-align: center;
}

.main-title {
    font-size: 2.2rem;
    color: #301934;
    margin-bottom: 5px;
    font-weight: 700;
}

.user-rank-card {
    background-color: #F1ECE2;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 25px;
    display: flex;
    justify-content: space-around;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    width: 100%;
}

.user-rank-info, .user-points-info {
    text-align: center;
}

.rank-label, .points-label {
    font-size: 1.1rem;
    color: #5D4225;
    margin-bottom: 5px;
}

.rank-number, .points-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #301934;
}

.empty-state {
    background-color: #F1ECE2;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    font-size: 1.1rem;
    color: #5D4225;
}

.leaderboard-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
}

.leaderboard-item {
    background-color: #F1ECE2;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
    width: 100%;
}

.leaderboard-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

.leaderboard-item.current-user {
    background-color: #e0d7c5;
    border-left: 5px solid #301934;
}

.rank-badge {
    width: 40px;
    height: 40px;
    background-color: #301934;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    margin-right: 15px;
}

.user-name {
    flex-grow: 1;
    font-weight: 600;
    color: #301934;
}

.user-points {
    background-color: #DAA520;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.9rem;
}

.back-link {
    margin-top: 30px;
    text-align: center;
}

.back-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #301934;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.back-button:hover {
    background-color: #421c54;
    transform: translateY(-2px);
    color: white;
}

@media (max-width: 576px) {
    .rank-number, .points-number {
        font-size: 2rem;
    }
    
    .leaderboard-item {
        padding: 12px;
    }
    
    .rank-badge {
        width: 32px;
        height: 32px;
        margin-right: 10px;
    }
    
    .user-points {
        font-size: 0.8rem;
    }
}
</style>

<?php
// Include footer
include_once '../includes/user_footer.php';
?> 