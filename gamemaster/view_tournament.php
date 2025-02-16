<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

$tournament_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($tournament_id === 0) {
    $_SESSION['error'] = [
        'title' => 'Tournament Selection Required',
        'message' => 'Please select a tournament to manage teams.'
    ];
    header('Location: teams.php');
    exit;
}

// First, verify if the gamemaster has access to this tournament
$access_query = "
    SELECT 
        t.id,
        t.name as tournament_name,
        t.status,
        tgm.user_id
    FROM tournaments t
    INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
    WHERE t.id = ? AND tgm.user_id = ?";
$stmt = $conn->prepare($access_query);
$stmt->bind_param("ii", $tournament_id, $_SESSION['user_id']);
$stmt->execute();
$tournament_access = $stmt->get_result()->fetch_assoc();

if (!$tournament_access) {
    $_SESSION['error'] = [
        'title' => 'Access Denied',
        'message' => 'You do not have permission to manage teams for this tournament.'
    ];
    header('Location: teams.php');
    exit;
}

try {
    // Fetch tournament details
    $tournament_query = "
        SELECT 
            t.*,
            g.name as game_name,
            g.icon as game_icon,
            g.team_size,
            COUNT(DISTINCT tt.team_id) as total_teams
        FROM tournaments t
        JOIN games g ON t.game_id = g.id
        LEFT JOIN tournament_teams tt ON t.id = tt.team_id
        WHERE t.id = ?
        GROUP BY t.id";

    $stmt = $conn->prepare($tournament_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();

    if (!$tournament) {
        error_log("Tournament not found: {$tournament_id}");
        $_SESSION['error'] = [
            'title' => 'Tournament Not Found',
            'message' => 'The requested tournament could not be found.'
        ];
        header('Location: teams.php');
        exit;
    }

    // Fetch registered teams with their status
    $teams_query = "
        SELECT 
            t.*,
            tt.status as registration_status,
            tt.id as registration_id,
            tt.tournament_id,
            COUNT(DISTINCT tm.id) as member_count
        FROM teams t
        INNER JOIN tournament_teams tt ON t.id = tt.team_id
        LEFT JOIN team_members tm ON t.id = tm.team_id
        WHERE tt.tournament_id = ?
        GROUP BY t.id
        ORDER BY tt.status ASC, t.created_at DESC";

    $stmt = $conn->prepare($teams_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $teams = $stmt->get_result();

    // Get status counts
    $status_counts = [
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0
    ];

    $status_query = "
        SELECT status, COUNT(*) as count 
        FROM tournament_teams 
        WHERE tournament_id = ? 
        GROUP BY status";
    $stmt = $conn->prepare($status_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $status_result = $stmt->get_result();
    while ($row = $status_result->fetch_assoc()) {
        $status_counts[$row['status']] = $row['count'];
    }
} catch (Exception $e) {
    error_log("Database error in view_tournament.php: " . $e->getMessage());
    $_SESSION['error'] = [
        'title' => 'Database Error',
        'message' => 'An error occurred while fetching tournament data.'
    ];
    header('Location: teams.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Tournament - <?php echo htmlspecialchars($tournament['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/view_tournament.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <strong><?php echo $_SESSION['error']['title']; ?></strong>
                    <p><?php echo $_SESSION['error']['message']; ?></p>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="tournament-header">
                <div class="header-actions">
                    <a href="teams.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Teams
                    </a>
                </div>
                <div class="tournament-title">
                    <div class="game-badge">
                        <img src="../images/games/<?php echo htmlspecialchars($tournament['game_icon']); ?>" 
                             alt="<?php echo htmlspecialchars($tournament['game_name']); ?>">
                        <span><?php echo htmlspecialchars($tournament['game_name']); ?></span>
                    </div>
                    <h1><?php echo htmlspecialchars($tournament['name']); ?></h1>
                    <div class="tournament-meta">
                        <span class="status-badge <?php echo $tournament['status']; ?>">
                            <?php echo ucfirst($tournament['status']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="content-card">
                        <div class="card-header">
                            <h2>Registered Teams</h2>
                            <div class="header-actions">
                                <select id="statusFilter" class="form-select">
                                    <option value="all">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>

                        <div class="teams-list">
                            <?php while($team = $teams->fetch_assoc()): ?>
                                <div class="team-card">
                                    <div class="team-info">
                                        <div class="team-logo-container">
                                            <?php 
                                            $logo_path = '../images/team-logos/';
                                            $default_logo = 'default-team-logo.png';
                                            $team_logo = $team['team_logo'] ?? $default_logo;
                                            
                                            if (!file_exists($logo_path . $team_logo)) {
                                                $team_logo = $default_logo;
                                            }
                                            ?>
                                            <img src="<?php echo $logo_path . htmlspecialchars($team_logo); ?>" 
                                                 alt="<?php echo htmlspecialchars($team['team_name']); ?>"
                                                 class="team-logo">
                                        </div>
                                        <div class="team-details">
                                            <h3><?php echo htmlspecialchars($team['team_name']); ?></h3>
                                            <div class="team-meta">
                                                <span class="captain-name">
                                                    <i class="bi bi-person-badge"></i>
                                                    <?php echo htmlspecialchars($team['captain_name']); ?>
                                                </span>
                                                <span class="member-count">
                                                    <i class="bi bi-people"></i>
                                                    <?php echo $team['member_count']; ?> members
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="status-display">
                                        <span class="status-badge <?php echo $team['registration_status']; ?>">
                                            <?php echo ucfirst($team['registration_status']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="content-card">
                        <div class="summary-header">
                            <h3>Registration Summary</h3>
                        </div>
                        <div class="status-summary">
                            <div class="status-item">
                                <span class="label">Pending</span>
                                <span class="value pending-count"><?php echo $status_counts['pending'] ?? 0; ?></span>
                            </div>
                            <div class="status-item">
                                <span class="label">Approved</span>
                                <span class="value approved-count"><?php echo $status_counts['approved'] ?? 0; ?></span>
                            </div>
                            <div class="status-item">
                                <span class="label">Rejected</span>
                                <span class="value rejected-count"><?php echo $status_counts['rejected'] ?? 0; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Status filter
        document.getElementById('statusFilter').addEventListener('change', function() {
            const status = this.value;
            const teamCards = document.querySelectorAll('.team-card');
            let counts = {
                pending: 0,
                approved: 0,
                rejected: 0
            };

            teamCards.forEach(card => {
                const statusBadge = card.querySelector('.status-badge');
                const cardStatus = statusBadge.classList[1]; // Get the status class
                
                if (status === 'all' || cardStatus === status) {
                    card.style.display = '';
                    counts[cardStatus]++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Update the counts
            document.querySelector('.pending-count').textContent = counts.pending;
            document.querySelector('.approved-count').textContent = counts.approved;
            document.querySelector('.rejected-count').textContent = counts.rejected;
        });

        // Initial count update
        updateStatusCounts();
    });

    function updateStatusCounts() {
        const teamCards = document.querySelectorAll('.team-card');
        const counts = {
            pending: 0,
            approved: 0,
            rejected: 0
        };

        teamCards.forEach(card => {
            const statusBadge = card.querySelector('.status-badge');
            const status = statusBadge.classList[1];
            if (counts.hasOwnProperty(status)) {
                counts[status]++;
            }
        });

        document.querySelector('.pending-count').textContent = counts.pending;
        document.querySelector('.approved-count').textContent = counts.approved;
        document.querySelector('.rejected-count').textContent = counts.rejected;
    }
    </script>
</body>
</html> 