<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Fetch all scheduled matches with related data
$matches_query = "
    SELECT 
        m.*,
        t.name as tournament_name,
        g.name as game_name,
        g.icon as game_icon,
        t1.team_name as team1_name,
        t2.team_name as team2_name,
        COALESCE(gm.username, 'Not Assigned') as gamemaster_name
    FROM matches m
    JOIN tournaments t ON m.tournament_id = t.id
    JOIN games g ON t.game_id = g.id
    LEFT JOIN teams t1 ON m.team1_id = t1.id
    LEFT JOIN teams t2 ON m.team2_id = t2.id
    LEFT JOIN users gm ON m.gamemaster_id = gm.id
    ORDER BY m.match_date ASC, m.match_time ASC";

// Verify database structure first
$verify_tables_sql = "
-- Tournaments table
CREATE TABLE IF NOT EXISTS `tournaments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `game_id` int(11) NOT NULL,
    `description` text DEFAULT NULL,
    `status` enum('pending','ongoing','completed') NOT NULL DEFAULT 'pending',
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `game_id` (`game_id`),
    CONSTRAINT `tournaments_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Teams table
CREATE TABLE IF NOT EXISTS `teams` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `team_name` varchar(100) NOT NULL,
    `team_logo` varchar(255) DEFAULT 'default_team.png',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Matches table
CREATE TABLE IF NOT EXISTS `matches` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `tournament_id` int(11) NOT NULL,
    `team1_id` int(11) NOT NULL,
    `team2_id` int(11) NOT NULL,
    `gamemaster_id` int(11) DEFAULT NULL,
    `match_date` date NOT NULL,
    `match_time` time NOT NULL,
    `status` enum('pending','ongoing','completed') NOT NULL DEFAULT 'pending',
    `winner_id` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `tournament_id` (`tournament_id`),
    KEY `team1_id` (`team1_id`),
    KEY `team2_id` (`team2_id`),
    KEY `gamemaster_id` (`gamemaster_id`),
    CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`),
    CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`id`),
    CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`id`),
    CONSTRAINT `matches_ibfk_4` FOREIGN KEY (`gamemaster_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Games table
CREATE TABLE IF NOT EXISTS `games` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `icon` varchar(255) DEFAULT 'default.png',
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Execute the table verification/creation
$conn->multi_query($verify_tables_sql);
while ($conn->next_result()) {
    if (!$conn->more_results()) break;
}

// Insert sample data if tables are empty
$sample_data_sql = "
-- Insert sample game if none exists
INSERT INTO games (name, description, status) 
SELECT 'Sample Game', 'Sample game description', 'active' 
WHERE NOT EXISTS (SELECT 1 FROM games LIMIT 1);

-- Insert sample tournament if none exists
INSERT INTO tournaments (name, game_id, status, start_date, end_date) 
SELECT 'Sample Tournament', 
       (SELECT id FROM games LIMIT 1), 
       'ongoing', 
       CURDATE(), 
       DATE_ADD(CURDATE(), INTERVAL 7 DAY)
WHERE NOT EXISTS (SELECT 1 FROM tournaments LIMIT 1);

-- Insert sample teams if none exist
INSERT INTO teams (team_name) 
SELECT 'Team Alpha' WHERE NOT EXISTS (SELECT 1 FROM teams WHERE team_name = 'Team Alpha');
INSERT INTO teams (team_name) 
SELECT 'Team Beta' WHERE NOT EXISTS (SELECT 1 FROM teams WHERE team_name = 'Team Beta');
";

// Execute sample data insertion
$conn->multi_query($sample_data_sql);
while ($conn->next_result()) {
    if (!$conn->more_results()) break;
}

// Insert sample match if none exists
$sample_match_sql = "
INSERT INTO matches (tournament_id, team1_id, team2_id, match_date, match_time, status) 
SELECT 
    (SELECT id FROM tournaments LIMIT 1),
    (SELECT id FROM teams WHERE team_name = 'Team Alpha'),
    (SELECT id FROM teams WHERE team_name = 'Team Beta'),
    CURDATE(),
    CURTIME(),
    'pending'
WHERE NOT EXISTS (SELECT 1 FROM matches LIMIT 1)
";

$conn->query($sample_match_sql);

// Now fetch the matches
$matches = $conn->query($matches_query);

// Fetch available game masters
$gamemasters_query = "SELECT id, username FROM users WHERE role = 'gamemaster' AND status = 'active'";
$gamemasters = $conn->query($gamemasters_query);

// Update tournaments query in the filters section
$tournaments_query = "SELECT id, name FROM tournaments WHERE status != 'completed'";
$tournaments = $conn->query($tournaments_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Matches</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/scheduled_matches.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <div class="header-title">
                    <h1>Scheduled Matches</h1>
                </div>
                <div class="header-actions">
                    <button class="btn btn-success" onclick="exportToExcel()">
                        <i class="bi bi-file-excel"></i> Export to Excel
                    </button>
                    <button class="btn btn-info" onclick="printMatches()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-container">
                <div class="filter-group">
                    <select class="form-select" id="tournamentFilter">
                        <option value="all">All Tournaments</option>
                        <?php
                        $tournaments = $conn->query("SELECT id, name FROM tournaments WHERE status != 'completed'");
                        while($tournament = $tournaments->fetch_assoc()):
                        ?>
                        <option value="<?php echo $tournament['id']; ?>">
                            <?php echo htmlspecialchars($tournament['name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                    <select class="form-select" id="statusFilter">
                        <option value="all">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="completed">Completed</option>
                    </select>
                    <input type="date" class="form-control" id="dateFilter">
                </div>
            </div>

            <!-- Matches List -->
            <div class="matches-container">
                <?php while($match = $matches->fetch_assoc()): ?>
                <div class="match-card" data-tournament="<?php echo $match['tournament_id']; ?>" 
                     data-status="<?php echo $match['status']; ?>"
                     data-date="<?php echo $match['match_date']; ?>">
                    <div class="match-header">
                        <div class="tournament-info">
                            <img src="../images/games/<?php echo $match['game_icon'] ?? 'default.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($match['game_name']); ?>"
                                 class="game-icon">
                            <span><?php echo htmlspecialchars($match['tournament_name']); ?></span>
                        </div>
                        <div class="match-status <?php echo $match['status']; ?>">
                            <?php echo ucfirst($match['status']); ?>
                        </div>
                    </div>

                    <div class="match-content">
                        <div class="match-teams">
                            <div class="team team1">
                                <span class="team-name"><?php echo htmlspecialchars($match['team1_name'] ?? 'TBD'); ?></span>
                            </div>
                            <div class="vs">VS</div>
                            <div class="team team2">
                                <span class="team-name"><?php echo htmlspecialchars($match['team2_name'] ?? 'TBD'); ?></span>
                            </div>
                        </div>

                        <div class="match-details">
                            <div class="detail-item">
                                <i class="bi bi-calendar-event"></i>
                                <span><?php echo date('M d, Y', strtotime($match['match_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="bi bi-clock"></i>
                                <span><?php echo date('h:i A', strtotime($match['match_time'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="bi bi-person-badge"></i>
                                <span>GM: <?php echo htmlspecialchars($match['gamemaster_name']); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="match-actions">
                        <button class="btn btn-primary" onclick="editMatch(<?php echo $match['id']; ?>)">
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <button class="btn btn-outline-primary" onclick="viewDetails(<?php echo $match['id']; ?>)">
                            <i class="bi bi-eye"></i> Details
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>

    <!-- Edit Match Modal -->
    <div class="modal fade" id="matchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Match</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="matchForm">
                        <input type="hidden" id="matchId" name="matchId">
                        <div class="mb-3">
                            <label class="form-label">Game Master</label>
                            <select class="form-select" id="gameMaster" name="gamemaster_id">
                                <option value="">Select Game Master</option>
                                <?php
                                $gamemasters->data_seek(0);
                                while($gm = $gamemasters->fetch_assoc()):
                                ?>
                                <option value="<?php echo $gm['id']; ?>">
                                    <?php echo htmlspecialchars($gm['username']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Match Date</label>
                            <input type="date" class="form-control" id="matchDate" name="match_date">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Match Time</label>
                            <input type="time" class="form-control" id="matchTime" name="match_time">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="matchStatus" name="status">
                                <option value="pending">Pending</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveMatch()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scheduled_matches.js"></script>
</body>
</html> 