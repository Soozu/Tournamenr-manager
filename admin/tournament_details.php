<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Get tournament ID from URL
$tournament_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch tournament details
$tournament_query = "
    SELECT 
        t.*,
        g.name as game_name,
        g.icon as game_icon,
        (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.id) as team_count,
        (SELECT COUNT(*) FROM matches WHERE tournament_id = t.id) as match_count,
        ts.registration_open,
        ts.team_size,
        ts.auto_assign_gm,
        ts.match_duration,
        (SELECT COUNT(*) 
         FROM tournament_game_masters 
         WHERE tournament_id = t.id 
         AND status = 'active') as assigned_gms
    FROM tournaments t
    JOIN games g ON t.game_id = g.id
    LEFT JOIN tournament_settings ts ON t.id = ts.tournament_id
    WHERE t.id = ?";

$stmt = $conn->prepare($tournament_query);
$stmt->bind_param("i", $tournament_id);
$stmt->execute();
$tournament = $stmt->get_result()->fetch_assoc();

if (!$tournament) {
    header('Location: dashboard_admin.php');
    exit();
}

// Set default values for all used fields
$tournament = array_merge([
    'registration_open' => true,
    'team_size' => 5,
    'auto_assign_gm' => false,
    'match_duration' => 60,
    'assigned_gms' => 0,
    'team_count' => 0,
    'match_count' => 0,
    'prize_pool' => 0
], $tournament ?? []);

// Fetch registered teams
$teams_query = "
    SELECT 
        t.*,
        (SELECT COUNT(*) FROM matches m 
         WHERE (m.team1_id = t.id OR m.team2_id = t.id) 
         AND m.tournament_id = ?) as matches_played,
        (SELECT COUNT(*) FROM matches m 
         WHERE (m.team1_id = t.id OR m.team2_id = t.id) 
         AND m.winner_id = t.id 
         AND m.tournament_id = ?) as matches_won
    FROM teams t
    JOIN tournament_teams tt ON t.id = tt.team_id
    WHERE tt.tournament_id = ?
    ORDER BY matches_won DESC";

$stmt = $conn->prepare($teams_query);
$stmt->bind_param("iii", $tournament_id, $tournament_id, $tournament_id);
$stmt->execute();
$teams = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournament Details - <?php echo htmlspecialchars($tournament['name']); ?></title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Add SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/tournament_details.css" rel="stylesheet">

    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Add jQuery (if needed) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="dashboard-body">
    <div class="dashboard-container">
        <!-- Include your sidebar here -->
        
        <div class="main-content">
            <div class="tournament-header">
                <div class="back-button">
                    <a href="tournaments.php">
                        <i class="bi bi-arrow-left"></i>
                        <span>Back to Dashboard</span>
                    </a>
                </div>
                
                <div class="tournament-banner">
                    <div class="game-icon">
                        <img src="../images/games/<?php echo $tournament['game_icon']; ?>" 
                             alt="<?php echo htmlspecialchars($tournament['game_name']); ?>">
                    </div>
                    <div class="tournament-info">
                        <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
                        <div class="tournament-meta">
                            <span class="game-name">
                                <i class="bi bi-controller"></i>
                                <?php echo htmlspecialchars($tournament['game_name']); ?>
                            </span>
                            <span class="date">
                                <i class="bi bi-calendar3"></i>
                                <?php echo date('M d, Y', strtotime($tournament['start_date'])); ?> - 
                                <?php echo date('M d, Y', strtotime($tournament['end_date'])); ?>
                            </span>
                            <span class="status <?php echo $tournament['status']; ?>">
                                <?php echo ucfirst($tournament['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="tournament-actions">
                        <button class="btn btn-primary" onclick="editTournament(<?php echo $tournament_id; ?>)">
                            <i class="bi bi-pencil"></i> Edit Tournament
                        </button>
                        <button class="btn btn-danger" onclick="deleteTournament(<?php echo $tournament_id; ?>)">
                            <i class="bi bi-trash"></i> Delete Tournament
                        </button>
                    </div>
                </div>
            </div>

            <div class="tournament-content">
                <div class="row">
                    <!-- Tournament Overview -->
                    <div class="col-md-4">
                        <div class="admin-card overview-card">
                            <h3>Tournament Overview</h3>
                            <div class="overview-stats">
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-value"><?php echo $tournament['team_count']; ?></div>
                                        <div class="stat-label">Registered Teams</div>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="bi bi-controller"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-value"><?php echo $tournament['match_count']; ?></div>
                                        <div class="stat-label">Total Matches</div>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="bi bi-person-badge"></i>
                                    </div>
                                    <div class="stat-info">
                                        <div class="stat-value"><?php echo $tournament['assigned_gms'] ?? 0; ?></div>
                                        <div class="stat-label">Assigned GMs</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Quick Actions -->
                            <div class="quick-actions">
                                <h3>Quick Actions</h3>
                                <button onclick="assignGameMasters(<?php echo $tournament_id; ?>)" class="btn btn-accent">
                                    <i class="bi bi-person-plus-fill"></i>
                                    Assign Game Masters
                                </button>
                                
                                <button onclick="manageBrackets(<?php echo $tournament_id; ?>)" class="btn btn-warning">
                                    <i class="bi bi-diagram-3-fill"></i>
                                    Manage Brackets
                                </button>
                                
                                <button onclick="viewReports(<?php echo $tournament_id; ?>)" class="btn btn-info">
                                    <i class="bi bi-file-text-fill"></i>
                                    View Reports
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Game Master Management -->
                    <div class="col-md-8">
                        <div class="admin-card gm-card">
                            <div class="card-header-actions">
                                <h3>Game Master Assignments</h3>
                                <button class="btn btn-primary" onclick="addGameMaster(<?php echo $tournament_id; ?>)">
                                    <i class="bi bi-plus-lg"></i> Add Game Master
                                </button>
                            </div>
                            <div class="gm-list">
                                <?php
                                $gm_query = "
                                    SELECT 
                                        u.id, u.username, u.avatar,
                                        COUNT(m.id) as assigned_matches,
                                        SUM(CASE WHEN m.status = 'completed' THEN 1 ELSE 0 END) as completed_matches
                                    FROM users u
                                    LEFT JOIN matches m ON u.id = m.gamemaster_id AND m.tournament_id = ?
                                    WHERE u.role = 'gamemaster'
                                    GROUP BY u.id
                                    ORDER BY assigned_matches DESC";
                                
                                $stmt = $conn->prepare($gm_query);
                                $stmt->bind_param("i", $tournament_id);
                                $stmt->execute();
                                $gamemasters = $stmt->get_result();
                                
                                while($gm = $gamemasters->fetch_assoc()):
                                ?>
                                <div class="gm-item">
                                    <div class="gm-info">
                                        <img src="../images/avatars/<?php echo $gm['avatar'] ?? 'default.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($gm['username']); ?>">
                                        <div class="gm-details">
                                            <h4><?php echo htmlspecialchars($gm['username']); ?></h4>
                                            <div class="gm-stats">
                                                <span><?php echo $gm['assigned_matches']; ?> matches assigned</span>
                                                <span><?php echo $gm['completed_matches']; ?> completed</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="gm-actions">
                                        <button class="btn btn-sm btn-outline-primary" 
                                                onclick="viewGMSchedule(<?php echo $gm['id']; ?>)">
                                            View Schedule
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="removeGM(<?php echo $gm['id']; ?>)">
                                            Remove
                                        </button>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tournament Settings -->
                    <div class="col-12 mt-4">
                        <div class="admin-card settings-card">
                            <h3>Tournament Settings</h3>
                            <div class="settings-grid">
                                <div class="setting-group">
                                    <h4>Registration</h4>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="allowRegistration"
                                               <?php echo $tournament['registration_open'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="allowRegistration">Allow Team Registration</label>
                                    </div>
                                    <div class="form-group mt-3">
                                        <label>Team Size Limit</label>
                                        <input type="number" class="form-control" value="<?php echo $tournament['team_size']; ?>">
                                    </div>
                                </div>
                                
                                <div class="setting-group">
                                    <h4>Match Settings</h4>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="autoAssignGM"
                                               <?php echo $tournament['auto_assign_gm'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="autoAssignGM">Auto-assign Game Masters</label>
                                    </div>
                                    <div class="form-group mt-3">
                                        <label>Match Duration (minutes)</label>
                                        <input type="number" class="form-control" value="<?php echo $tournament['match_duration']; ?>">
                                    </div>
                                </div>
                                
                                <div class="setting-group">
                                    <h4>Prize Pool</h4>
                                    <div class="prize-settings">
                                        <div class="form-group">
                                            <label>Total Prize Pool</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" class="form-control" 
                                                       value="<?php echo $tournament['prize_pool']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="settings-actions mt-4">
                                <button class="btn btn-primary" onclick="saveSettings(<?php echo $tournament_id; ?>)">
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/tournament_details.js"></script>
</body>
</html> 