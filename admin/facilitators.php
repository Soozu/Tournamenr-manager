<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Fetch facilitators with their stats
$facilitators_query = "
    SELECT 
        u.*,
        COUNT(DISTINCT t.id) as tournament_count,
        COUNT(DISTINCT m.id) as matches_count,
        u.status,
        u.created_at
    FROM users u
    LEFT JOIN tournaments t ON u.id = t.gamemaster_id
    LEFT JOIN matches m ON t.id = m.tournament_id
    WHERE u.role = 'facilitator'
    GROUP BY u.id
    ORDER BY u.created_at DESC";

$facilitators = $conn->query($facilitators_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Facilitators - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/facilitators.css" rel="stylesheet">
    <link href="css/sidebar.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="content-header">
                <div class="header-left">
                    <h1><i class="bi bi-people"></i> Facilitators</h1>
                    <p>Manage tournament facilitators and gamemasters</p>
                </div>
                <button class="btn-create" onclick="showAddFacilitatorModal()">
                    <i class="bi bi-plus-lg"></i>
                    <span>Add Facilitator</span>
                </button>
            </div>

            <div class="search-filter-container">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" placeholder="Search facilitators...">
                </div>
                <div class="filter-box">
                    <select>
                        <option>All Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="facilitators-grid">
                <?php while($facilitator = $facilitators->fetch_assoc()): ?>
                <div class="facilitator-card">
                    <div class="card-header">
                        <div class="facilitator-info">
                            <img src="../images/avatars/<?php echo $facilitator['avatar'] ?: 'default-avatar.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($facilitator['username']); ?>"
                                 class="avatar">
                            <div class="info-text">
                                <h3><?php echo htmlspecialchars($facilitator['username']); ?></h3>
                                <p><?php echo htmlspecialchars($facilitator['email']); ?></p>
                            </div>
                        </div>
                        <span class="status-badge <?php echo $facilitator['status']; ?>">
                            <?php echo ucfirst($facilitator['status']); ?>
                        </span>
                    </div>

                    <div class="card-body">
                        <div class="stats-container">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $facilitator['tournament_count']; ?></div>
                                <div class="stat-label">Tournaments</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $facilitator['matches_count']; ?></div>
                                <div class="stat-label">Matches</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">
                                    <?php echo date('M d, Y', strtotime($facilitator['created_at'])); ?>
                                </div>
                                <div class="stat-label">Joined Date</div>
                            </div>
                        </div>

                        <div class="recent-activity">
                            <h4>Recent Activity</h4>
                            <div class="activity-list">
                                <!-- Add recent activity items here -->
                                <div class="activity-item">
                                    <i class="bi bi-trophy"></i>
                                    <span>Created new tournament</span>
                                    <small>2 hours ago</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-actions">
                        <button class="btn-view" onclick="viewFacilitator(<?php echo $facilitator['id']; ?>)">
                            <i class="bi bi-eye"></i> View Profile
                        </button>
                        <button class="btn-edit" title="Edit" onclick="editFacilitator(<?php echo $facilitator['id']; ?>)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn-delete" title="Delete" onclick="deleteFacilitator(<?php echo $facilitator['id']; ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>

    <!-- Add/Edit Facilitator Modal -->
    <div class="modal fade" id="facilitatorModal" tabindex="-1">
        <!-- Add modal content here -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/facilitators.js"></script>
</body>
</html> 