<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

$tournament_id = $_GET['tournament_id'] ?? 0;

// Fetch tournament details with winner
$tournament_query = "
    SELECT 
        t.*,
        g.name as game_name,
        g.icon as game_icon,
        w.team_name as winner_name,
        w.team_logo as winner_logo,
        (SELECT COUNT(*) FROM team_members WHERE team_id = w.id) as winner_member_count
    FROM tournaments t
    INNER JOIN games g ON t.game_id = g.id
    LEFT JOIN teams w ON t.winner_id = w.id
    WHERE t.id = ? AND t.status = 'completed'";

$stmt = $conn->prepare($tournament_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();

if (!$tournament) {
    header('Location: my_games.php');
    exit;
}

// Fetch all matches
$matches_query = "
    SELECT 
        m.*,
        t1.team_name as team1_name,
        t1.team_logo as team1_logo,
        t2.team_name as team2_name,
        t2.team_logo as team2_logo,
        CASE 
            WHEN m.winner_id = t1.id THEN t1.team_name
            WHEN m.winner_id = t2.id THEN t2.team_name
            ELSE NULL 
        END as winner_name
    FROM tournament_single_elimination m
    LEFT JOIN teams t1 ON m.team1_id = t1.id
    LEFT JOIN teams t2 ON m.team2_id = t2.id
    WHERE m.tournament_id = ?
    ORDER BY m.round_number ASC, m.match_order ASC";

$stmt = $conn->prepare($matches_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$matches = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Matches - <?php echo htmlspecialchars($tournament['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/matches.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <div class="tournament-info">
                <img src="../images/games/<?php echo $tournament['game_icon']; ?>" 
                     alt="<?php echo htmlspecialchars($tournament['game_name']); ?>" 
                     class="game-icon">
                <div class="tournament-details">
                    <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
                    <span class="game-name"><?php echo htmlspecialchars($tournament['game_name']); ?></span>
                </div>
                <div class="tournament-status completed">Completed</div>
            </div>

            <!-- Winner Section -->
            <div class="winner-section">
                <div class="winner-header">
                    <i class="bi bi-trophy-fill"></i>
                    <span>Tournament Champion</span>
                </div>
                <div class="winner-details">
                    <img src="../images/team-logos/<?php echo $tournament['winner_logo'] ?: 'default-team-logo.png'; ?>" 
                         alt="<?php echo htmlspecialchars($tournament['winner_name']); ?>" 
                         class="winner-logo">
                    <div class="winner-info">
                        <h3><?php echo htmlspecialchars($tournament['winner_name']); ?></h3>
                        <span class="member-count">
                            <i class="bi bi-people"></i>
                            <?php echo $tournament['winner_member_count']; ?> Members
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Matches List -->
        <div class="matches-container">
            <?php
            $current_round = 0;
            while ($match = $matches->fetch_assoc()):
                if ($current_round != $match['round_number']):
                    if ($current_round != 0) echo '</div>'; // Close previous round
                    $current_round = $match['round_number'];
                    $round_name = match($match['round_number']) {
                        1 => 'Quarter Finals',
                        2 => 'Semi Finals',
                        3 => 'Finals',
                        default => "Round {$match['round_number']}"
                    };
            ?>
                <div class="round-section">
                    <h2 class="round-title"><?php echo $round_name; ?></h2>
            <?php
                endif;
            ?>
                <div class="match-card">
                    <div class="match-teams">
                        <div class="team <?php echo $match['winner_id'] == $match['team1_id'] ? 'winner' : ''; ?>">
                            <img src="../images/team-logos/<?php echo $match['team1_logo'] ?: 'default-team-logo.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($match['team1_name']); ?>" 
                                 class="team-logo">
                            <span class="team-name"><?php echo htmlspecialchars($match['team1_name']); ?></span>
                            <span class="team-score"><?php echo $match['team1_score']; ?></span>
                        </div>
                        <div class="vs">VS</div>
                        <div class="team <?php echo $match['winner_id'] == $match['team2_id'] ? 'winner' : ''; ?>">
                            <img src="../images/team-logos/<?php echo $match['team2_logo'] ?: 'default-team-logo.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($match['team2_name']); ?>" 
                                 class="team-logo">
                            <span class="team-name"><?php echo htmlspecialchars($match['team2_name']); ?></span>
                            <span class="team-score"><?php echo $match['team2_score']; ?></span>
                        </div>
                    </div>
                    <div class="match-winner">
                        Winner: <?php echo htmlspecialchars($match['winner_name']); ?>
                    </div>
                </div>
            <?php 
            endwhile;
            if ($current_round != 0) echo '</div>'; // Close last round
            ?>
        </div>

        <div class="back-button">
            <a href="my_games.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Tournaments
            </a>
        </div>
    </div>
</body>
</html> 