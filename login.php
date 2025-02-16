<?php
session_start();
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username = ? AND status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirect based on role
            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard_admin.php");
            } else {
                header("Location: gamemaster/dashboard.php");
            }
            exit();
        } else {
            $error = 'Invalid password';
        }
    } else {
        $error = 'User not found or account inactive';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSG Tournament - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-header">
                <div class="logo-container">
                    <i class="bi bi-controller"></i>
                </div>
                <h1>Welcome Back</h1>
                <p>Login to manage tournaments</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-custom">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <input type="text" name="username" class="form-control" placeholder="Username" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-login">
                    <span>Login</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            
            </form>

            <div class="login-footer">
                <a href="index.php" class="back-link">
                    <i class="bi bi-chevron-left"></i>
                    Back to Homepage
                </a>
            </div>
        </div>
    </div>

    <div class="particles-container" id="particles-js"></div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="js/login.js"></script>
</body>
</html> 