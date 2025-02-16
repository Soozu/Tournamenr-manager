<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Fetch all game masters with their detailed stats
$gamemasters_query = "
    SELECT 
        u.*,
        COUNT(DISTINCT m.id) as total_matches,
        COUNT(DISTINCT CASE WHEN m.status = 'completed' THEN m.id END) as completed_matches,
        COUNT(DISTINCT CASE WHEN m.status = 'pending' THEN m.id END) as pending_matches,
        COUNT(DISTINCT CASE WHEN m.status = 'ongoing' THEN m.id END) as ongoing_matches,
        MAX(m.match_date) as last_active,
        COALESCE((SELECT COUNT(*) FROM match_reports mr 
         JOIN matches m2 ON mr.match_id = m2.id 
         WHERE m2.gamemaster_id = u.id), 0) as total_reports
    FROM users u
    LEFT JOIN matches m ON u.id = m.gamemaster_id
    WHERE u.role = 'gamemaster'
    GROUP BY u.id
    ORDER BY u.username ASC";

$gamemasters = $conn->query($gamemasters_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Masters Activity Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <link href="../css/game_masters.css" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <div class="header-title">
                    <h1>Game Masters Activity Monitor</h1>
                </div>
                <div class="header-actions">
                    <div class="filter-group">
                        <select id="activityFilter" class="form-select">
                            <option value="all">All Activities</option>
                            <option value="active">Currently Active</option>
                            <option value="inactive">Inactive (>7 days)</option>
                        </select>
                        <input type="text" id="searchGM" class="form-control" placeholder="Search game masters...">
                    </div>
                </div>
            </div>

            <div class="gm-container">
                <?php while($gm = $gamemasters->fetch_assoc()): 
                    $last_active = $gm['last_active'] ? new DateTime($gm['last_active']) : null;
                    $inactive_days = $last_active ? $last_active->diff(new DateTime())->days : null;
                    $activity_status = $inactive_days > 7 ? 'inactive' : 'active';
                ?>
                <div class="gm-card" data-activity="<?php echo $activity_status; ?>">
                    <div class="gm-header">
                        <img src="../images/avatars/<?php echo $gm['avatar'] ?? 'default.png'; ?>" alt="Avatar" class="gm-avatar">
                        <div class="gm-status <?php echo $gm['status']; ?>">
                            <?php echo ucfirst($gm['status']); ?>
                        </div>
                    </div>
                    
                    <div class="gm-content">
                        <h3><?php echo htmlspecialchars($gm['username']); ?></h3>
                        
                        <div class="gm-info">
                            <div class="info-item">
                                <i class="bi bi-envelope"></i>
                                <span><?php echo htmlspecialchars($gm['email']); ?></span>
                            </div>
                            <div class="info-item">
                                <i class="bi bi-clock"></i>
                                <span>Last Active: <?php echo $last_active ? $last_active->format('Y-m-d H:i') : 'Never'; ?></span>
                            </div>
                        </div>

                        <div class="gm-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $gm['total_matches']; ?></div>
                                <div class="stat-label">Total Matches</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $gm['completed_matches']; ?></div>
                                <div class="stat-label">Completed</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $gm['ongoing_matches']; ?></div>
                                <div class="stat-label">Ongoing</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $gm['pending_matches']; ?></div>
                                <div class="stat-label">Pending</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $gm['total_reports']; ?></div>
                                <div class="stat-label">Reports Filed</div>
                            </div>
                        </div>

                        <div class="gm-actions">
                            <button type="button" class="btn btn-primary" onclick="viewActivity(<?php echo $gm['id']; ?>)">
                                <i class="bi bi-activity"></i> View Activity
                            </button>
                            <button type="button" class="btn btn-info" onclick="viewReports(<?php echo $gm['id']; ?>)">
                                <i class="bi bi-file-text"></i> View Reports
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/game_masters.js"></script>
</body>
</html> 