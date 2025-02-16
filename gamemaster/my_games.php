<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

// Get tournaments for this gamemaster with status and winner info
$tournaments_query = "
    SELECT 
        t.*,
        g.name as game_name,
        g.icon as game_icon,
        COUNT(DISTINCT tt.team_id) as team_count,
        w.team_name as winner_name,
        w.team_logo as winner_logo,
        w.id as winner_id,
        (SELECT COUNT(*) FROM team_members WHERE team_id = w.id) as winner_member_count
    FROM tournaments t
    INNER JOIN games g ON t.game_id = g.id
    INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id AND tt.status = 'approved'
    LEFT JOIN teams w ON t.winner_id = w.id
    WHERE tgm.user_id = ?
    GROUP BY t.id
    ORDER BY t.created_at DESC";

$stmt = $conn->prepare($tournaments_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$tournaments = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Games - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/my_games.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <div class="header-title">
                <h1><i class="bi bi-trophy"></i> My Tournaments</h1>
                <p>Manage your assigned tournaments</p>
            </div>
        </div>

        <div class="tournaments-grid">
            <?php while($tournament = $tournaments->fetch_assoc()): ?>
                <div class="tournament-card <?php echo $tournament['status']; ?>">
                    <div class="tournament-header">
                        <img src="../images/games/<?php echo $tournament['game_icon']; ?>" alt="<?php echo htmlspecialchars($tournament['game_name']); ?>" class="game-icon">
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
                        <div class="detail-item">
                            <i class="bi bi-diagram-3"></i>
                            <span><?php echo ucfirst($tournament['tournament_type']); ?></span>
                        </div>
                        <?php if($tournament['status'] === 'completed' && $tournament['winner_id']): ?>
                            <div class="winner-section">
                                <div class="winner-header">
                                    <i class="bi bi-trophy-fill"></i>
                                    <span>Tournament Winner</span>
                                </div>
                                <div class="winner-details">
                                    <div class="winner-team">
                                        <img src="../images/team-logos/<?php echo $tournament['winner_logo'] ?: 'default-team-logo.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($tournament['winner_name']); ?>" 
                                             class="winner-logo">
                                        <div class="winner-info">
                                            <h4><?php echo htmlspecialchars($tournament['winner_name']); ?></h4>
                                            <span class="member-count">
                                                <i class="bi bi-people"></i>
                                                <?php echo $tournament['winner_member_count']; ?> Members
                                            </span>
                                        </div>
                                    </div>
                                    <div class="completion-info">
                                        <span class="completion-date">
                                            <i class="bi bi-calendar-check"></i>
                                            Completed on <?php echo date('M d, Y', strtotime($tournament['completed_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tournament-actions">
                        <?php if($tournament['status'] === 'completed'): ?>
                            <a href="view_matches.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-trophy"></i> View Tournament Results
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 