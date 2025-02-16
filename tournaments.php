<?php
require_once('config/database.php');

// Get the selected game category from URL
$selectedGame = isset($_GET['game']) ? $_GET['game'] : null;

// Base query
$query = "
    SELECT t.*, g.name as game_name, g.icon as game_icon, g.platform, g.game_code,
           (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.id) as team_count
    FROM tournaments t
    LEFT JOIN games g ON t.game_id = g.id";

// Add game filter if a specific game is selected
if ($selectedGame) {
    $query .= " WHERE g.game_code = '" . $conn->real_escape_string($selectedGame) . "'";
}

$query .= " ORDER BY t.created_at DESC";
$tournaments = $conn->query($query);

// Separate tournaments into physical and online
$physicalTournaments = [];
$onlineTournaments = [];
while ($tournament = $tournaments->fetch_assoc()) {
    if (isset($tournament['platform']) && $tournament['platform'] === 'Physical') {
        $physicalTournaments[] = $tournament;
    } else {
        $onlineTournaments[] = $tournament;
    }
}

// Get game info if a specific game is selected
$gameInfo = null;
if ($selectedGame) {
    $gameQuery = "SELECT * FROM games WHERE game_code = '" . $conn->real_escape_string($selectedGame) . "'";
    $gameResult = $conn->query($gameQuery);
    $gameInfo = $gameResult->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournaments - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container mt-4">
        <div class="content-header">
            <?php if ($gameInfo): ?>
                <h1>
                    <img src="images/games/<?php echo htmlspecialchars($gameInfo['icon']); ?>" 
                         alt="<?php echo htmlspecialchars($gameInfo['name']); ?>" 
                         class="game-icon-small me-2">
                    <?php echo htmlspecialchars($gameInfo['name']); ?> Tournaments
                </h1>
                <p>Browse and join <?php echo htmlspecialchars($gameInfo['name']); ?> tournaments</p>
            <?php else: ?>
                <h1><i class="bi bi-trophy"></i> All Tournaments</h1>
                <p>Browse and join upcoming tournaments</p>
            <?php endif; ?>
        </div>

        <?php if (!empty($physicalTournaments)): ?>
        <h2>Physical Tournaments</h2>
        <div class="row">
            <?php foreach ($physicalTournaments as $tournament): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="tournament-card">
                        <div class="tournament-header">
                            <?php if($tournament['game_icon']): ?>
                                <img src="images/games/<?php echo htmlspecialchars($tournament['game_icon']); ?>" 
                                     alt="<?php echo htmlspecialchars($tournament['game_name'] ?? 'Game'); ?>" 
                                     class="game-icon">
                            <?php else: ?>
                                <div class="game-icon-placeholder">
                                    <i class="bi bi-controller"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="tournament-info">
                                <h3><?php echo htmlspecialchars($tournament['name']); ?></h3>
                                <span class="game-name">
                                    <?php echo htmlspecialchars($tournament['game_name'] ?? 'Unknown Game'); ?>
                                </span>
                                <span class="status <?php echo $tournament['status']; ?>">
                                    <?php echo ucfirst($tournament['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="tournament-meta">
                            <div class="meta-item">
                                <i class="bi bi-calendar3"></i>
                                <span><?php echo date('M d, Y', strtotime($tournament['start_date'])); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-people-fill"></i>
                                <span><?php echo (int)$tournament['team_count']; ?>/<?php echo (int)$tournament['max_teams']; ?> Teams</span>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-trophy"></i>
                                <span>₱<?php echo number_format((float)$tournament['prize_pool'], 2); ?> Prize Pool</span>
                            </div>
                        </div>

                        <div class="tournament-actions">
                            <a href="tournament_details.php?id=<?php echo $tournament['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-info-circle"></i> View Details
                            </a>
                            
                            <?php if($tournament['status'] === 'ongoing' || $tournament['status'] === 'completed'): ?>
                                <div class="view-buttons">
                                    <a href="bracket.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-info">
                                        <i class="bi bi-diagram-3"></i> View Bracket
                                    </a>
                                    <a href="leaderboard.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-secondary">
                                        <i class="bi bi-trophy"></i> Leaderboard
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($onlineTournaments)): ?>
        <h2>Online Tournaments</h2>
        <div class="row">
            <?php foreach ($onlineTournaments as $tournament): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="tournament-card">
                        <div class="tournament-header">
                            <?php if($tournament['game_icon']): ?>
                                <img src="images/games/<?php echo htmlspecialchars($tournament['game_icon']); ?>" 
                                     alt="<?php echo htmlspecialchars($tournament['game_name'] ?? 'Game'); ?>" 
                                     class="game-icon">
                            <?php else: ?>
                                <div class="game-icon-placeholder">
                                    <i class="bi bi-controller"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="tournament-info">
                                <h3><?php echo htmlspecialchars($tournament['name']); ?></h3>
                                <span class="game-name">
                                    <?php echo htmlspecialchars($tournament['game_name'] ?? 'Unknown Game'); ?>
                                </span>
                                <span class="status <?php echo $tournament['status']; ?>">
                                    <?php echo ucfirst($tournament['status']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="tournament-meta">
                            <div class="meta-item">
                                <i class="bi bi-calendar3"></i>
                                <span><?php echo date('M d, Y', strtotime($tournament['start_date'])); ?></span>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-people-fill"></i>
                                <span><?php echo (int)$tournament['team_count']; ?>/<?php echo (int)$tournament['max_teams']; ?> Teams</span>
                            </div>
                            <div class="meta-item">
                                <i class="bi bi-trophy"></i>
                                <span>₱<?php echo number_format((float)$tournament['prize_pool'], 2); ?> Prize Pool</span>
                            </div>
                        </div>

                        <div class="tournament-actions">
                            <a href="tournament_details.php?id=<?php echo $tournament['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-info-circle"></i> View Details
                            </a>
                            
                            <?php if($tournament['status'] === 'ongoing' || $tournament['status'] === 'completed'): ?>
                                <div class="view-buttons">
                                    <a href="bracket.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-info">
                                        <i class="bi bi-diagram-3"></i> View Bracket
                                    </a>
                                    <a href="leaderboard.php?tournament_id=<?php echo $tournament['id']; ?>" class="btn btn-secondary">
                                        <i class="bi bi-trophy"></i> Leaderboard
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($physicalTournaments) && empty($onlineTournaments)): ?>
        <div class="no-tournaments">
            <i class="bi bi-calendar-x"></i>
            <h3>No Tournaments Found</h3>
            <p>There are currently no tournaments available for this game.</p>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 