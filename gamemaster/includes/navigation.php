<?php
// Check if database connection exists, if not include it
if (!isset($conn)) {
    require_once(__DIR__ . '/../../config/database.php');
}

// Check if session exists, if not start it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize default counts
$notifications_count = ['count' => 0];
$games_count = ['count' => 0];
$pending_matches = ['count' => 0];

// Function to safely execute queries
function executeQuery($conn, $query) {
    try {
        if ($conn && $conn->ping()) {
            $result = $conn->query($query);
            return $result ? $result->fetch_assoc() : ['count' => 0];
        }
        return ['count' => 0];
    } catch (Exception $e) {
        error_log("Database query error: " . $e->getMessage());
        return ['count' => 0];
    }
}

// Safely get counts
if (isset($_SESSION['user_id'])) {
    // Get notifications count
    $notifications_count = executeQuery($conn, 
        "SELECT COUNT(*) as count FROM notifications 
        WHERE user_id = " . (int)$_SESSION['user_id'] . " 
        AND is_read = 0"
    );

    // Fetch games count for the logged-in gamemaster
    $games_count_query = "
        SELECT COUNT(DISTINCT t.game_id) as count
        FROM tournaments t
        INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        WHERE tgm.user_id = ?";

    $stmt = $conn->prepare($games_count_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $games_count = $stmt->get_result()->fetch_assoc();

    // Get pending matches count
    $pending_matches = executeQuery($conn, 
        "SELECT COUNT(*) as count FROM matches m 
        INNER JOIN tournaments t ON m.tournament_id = t.id 
        INNER JOIN gamemaster_games gg ON t.game_id = gg.game_id 
        WHERE gg.user_id = " . (int)$_SESSION['user_id'] . " 
        AND m.status = 'pending'"
    );
}
?>

<!-- Top Navbar -->
<nav class="top-navbar">
    <div class="navbar-left">
        <button class="sidebar-toggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="navbar-brand">
            <i class="bi bi-trophy-fill"></i>
            <span>Tournament Manager</span>
        </div>
    </div>
    <div class="navbar-right">
        <div class="nav-item">
            <a href="notifications.php" class="nav-link">
                <i class="bi bi-bell-fill"></i>
                <?php if($notifications_count['count'] > 0): ?>
                <span class="notification-badge"><?php echo htmlspecialchars($notifications_count['count']); ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-item">
            <a href="profile.php" class="nav-link">
                <?php if(isset($_SESSION['avatar']) && !empty($_SESSION['avatar'])): ?>
                    <img src="../uploads/avatars/<?php echo htmlspecialchars($_SESSION['avatar']); ?>" alt="Profile" class="profile-image">
                <?php else: ?>
                    <i class="bi bi-person-circle"></i>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></span>
            </a>
        </div>
        <div class="nav-item">
            <a href="../logout.php" class="nav-link logout-link">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</nav>

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-menu">
        <!-- Main Navigation -->
        <div class="menu-section">
            <div class="menu-title">MAIN NAVIGATION</div>
            <ul class="menu-items">
                <li>
                    <a href="dashboard.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="my_games.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_games.php' ? 'active' : ''; ?>">
                        <i class="bi bi-controller"></i>
                        <span>My Games</span>
                        <?php if($games_count['count'] > 0): ?>
                            <span class="badge"><?php echo $games_count['count']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="schedules.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'schedules.php' ? 'active' : ''; ?>">
                        <i class="bi bi-calendar3"></i>
                        <span>Schedules</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tournament Management -->
        <div class="menu-section">
            <div class="menu-title">TOURNAMENT MANAGEMENT</div>
            <ul class="menu-items">
                <li>
                    <a href="matches.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'matches.php' ? 'active' : ''; ?>">
                        <i class="bi bi-trophy"></i>
                        <span>Matches</span>
                        <?php 
                        if($pending_matches['count'] > 0):
                        ?>
                        <span class="badge badge-warning"><?php echo $pending_matches['count']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="teams.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'teams.php' ? 'active' : ''; ?>">
                        <i class="bi bi-people"></i>
                        <span>Teams</span>
                    </a>
                </li>
                <li>
                    <a href="statistics.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'statistics.php' ? 'active' : ''; ?>">
                        <i class="bi bi-graph-up"></i>
                        <span>Statistics</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Settings -->
        <div class="menu-section">
            <div class="menu-title">SETTINGS</div>
            <ul class="menu-items">
                <li>
                    <a href="profile.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <i class="bi bi-person-gear"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li>
                    <a href="settings.php" class="menu-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                        <i class="bi bi-gear"></i>
                        <span>Settings</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside> 