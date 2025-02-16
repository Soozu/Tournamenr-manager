<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

// Get gamemaster profile info
$profile_query = "
    SELECT 
        u.*,
        COUNT(DISTINCT tgm.tournament_id) as tournaments_managed,
        COUNT(DISTINCT gg.game_id) as games_assigned
    FROM users u
    LEFT JOIN tournament_game_masters tgm ON u.id = tgm.user_id
    LEFT JOIN gamemaster_games gg ON u.id = gg.user_id
    WHERE u.id = ?
    GROUP BY u.id";

$stmt = $conn->prepare($profile_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Get assigned games
$games_query = "
    SELECT 
        g.*,
        COUNT(DISTINCT t.id) as tournament_count
    FROM games g
    INNER JOIN gamemaster_games gg ON g.id = gg.game_id
    LEFT JOIN tournaments t ON g.id = t.game_id AND t.gamemaster_id = ?
    WHERE gg.user_id = ?
    GROUP BY g.id";

$stmt = $conn->prepare($games_query);
$stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$stmt->execute();
$games = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/profile.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="main-content">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-cover"></div>
                <div class="profile-info">
                    <div class="profile-avatar">
                        <img src="<?php echo $profile['avatar'] ?? 'images/default-avatar.png'; ?>" alt="Profile">
                    </div>
                    <div class="profile-details">
                        <h1><?php echo htmlspecialchars($profile['username']); ?></h1>
                        <p class="role">Game Master</p>
                        <div class="profile-stats">
                            <div class="stat-item">
                                <span class="value"><?php echo $profile['tournaments_managed']; ?></span>
                                <span class="label">Tournaments</span>
                            </div>
                            <div class="stat-item">
                                <span class="value"><?php echo $profile['games_assigned']; ?></span>
                                <span class="label">Games</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="profile-content">
                <!-- Personal Information -->
                <div class="profile-section">
                    <h2><i class="bi bi-person"></i> Personal Information</h2>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Username</label>
                            <span><?php echo htmlspecialchars($profile['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Email</label>
                            <span><?php echo htmlspecialchars($profile['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Joined Date</label>
                            <span><?php echo date('F d, Y', strtotime($profile['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <label>Status</label>
                            <span class="status-badge active">Active</span>
                        </div>
                    </div>
                </div>

                <!-- Assigned Games -->
                <div class="profile-section">
                    <h2><i class="bi bi-controller"></i> Assigned Games</h2>
                    <div class="games-grid">
                        <?php while($game = $games->fetch_assoc()): ?>
                            <div class="game-card">
                                <img src="../images/games/<?php echo $game['icon']; ?>" alt="<?php echo htmlspecialchars($game['name']); ?>" class="game-icon">
                                <div class="game-info">
                                    <h3><?php echo htmlspecialchars($game['name']); ?></h3>
                                    <span class="platform"><?php echo htmlspecialchars($game['platform']); ?></span>
                                    <div class="tournament-count">
                                        <i class="bi bi-trophy"></i>
                                        <?php echo $game['tournament_count']; ?> Tournaments
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="profile-actions">
                    <button class="btn btn-primary" onclick="location.href='edit_profile.php'">
                        <i class="bi bi-pencil"></i> Edit Profile
                    </button>
                    <button class="btn btn-secondary" onclick="location.href='change_password.php'">
                        <i class="bi bi-lock"></i> Change Password
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 