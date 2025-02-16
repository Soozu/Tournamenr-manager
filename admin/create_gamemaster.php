<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

// Fetch all gamemasters with enhanced details
$gamemasters_query = "
    SELECT 
        u.id,
        u.username,
        u.email,
        u.full_name,
        u.avatar,
        u.status,
        u.created_at,
        COUNT(DISTINCT gg.game_id) as game_count,
        GROUP_CONCAT(DISTINCT g.name) as assigned_games,
        COUNT(DISTINCT t.id) as active_tournaments
    FROM users u
    LEFT JOIN gamemaster_games gg ON u.id = gg.user_id
    LEFT JOIN games g ON gg.game_id = g.id
    LEFT JOIN tournament_game_masters tgm ON u.id = tgm.user_id
    LEFT JOIN tournaments t ON tgm.tournament_id = t.id AND t.status = 'ongoing'
    WHERE u.role = 'gamemaster'
    GROUP BY u.id
    ORDER BY u.created_at DESC";

$gamemasters = $conn->query($gamemasters_query);

// Handle form submission for new gamemaster
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Hash password
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Insert new gamemaster
        $stmt = $conn->prepare("
            INSERT INTO users (
                username,
                email,
                full_name,
                password,
                role,
                status,
                avatar
            ) VALUES (?, ?, ?, ?, 'gamemaster', 'active', 'default.png')
        ");

        $stmt->bind_param(
            "ssss",
            $_POST['username'],
            $_POST['email'],
            $_POST['full_name'],
            $password
        );

        $stmt->execute();
        $conn->commit();
        
        header("Location: create_gamemaster.php?success=Game Master created successfully");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error creating game master: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Masters - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/sidebar.css" rel="stylesheet">
    <link href="css/gamemaster.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php 
        $current_page = 'create_gamemaster';
        include 'includes/sidebar.php'; 
        ?>

        <div class="main-content">
            <div class="page-header">
                <h2><i class="bi bi-people-fill"></i> Game Masters</h2>
                <button class="btn-create" data-bs-toggle="modal" data-bs-target="#createGMModal">
                    <i class="bi bi-plus-lg"></i> Add Game Master
                </button>
            </div>

            <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($_GET['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="gamemaster-grid">
                <?php while($gm = $gamemasters->fetch_assoc()): ?>
                    <div class="gamemaster-card">
                        <div class="gm-header">
                            <div class="avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="gm-info">
                                <h3><?php echo htmlspecialchars($gm['username']); ?></h3>
                                <span class="email"><?php echo htmlspecialchars($gm['email']); ?></span>
                                <span class="status-badge <?php echo $gm['status']; ?>">
                                    <?php echo ucfirst($gm['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="gm-stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $gm['game_count']; ?></div>
                                <div class="stat-label">Games Assigned</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $gm['active_tournaments']; ?></div>
                                <div class="stat-label">Active Tournaments</div>
                            </div>
                        </div>

                        <div class="assigned-games">
                            <small class="text-secondary">Assigned Games:</small>
                            <p><?php echo $gm['assigned_games'] ? htmlspecialchars($gm['assigned_games']) : 'No games assigned'; ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Create Game Master Modal -->
    <div class="modal fade" id="createGMModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Game Master</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Game Master</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 