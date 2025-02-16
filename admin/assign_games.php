<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Fetch all gamemasters with enhanced details
$gamemasters_query = "
    SELECT 
        u.id,
        u.username,
        u.avatar,
        u.status,
        u.created_at,
        GROUP_CONCAT(DISTINCT g.name) as assigned_games,
        COUNT(DISTINCT g.id) as game_count,
        GROUP_CONCAT(DISTINCT t.name) as active_tournaments
    FROM users u
    LEFT JOIN gamemaster_games gg ON u.id = gg.user_id
    LEFT JOIN games g ON gg.game_id = g.id
    LEFT JOIN tournament_game_masters tgm ON u.id = tgm.user_id
    LEFT JOIN tournaments t ON tgm.tournament_id = t.id AND t.status = 'ongoing'
    WHERE u.role = 'gamemaster'
    GROUP BY u.id
    ORDER BY u.username";

$gamemasters = $conn->query($gamemasters_query);

// Fetch all active games with stats
$games_query = "
    SELECT 
        g.*,
        COUNT(DISTINCT gg.user_id) as assigned_gms,
        COUNT(DISTINCT t.id) as active_tournaments
    FROM games g
    LEFT JOIN gamemaster_games gg ON g.id = gg.game_id
    LEFT JOIN tournaments t ON g.id = t.game_id AND t.status = 'ongoing'
    WHERE g.status = 'active'
    GROUP BY g.id
    ORDER BY g.name";
$games = $conn->query($games_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Games - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/sidebar.css" rel="stylesheet">
    <link href="css/assign_games.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php 
        $current_page = 'assign_games';
        include 'includes/sidebar.php'; 
        ?>

        <div class="main-content">
            <div class="page-header">
                <div class="header-wrapper">
                    <h2><i class="bi bi-controller"></i> Assign Games to Gamemasters</h2>
                    <div class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchGamemaster" placeholder="Search gamemaster...">
                    </div>
                </div>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="gamemaster-grid">
                <?php while($gamemaster = $gamemasters->fetch_assoc()): ?>
                    <div class="gamemaster-card" data-gamemaster="<?php echo htmlspecialchars($gamemaster['username']); ?>">
                        <div class="gamemaster-header">
                            <div class="avatar-wrapper">
                                <i class="bi bi-person-circle"></i>
                            </div>
                            <div class="info-wrapper">
                                <h3><?php echo htmlspecialchars($gamemaster['username']); ?></h3>
                                <span class="status-badge">
                                    <?php echo $gamemaster['game_count']; ?> Games Assigned
                                </span>
                            </div>
                        </div>
                        
                        <form class="game-assignment-form" method="POST" action="update_gamemaster_games.php">
                            <input type="hidden" name="gamemaster_id" value="<?php echo $gamemaster['id']; ?>">
                            
                            <div class="games-grid">
                                <?php 
                                $games->data_seek(0);
                                $current_games = $gamemaster['assigned_games'] ? explode(',', $gamemaster['assigned_games']) : [];
                                while($game = $games->fetch_assoc()): 
                                ?>
                                    <label class="game-checkbox">
                                        <input type="checkbox" 
                                               name="games[]" 
                                               value="<?php echo $game['id']; ?>"
                                               <?php echo in_array($game['name'], $current_games) ? 'checked' : ''; ?>>
                                        <span class="game-name"><?php echo htmlspecialchars($game['name']); ?></span>
                                    </label>
                                <?php endwhile; ?>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Update Games
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchGamemaster').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            document.querySelectorAll('.gamemaster-card').forEach(card => {
                const gamemaster = card.dataset.gamemaster.toLowerCase();
                card.style.display = gamemaster.includes(search) ? 'block' : 'none';
            });
        });

        // Alert auto-dismiss
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            });
        }, 5000);
    </script>
</body>
</html> 