<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... other head elements ... -->
    <link href="css/navbar.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-trophy-fill"></i>CSG Tournament
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'admin/dashboard.php') !== false) ? 'active' : ''; ?>" href="admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i>Admin Dashboard
                        </a>
                    <?php else: ?>
                        <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'gamemaster/dashboard.php') !== false) ? 'active' : ''; ?>" href="gamemaster/dashboard.php">
                            <i class="bi bi-controller"></i>Gamemaster Dashboard
                        </a>
                    <?php endif; ?>
                    <a class="nav-link" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i>Logout
                    </a>
                <?php else: ?>
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], 'login.php') !== false) ? 'active' : ''; ?>" href="login.php">
                        <i class="bi bi-box-arrow-in-right"></i>Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav> 