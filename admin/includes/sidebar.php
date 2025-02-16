<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo">
            <div class="logo-img">
                <img src="../images/logo.svg" alt="CSG Tournament Manager">
            </div>
            <h2>CSG Tournament Manager</h2>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">MAIN</span>
            <a href="dashboard_admin.php" class="nav-item <?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                <i class="bi bi-grid-1x2-fill"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">TOURNAMENT</span>
            <a href="tournaments.php" class="nav-item <?php echo $current_page == 'tournaments' ? 'active' : ''; ?>">
                <i class="bi bi-trophy-fill"></i>
                <span>Tournaments</span>
            </a>
            <a href="manage_games.php" class="nav-item <?php echo $current_page == 'games' ? 'active' : ''; ?>">
                <i class="bi bi-controller"></i>
                <span>Manage Games</span>
            </a>
            <a href="history_tournament.php" class="nav-item <?php echo $current_page == 'history_tournament' ? 'active' : ''; ?>">
                <i class="bi bi-clock-history"></i>
                <span>Tournament History</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">MANAGEMENT</span>
            <a href="create_gamemaster.php" class="nav-item <?php echo $current_page == 'create_gamemaster' ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i>
                <span>Game Masters</span>
            </a>
            <a href="assign_games.php" class="nav-item <?php echo $current_page == 'assign_games' ? 'active' : ''; ?>">
                <i class="bi bi-joystick"></i>
                <span>Assign Games</span>
            </a>
            <a href="reports.php" class="nav-item <?php echo $current_page == 'reports' ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-text-fill"></i>
                <span>Reports</span>
            </a>
        </div>
    </nav>

    <div class="user-section">
        <div class="user-info">
            <div class="user-avatar">
                <?php if(isset($_SESSION['avatar']) && file_exists("../images/avatars/" . $_SESSION['avatar'])): ?>
                    <img src="../images/avatars/<?php echo $_SESSION['avatar']; ?>" alt="User Avatar">
                <?php else: ?>
                    <div class="avatar-placeholder">
                        <i class="bi bi-person-fill"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                <div class="user-role"><?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'User'; ?></div>
            </div>
        </div>
        <a href="../logout.php" class="logout-btn">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</div> 