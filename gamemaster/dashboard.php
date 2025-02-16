<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

// Get gamemaster's assigned games with detailed stats
try {
    $games_query = "
        SELECT 
            g.*,
            COUNT(DISTINCT t.id) as total_tournaments,
            COUNT(DISTINCT CASE WHEN t.status = 'active' THEN t.id END) as active_tournaments,
            COUNT(DISTINCT CASE WHEN t.status = 'ongoing' THEN t.id END) as ongoing_tournaments,
            COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tournaments,
            COUNT(DISTINCT CASE WHEN t.status = 'registration' THEN t.id END) as registration_tournaments,
            COUNT(DISTINCT tt.team_id) as total_teams
        FROM games g
        INNER JOIN tournaments t ON g.id = t.game_id
        INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id AND tt.status = 'approved'
        WHERE tgm.user_id = ?
        GROUP BY g.id
        ORDER BY g.name ASC
        LIMIT 3";

    $stmt = $conn->prepare($games_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $games_result = $stmt->get_result();

    // Get today's matches with detailed information
    $today_matches_query = "
        SELECT 
            tb.*,
            t.name as tournament_name,
            t.status as tournament_status,
            g.name as game_name,
            g.icon as game_icon,
            t1.team_name as team1_name,
            t1.team_logo as team1_logo,
            t2.team_name as team2_name,
            t2.team_logo as team2_logo
        FROM tournament_brackets tb
        INNER JOIN tournaments t ON tb.tournament_id = t.id
        INNER JOIN games g ON t.game_id = g.id
        INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        LEFT JOIN teams t1 ON tb.team1_id = t1.id
        LEFT JOIN teams t2 ON tb.team2_id = t2.id
        WHERE tgm.user_id = ?
        AND DATE(tb.match_date) = CURDATE()
        AND tb.status IN ('pending', 'ongoing')
        ORDER BY tb.match_time ASC";

    $stmt = $conn->prepare($today_matches_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $today_matches = $stmt->get_result();

    // Get recent activity - Updated query with correct column names
    $recent_activity_query = "
        SELECT 
            n.*,
            t.name as tournament_name,
            g.name as game_name,
            g.icon as game_icon
        FROM notifications n
        INNER JOIN tournaments t ON n.reference_id = t.id 
        INNER JOIN games g ON t.game_id = g.id
        INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        WHERE tgm.user_id = ?
        AND n.type IN ('tournament_status_update', 'match_update', 'team_status_update')
        ORDER BY n.created_at DESC
        LIMIT 5";

    $stmt = $conn->prepare($recent_activity_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $recent_activity = $stmt->get_result();

    // Fetch recent tournaments (last 3) with their status and details
    $recent_tournaments_query = "
        SELECT 
            t.*,
            g.name as game_name,
            g.icon as game_icon,
            COUNT(DISTINCT tt.team_id) as team_count,
            w.team_name as winner_name,
            w.team_logo as winner_logo
        FROM tournaments t
        INNER JOIN games g ON t.game_id = g.id
        INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id AND tt.status = 'approved'
        LEFT JOIN teams w ON t.winner_id = w.id
        WHERE tgm.user_id = ?
        GROUP BY t.id
        ORDER BY t.created_at DESC
        LIMIT 3";

    $stmt = $conn->prepare($recent_tournaments_query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $recent_tournaments = $stmt->get_result();

} catch (Exception $e) {
    die("Error fetching dashboard data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="main-content">
        <div class="dashboard-header">
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
            <p class="current-date"><?php echo date('l, F j, Y'); ?></p>
        </div>

        <!-- Games Overview Section -->
        <div class="games-section">
            <div class="section-header">
                <h2><i class="bi bi-controller"></i> Your Games</h2>
                <span class="total-count"><?php echo $games_result->num_rows; ?> Games</span>
            </div>
            
            <div class="games-grid">
                <?php while($game = $games_result->fetch_assoc()): ?>
                    <div class="game-card <?php echo strtolower(str_replace(' ', '-', $game['name'])); ?>">
                        <div class="game-header">
                            <img src="../images/games/<?php echo $game['icon']; ?>" 
                                 alt="<?php echo htmlspecialchars($game['name']); ?>" 
                                 class="game-icon">
                            <div class="game-info">
                                <h3><?php echo htmlspecialchars($game['name']); ?></h3>
                                <span class="game-type">
                                    <?php if($game['name'] === 'Tekken 8'): ?>
                                        <i class="bi bi-person-fill"></i> 1v1
                                    <?php elseif($game['name'] === 'Mobile Legends'): ?>
                                        <i class="bi bi-people-fill"></i> 5v5
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="game-stats">
                            <div class="stat-item <?php echo $game['name'] === 'Tekken 8' ? 'tekken' : 'ml'; ?>">
                                <div class="stat-value"><?php echo $game['registration_tournaments']; ?></div>
                                <div class="stat-label">Registration</div>
                            </div>
                            <div class="stat-item <?php echo $game['name'] === 'Tekken 8' ? 'tekken' : 'ml'; ?>">
                                <div class="stat-value"><?php echo $game['active_tournaments']; ?></div>
                                <div class="stat-label">Active</div>
                            </div>
                            <div class="stat-item <?php echo $game['name'] === 'Tekken 8' ? 'tekken' : 'ml'; ?>">
                                <div class="stat-value"><?php echo $game['ongoing_tournaments']; ?></div>
                                <div class="stat-label">Ongoing</div>
                            </div>
                            <div class="stat-item <?php echo $game['name'] === 'Tekken 8' ? 'tekken' : 'ml'; ?>">
                                <div class="stat-value"><?php echo $game['completed_tournaments']; ?></div>
                                <div class="stat-label">Completed</div>
                            </div>
                        </div>

                        <div class="game-progress">
                            <div class="progress-item">
                                <div class="progress-header">
                                    <label>Tournament Progress</label>
                                    <span class="progress-count">
                                        <?php echo $game['completed_tournaments']; ?>/<?php echo $game['total_tournaments']; ?>
                                    </span>
                                </div>
                                <div class="progress">
                                    <?php 
                                    $total_tournaments = $game['total_tournaments'];
                                    $completion = $total_tournaments > 0 ? 
                                        ($game['completed_tournaments'] / $total_tournaments) * 100 : 0;
                                    ?>
                                    <div class="progress-bar <?php echo $game['name'] === 'Tekken 8' ? 'tekken' : 'ml'; ?>" 
                                         style="width: <?php echo $completion; ?>%">
                                        <?php echo round($completion); ?>%
                                    </div>
                                </div>
                            </div>
                            <div class="total-teams">
                                <i class="bi bi-people-fill"></i>
                                <?php echo $game['total_teams']; ?> Total Teams
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Today's Matches Section -->
        <div class="matches-section">
            <div class="section-header">
                <h2><i class="bi bi-calendar-event"></i> Today's Matches</h2>
                <span class="total-count"><?php echo $today_matches->num_rows; ?> Matches</span>
            </div>
            
            <div class="matches-grid">
                <?php if($today_matches->num_rows > 0): ?>
                    <?php while($match = $today_matches->fetch_assoc()): ?>
                        <div class="match-card">
                            <div class="match-header">
                                <img src="../images/games/<?php echo $match['game_icon']; ?>" alt="<?php echo $match['game_name']; ?>" class="game-icon">
                                <div class="match-info">
                                    <h4><?php echo htmlspecialchars($match['tournament_name']); ?></h4>
                                    <span class="match-time">
                                        <i class="bi bi-clock"></i>
                                        <?php echo date('h:i A', strtotime($match['match_time'])); ?>
                                    </span>
                                </div>
                                <span class="match-status <?php echo $match['status']; ?>">
                                    <?php echo ucfirst($match['status']); ?>
                                </span>
                            </div>
                            
                            <div class="teams-container">
                                <div class="team">
                                    <?php if($match['team1_logo']): ?>
                                        <img src="../images/team-logos/<?php echo $match['team1_logo']; ?>" alt="Team 1" class="team-logo">
                                    <?php endif; ?>
                                    <span class="team-name"><?php echo htmlspecialchars($match['team1_name']); ?></span>
                                </div>
                                <div class="vs">VS</div>
                                <div class="team">
                                    <?php if($match['team2_logo']): ?>
                                        <img src="../images/team-logos/<?php echo $match['team2_logo']; ?>" alt="Team 2" class="team-logo">
                                    <?php endif; ?>
                                    <span class="team-name"><?php echo htmlspecialchars($match['team2_name']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-matches">
                        <i class="bi bi-calendar-x"></i>
                        <h3>No Matches Today</h3>
                        <p>There are no matches scheduled for today.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="activity-section">
            <div class="section-header">
                <h2><i class="bi bi-activity"></i> Recent Activity</h2>
            </div>
            
            <div class="activity-list">
                <?php if($recent_activity->num_rows > 0): ?>
                    <?php while($activity = $recent_activity->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <img src="../images/games/<?php echo $activity['game_icon']; ?>" alt="<?php echo $activity['game_name']; ?>">
                            </div>
                            <div class="activity-content">
                                <p><?php echo htmlspecialchars($activity['message']); ?></p>
                                <span class="activity-time">
                                    <?php echo timeAgo($activity['created_at']); ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-activity">
                        <i class="bi bi-clock-history"></i>
                        <p>No recent activity</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Tournaments Section -->
        <div class="recent-tournaments">
            <div class="section-header">
                <h2><i class="bi bi-controller"></i> My Recent Games</h2>
                <a href="my_games.php" class="view-all">View All Games <i class="bi bi-arrow-right"></i></a>
            </div>
            
            <div class="tournaments-grid">
                <?php while($tournament = $recent_tournaments->fetch_assoc()): ?>
                    <div class="tournament-card">
                        <div class="tournament-header">
                            <img src="../images/games/<?php echo $tournament['game_icon']; ?>" 
                                 alt="<?php echo htmlspecialchars($tournament['game_name']); ?>" 
                                 class="game-icon">
                            <div class="tournament-info">
                                <h3><?php echo htmlspecialchars($tournament['name']); ?></h3>
                                <span class="game-name"><?php echo htmlspecialchars($tournament['game_name']); ?></span>
                            </div>
                            <div class="tournament-status <?php echo $tournament['status']; ?>">
                                <?php echo ucfirst($tournament['status']); ?>
                            </div>
                        </div>

                        <div class="tournament-details">
                            <div class="detail-item">
                                <i class="bi bi-people"></i>
                                <span><?php echo $tournament['team_count']; ?> Teams</span>
                            </div>
                            <?php if($tournament['status'] === 'completed' && $tournament['winner_name']): ?>
                                <div class="winner-info">
                                    <div class="winner-label">Winner</div>
                                    <div class="winner-team">
                                        <img src="../images/team-logos/<?php echo $tournament['winner_logo'] ?: 'default-team-logo.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($tournament['winner_name']); ?>" 
                                             class="winner-logo">
                                        <span class="winner-name"><?php echo htmlspecialchars($tournament['winner_name']); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tournament-actions">
                            <?php if($tournament['status'] === 'completed'): ?>
                                <a href="view_matches.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-primary">
                                    <i class="bi bi-trophy"></i> View Results
                                </a>
                            <?php else: ?>
                                <a href="matches.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-primary">
                                    <i class="bi bi-controller"></i> Manage Matches
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>

<?php
function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
?> 