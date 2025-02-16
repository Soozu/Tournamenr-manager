<?php
session_start();
require_once 'config/database.php';

$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch tournament details with game information
$tournament_query = "
    SELECT 
        t.*,
        g.name as game_name,
        g.icon as game_icon,
        g.platform,
        g.team_size,
        COUNT(DISTINCT tt.team_id) as registered_teams,
        GROUP_CONCAT(DISTINCT CONCAT(u.username, '|', u.id)) as gamemasters
    FROM tournaments t
    JOIN games g ON t.game_id = g.id
    LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id
    LEFT JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    LEFT JOIN users u ON tgm.user_id = u.id
    WHERE t.id = ?
    GROUP BY t.id";

$stmt = $conn->prepare($tournament_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();

if (!$tournament) {
    header("Location: index.php");
    exit;
}

// Fetch registered teams
$teams_query = "
    SELECT 
        t.*,
        CAST(t.players AS UNSIGNED) as member_count
    FROM teams t
    INNER JOIN tournament_teams tt ON t.id = tt.team_id
    WHERE tt.tournament_id = ?
    ORDER BY t.created_at ASC";

$stmt = $conn->prepare($teams_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$teams = $stmt->get_result();

// Add this near the top where you fetch tournament details
$registered_teams_query = "
    SELECT COUNT(*) as total_registered 
    FROM tournament_teams 
    WHERE tournament_id = ?";
$stmt = $conn->prepare($registered_teams_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$registered_count = $stmt->get_result()->fetch_object()->total_registered;

// Calculate if registration is still possible
$is_full = $registered_count >= $tournament['max_teams'];
$can_register = $tournament['registration_open'] && !$is_full && $tournament['status'] === 'registration';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tournament['name']); ?> - Tournament Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/tournament_details.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <?php if(isset($_SESSION['success'])): ?>
    <div class="alert-overlay">
        <div class="alert-box success">
            <div class="alert-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="alert-content">
                <h4><?php echo $_SESSION['success']['title']; ?></h4>
                <p><?php echo $_SESSION['success']['message']; ?></p>
                <?php if(isset($_SESSION['success']['team_name'])): ?>
                <div class="registration-details">
                    <p><strong>Team:</strong> <?php echo htmlspecialchars($_SESSION['success']['team_name']); ?></p>
                    <p><strong>Tournament:</strong> <?php echo htmlspecialchars($_SESSION['success']['tournament']); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <button class="alert-close" onclick="this.parentElement.parentElement.style.display='none'">×</button>
        </div>
    </div>
    <?php unset($_SESSION['success']); endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert-overlay">
        <div class="alert-box error">
            <div class="alert-icon">
                <i class="bi bi-exclamation-circle-fill"></i>
            </div>
            <div class="alert-content">
                <h4><?php echo $_SESSION['error']['title']; ?></h4>
                <p><?php echo $_SESSION['error']['message']; ?></p>
            </div>
            <button class="alert-close" onclick="this.parentElement.parentElement.style.display='none'">×</button>
        </div>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <div class="tournament-header" style="background-color: <?php echo htmlspecialchars($tournament['color'] ?? '#1a1f2c'); ?>">
        <div class="container">
            <div class="tournament-banner">
                <img src="images/games/<?php echo htmlspecialchars($tournament['game_icon']); ?>" 
                     alt="<?php echo htmlspecialchars($tournament['game_name']); ?>"
                     class="game-icon">
                <div class="tournament-info">
                    <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
                    <div class="tournament-meta">
                        <span class="game-name">
                            <i class="bi bi-controller"></i>
                            <?php echo htmlspecialchars($tournament['game_name']); ?>
                        </span>
                        <span class="platform">
                            <i class="bi bi-<?php echo strtolower($tournament['platform']) === 'mobile' ? 'phone' : 'pc-display'; ?>"></i>
                            <?php echo htmlspecialchars($tournament['platform']); ?>
                        </span>
                        <span class="team-size">
                            <i class="bi bi-people"></i>
                            <?php echo htmlspecialchars($tournament['team_size']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tournament-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <!-- Tournament Details -->
                    <div class="content-card">
                        <h2>Tournament Details</h2>
                        <div class="tournament-details">
                            <div class="detail-item">
                                <i class="bi bi-calendar-event"></i>
                                <div class="detail-content">
                                    <label>Start Date</label>
                                    <span><?php echo date('F d, Y', strtotime($tournament['start_date'])); ?></span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="bi bi-calendar-check"></i>
                                <div class="detail-content">
                                    <label>Registration Deadline</label>
                                    <span><?php echo date('F d, Y', strtotime($tournament['registration_deadline'])); ?></span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <i class="bi bi-trophy"></i>
                                <div class="detail-content">
                                    <label>Prize Pool</label>
                                    <span>₱<?php echo number_format($tournament['prize_pool'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Rules Section -->
                    <div class="content-card">
                        <h2>Tournament Rules</h2>
                        <div class="rules-content">
                            <?php echo $tournament['rules'] ? nl2br(htmlspecialchars($tournament['rules'])) : 'No specific rules provided.'; ?>
                        </div>
                    </div>

                    <!-- Registered Teams -->
                    <div class="content-card">
                        <h2>Registered Teams</h2>
                        <div class="teams-grid">
                            <?php if ($teams->num_rows > 0): ?>
                                <?php while($team = $teams->fetch_assoc()): ?>
                                    <div class="team-card">
                                        <img src="images/team-logos/<?php echo htmlspecialchars($team['team_logo'] ?? 'default-team-logo.png'); ?>" 
                                             alt="<?php echo htmlspecialchars($team['team_name']); ?>"
                                             class="team-logo">
                                        <div class="team-info">
                                            <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                                            <span class="team-captain">
                                                <i class="bi bi-person-badge"></i>
                                                <?php echo htmlspecialchars($team['captain_name'] ?? 'No captain'); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="no-teams">No teams registered yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Tournament Status Card -->
                    <div class="content-card status-card">
                        <div class="status-header">
                            <h3>Tournament Status</h3>
                            <span class="status-badge <?php echo $tournament['status']; ?>">
                                <?php echo ucfirst($tournament['status']); ?>
                            </span>
                        </div>
                        
                        <div class="status-details">
                            <div class="status-item">
                                <label>Start Date</label>
                                <span><?php echo date('M d, Y', strtotime($tournament['start_date'])); ?></span>
                            </div>
                            
                            <div class="status-item">
                                <label>Registration Deadline</label>
                                <span><?php echo date('M d, Y', strtotime($tournament['registration_deadline'])); ?></span>
                            </div>

                            <div class="registration-status">
                                <div class="teams-count">
                                    <span class="current"><?php echo $registered_count; ?></span>
                                    <span class="separator">/</span>
                                    <span class="max"><?php echo $tournament['max_teams']; ?></span>
                                    <span class="label">Teams Registered</span>
                                </div>

                                <div class="registration-progress">
                                    <div class="progress-bar <?php echo $is_full ? 'full' : ''; ?>" 
                                         style="width: <?php echo ($registered_count / $tournament['max_teams']) * 100; ?>%">
                                    </div>
                                </div>

                                <?php if ($is_full): ?>
                                    <div class="status-message full">
                                        <i class="bi bi-exclamation-circle"></i>
                                        Tournament is full
                                    </div>
                                <?php elseif (!$tournament['registration_open']): ?>
                                    <div class="status-message closed">
                                        <i class="bi bi-x-circle"></i>
                                        Registration closed
                                    </div>
                                <?php else: ?>
                                    <div class="status-message open">
                                        <i class="bi bi-check-circle"></i>
                                        Registration open
                                    </div>
                                <?php endif; ?>

                                <a href="register_team.php?tournament=<?php echo $tournament_id; ?>" 
                                   class="btn btn-primary <?php echo !$can_register ? 'disabled' : ''; ?>"
                                   <?php echo !$can_register ? 'disabled' : ''; ?>>
                                    <i class="bi bi-person-plus"></i>
                                    <?php if ($is_full): ?>
                                        Tournament Full
                                    <?php elseif (!$tournament['registration_open']): ?>
                                        Registration Closed
                                    <?php else: ?>
                                        Register Team
                                    <?php endif; ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Game Masters -->
                    <div class="content-card">
                        <h3>Game Masters</h3>
                        <div class="gamemasters-list">
                            <?php 
                            $gamemasters = $tournament['gamemasters'] ? 
                                array_map(function($gm) {
                                    return explode('|', $gm);
                                }, explode(',', $tournament['gamemasters'])) : [];
                            
                            if (!empty($gamemasters)):
                                foreach($gamemasters as $gm):
                            ?>
                                <div class="gamemaster-item">
                                    <i class="bi bi-person-badge"></i>
                                    <span><?php echo htmlspecialchars($gm[0]); ?></span>
                                </div>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <p class="no-gm">No game masters assigned yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 