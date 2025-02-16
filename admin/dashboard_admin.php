<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Fetch dashboard statistics
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM tournaments WHERE status = 'registration' AND platform = 'Online') as upcoming_online,
        (SELECT COUNT(*) FROM tournaments WHERE status = 'registration' AND platform = 'Physical') as upcoming_physical,
        (SELECT COUNT(*) FROM tournaments WHERE status = 'ongoing' AND platform = 'Online') as active_online,
        (SELECT COUNT(*) FROM tournaments WHERE status = 'ongoing' AND platform = 'Physical') as active_physical,
        (SELECT COUNT(*) FROM tournaments WHERE status = 'completed' AND platform = 'Online') as completed_online,
        (SELECT COUNT(*) FROM tournaments WHERE status = 'completed' AND platform = 'Physical') as completed_physical,
        (SELECT COUNT(*) FROM users WHERE role = 'gamemaster' AND status = 'active') as active_gamemasters,
        (SELECT COUNT(*) FROM teams) as total_teams,
        (SELECT COUNT(*) FROM matches WHERE status = 'completed') as completed_matches
    FROM dual";
$stats = $conn->query($stats_query)->fetch_assoc();

// Fetch active tournaments with progress
$active_tournaments_query = "
    SELECT 
        t.*,
        g.name as game_name,
        g.icon as game_icon,
        COUNT(DISTINCT tt.team_id) as registered_teams,
        COUNT(DISTINCT m.id) as total_matches,
        COUNT(DISTINCT CASE WHEN m.status = 'completed' THEN m.id END) as completed_matches,
        GROUP_CONCAT(DISTINCT u.username) as gamemasters
    FROM tournaments t
    JOIN games g ON t.game_id = g.id
    LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
    LEFT JOIN matches m ON t.id = m.tournament_id
    LEFT JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    LEFT JOIN users u ON tgm.user_id = u.id
    WHERE t.status IN ('ongoing', 'registration', 'completed')
    GROUP BY t.id
    ORDER BY 
        CASE t.status 
            WHEN 'ongoing' THEN 1
            WHEN 'registration' THEN 2
            WHEN 'completed' THEN 3
        END,
        t.start_date ASC
    LIMIT 8";
$active_tournaments = $conn->query($active_tournaments_query);

// Fetch gamemaster performance
$gamemaster_query = "
    SELECT 
        u.id,
        u.username,
        u.avatar,
        COUNT(DISTINCT m.id) as total_matches,
        COUNT(DISTINCT CASE WHEN m.status = 'completed' THEN m.id END) as completed_matches,
        COUNT(DISTINCT t.id) as active_tournaments,
        GROUP_CONCAT(DISTINCT g.name) as assigned_games
    FROM users u
    LEFT JOIN matches m ON u.id = m.gamemaster_id
    LEFT JOIN tournament_game_masters tgm ON u.id = tgm.user_id
    LEFT JOIN tournaments t ON tgm.tournament_id = t.id AND t.status = 'ongoing'
    LEFT JOIN gamemaster_games gg ON u.id = gg.user_id
    LEFT JOIN games g ON gg.game_id = g.id
    WHERE u.role = 'gamemaster' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY completed_matches DESC
    LIMIT 5";
$gamemasters = $conn->query($gamemaster_query);

// Fetch recent matches
$recent_matches_query = "
    SELECT 
        m.*,
        t.name as tournament_name,
        g.name as game_name,
        g.icon as game_icon,
        t1.team_name as team1_name,
        t2.team_name as team2_name,
        u.username as gamemaster_name
    FROM matches m
    JOIN tournaments t ON m.tournament_id = t.id
    JOIN games g ON t.game_id = g.id
    LEFT JOIN teams t1 ON m.team1_id = t1.id
    LEFT JOIN teams t2 ON m.team2_id = t2.id
    LEFT JOIN users u ON m.gamemaster_id = u.id
    ORDER BY m.match_date DESC, m.match_time DESC
    LIMIT 5";
$recent_matches = $conn->query($recent_matches_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/sidebar.css" rel="stylesheet">
    <link href="css/dashboard_admin.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <div class="header-title">
                    <h1>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['username']); ?></p>
                </div>
                <div class="header-actions">
                    <button class="btn btn-primary" onclick="location.href='create_tournament.php'">
                        <i class="bi bi-plus-lg"></i> Create Tournament
                    </button>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon active">
                        <i class="bi bi-trophy-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo $stats['active_online']; ?></div>
                        <div class="stat-label">Online</div>
                        <div class="stat-number"><?php echo $stats['active_physical']; ?></div>
                        <div class="stat-label">Physical</div>
                        <div class="stat-main-label">Active</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon upcoming">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo $stats['upcoming_online']; ?></div>
                        <div class="stat-label">Online</div>
                        <div class="stat-number"><?php echo $stats['upcoming_physical']; ?></div>
                        <div class="stat-label">Physical</div>
                        <div class="stat-main-label">Registration</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon completed">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo $stats['completed_online']; ?></div>
                        <div class="stat-label">Online</div>
                        <div class="stat-number"><?php echo $stats['completed_physical']; ?></div>
                        <div class="stat-label">Physical</div>
                        <div class="stat-main-label">Completed</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon matches">
                        <i class="bi bi-controller"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-number"><?php echo $stats['completed_matches']; ?></div>
                        <div class="stat-main-label">Matches Played</div>
                    </div>
                </div>
            </div>

            <!-- Main content area -->
            <div class="row">
                <!-- Active Tournaments -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Active Tournaments</h5>
                        </div>
                        <div class="card-body">
                            <?php 
                            // Separate tournaments by platform
                            $online_tournaments = [];
                            $physical_tournaments = [];
                            
                            while($tournament = $active_tournaments->fetch_assoc()) {
                                if($tournament['platform'] == 'Online') {
                                    $online_tournaments[] = $tournament;
                                } else {
                                    $physical_tournaments[] = $tournament;
                                }
                            }
                            
                            if(count($online_tournaments) > 0 || count($physical_tournaments) > 0): ?>
                                <?php if(count($online_tournaments) > 0): ?>
                                    <div class="tournament-category">
                                        <h6 class="category-title">Online Games</h6>
                                        <?php foreach($online_tournaments as $tournament): ?>
                                            <div class="tournament-item <?php echo $tournament['status']; ?>">
                                                <img src="../images/games/<?php echo $tournament['game_icon']; ?>" 
                                                     alt="<?php echo htmlspecialchars($tournament['game_name']); ?>"
                                                     class="game-icon">
                                                <div class="tournament-info">
                                                    <h6><?php echo htmlspecialchars($tournament['name']); ?></h6>
                                                    <div class="tournament-meta">
                                                        <span class="progress-text">
                                                            <?php echo $tournament['completed_matches']; ?>/<?php echo $tournament['total_matches']; ?> matches
                                                        </span>
                                                        <span class="teams-text">
                                                            <?php echo $tournament['registered_teams']; ?> teams registered
                                                        </span>
                                                        <span class="status-badge <?php echo $tournament['status']; ?>">
                                                            <?php echo ucfirst($tournament['status']); ?>
                                                        </span>
                                                        <?php if($tournament['status'] == 'completed' && $tournament['winner_id']): ?>
                                                            <?php 
                                                            // Fetch winner team name
                                                            $winner_query = "SELECT team_name FROM teams WHERE id = " . $tournament['winner_id'];
                                                            $winner_result = $conn->query($winner_query);
                                                            $winner = $winner_result->fetch_assoc();
                                                            if($winner): ?>
                                                                <span class="winner-badge">
                                                                    <i class="bi bi-trophy-fill"></i>
                                                                    Winner: <?php echo htmlspecialchars($winner['team_name']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="progress">
                                                        <?php 
                                                        $progress = $tournament['total_matches'] > 0 ? 
                                                            ($tournament['completed_matches'] / $tournament['total_matches']) * 100 : 0;
                                                        ?>
                                                        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                                    </div>
                                                </div>
                                                <a href="view_tournament.php?id=<?php echo $tournament['id']; ?>" 
                                                   class="btn btn-outline-primary">View</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if(count($physical_tournaments) > 0): ?>
                                    <div class="tournament-category">
                                        <h6 class="category-title">Physical Sports</h6>
                                        <?php foreach($physical_tournaments as $tournament): ?>
                                            <div class="tournament-item <?php echo $tournament['status']; ?>">
                                                <img src="../images/games/<?php echo $tournament['game_icon']; ?>" 
                                                     alt="<?php echo htmlspecialchars($tournament['game_name']); ?>"
                                                     class="game-icon">
                                                <div class="tournament-info">
                                                    <h6><?php echo htmlspecialchars($tournament['name']); ?></h6>
                                                    <div class="tournament-meta">
                                                        <span class="progress-text">
                                                            <?php echo $tournament['completed_matches']; ?>/<?php echo $tournament['total_matches']; ?> matches
                                                        </span>
                                                        <span class="teams-text">
                                                            <?php echo $tournament['registered_teams']; ?> teams registered
                                                        </span>
                                                        <span class="status-badge <?php echo $tournament['status']; ?>">
                                                            <?php echo ucfirst($tournament['status']); ?>
                                                        </span>
                                                        <?php if($tournament['status'] == 'completed' && $tournament['winner_id']): ?>
                                                            <?php 
                                                            // Fetch winner team name
                                                            $winner_query = "SELECT team_name FROM teams WHERE id = " . $tournament['winner_id'];
                                                            $winner_result = $conn->query($winner_query);
                                                            $winner = $winner_result->fetch_assoc();
                                                            if($winner): ?>
                                                                <span class="winner-badge">
                                                                    <i class="bi bi-trophy-fill"></i>
                                                                    Winner: <?php echo htmlspecialchars($winner['team_name']); ?>
                                                                </span>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="progress">
                                                        <?php 
                                                        $progress = $tournament['total_matches'] > 0 ? 
                                                            ($tournament['completed_matches'] / $tournament['total_matches']) * 100 : 0;
                                                        ?>
                                                        <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                                    </div>
                                                </div>
                                                <a href="view_tournament.php?id=<?php echo $tournament['id']; ?>" 
                                                   class="btn btn-outline-primary">View</a>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="no-data">No active tournaments</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Game Master Performance -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Game Master Performance</h5>
                        </div>
                        <div class="card-body">
                            <?php if($gamemasters->num_rows > 0): ?>
                                <?php while($gm = $gamemasters->fetch_assoc()): ?>
                                    <div class="gm-performance-item">
                                        <div class="gm-info">
                                            <div class="gm-avatar">
                                                <?php if($gm['avatar'] && file_exists("../images/avatars/" . $gm['avatar'])): ?>
                                                    <img src="../images/avatars/<?php echo $gm['avatar']; ?>" 
                                                         alt="<?php echo htmlspecialchars($gm['username']); ?>">
                                                <?php else: ?>
                                                    <div class="avatar-placeholder">
                                                        <i class="bi bi-person-fill"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="gm-details">
                                                <h6><?php echo htmlspecialchars($gm['username']); ?></h6>
                                                <span class="gm-stats">
                                                    <?php echo $gm['completed_matches']; ?> matches completed
                                                </span>
                                            </div>
                                        </div>
                                        <div class="progress">
                                            <?php 
                                            $completion = $gm['total_matches'] > 0 ? 
                                                ($gm['completed_matches'] / $gm['total_matches']) * 100 : 0;
                                            ?>
                                            <div class="progress-bar" style="width: <?php echo $completion; ?>%"></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="no-data">No game masters found</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Matches -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Matches</h5>
                        </div>
                        <div class="card-body">
                            <?php if($recent_matches->num_rows > 0): ?>
                                <div class="matches-timeline">
                                    <?php while($match = $recent_matches->fetch_assoc()): ?>
                                        <div class="match-item">
                                            <div class="match-time">
                                                <?php echo date('H:i', strtotime($match['match_time'] ?? date('H:i'))); ?>
                                            </div>
                                            <div class="match-details">
                                                <div class="match-teams">
                                                    <span class="team"><?php echo htmlspecialchars($match['team1_name'] ?? 'TBD'); ?></span>
                                                    <span class="vs">VS</span>
                                                    <span class="team"><?php echo htmlspecialchars($match['team2_name'] ?? 'TBD'); ?></span>
                                                </div>
                                                <div class="match-meta">
                                                    <span class="tournament">
                                                        <?php echo htmlspecialchars($match['tournament_name'] ?? 'Unknown Tournament'); ?>
                                                    </span>
                                                    <span class="gamemaster">
                                                        GM: <?php echo $match['gamemaster_name'] ? htmlspecialchars($match['gamemaster_name']) : 'Not Assigned'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="match-status <?php echo htmlspecialchars($match['status'] ?? 'pending'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($match['status'] ?? 'pending')); ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="no-data">No recent matches</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 