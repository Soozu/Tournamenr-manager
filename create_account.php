<?php
session_start();
require_once 'config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $error = 'All fields are required';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Check if username already exists
        $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_username->bind_param("s", $username);
        $check_username->execute();
        
        if ($check_username->get_result()->num_rows > 0) {
            $error = 'Username already exists';
        } else {
            // Check if email already exists
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            
            if ($check_email->get_result()->num_rows > 0) {
                $error = 'Email already exists';
            } else {
                // Create new account
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'gamemaster';
                $status = 'pending'; // Requires admin approval
                
                $stmt = $conn->prepare("
                    INSERT INTO users (username, email, password, full_name, role, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("ssssss", $username, $email, $hashed_password, $full_name, $role, $status);
                
                if ($stmt->execute()) {
                    $success = 'Account created successfully! Please wait for admin approval.';
                } else {
                    $error = 'Error creating account: ' . $conn->error;
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Gamemaster Account - CSG Tournament</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
    <link href="css/create_account.css" rel="stylesheet">
</head>
<body class="create-account-page">
    <div class="create-account-wrapper">
        <div class="create-account-container">
            <div class="create-account-header">
                <div class="logo-container">
                    <i class="bi bi-controller"></i>
                </div>
                <h1>Create Gamemaster Account</h1>
                <p>Join us to manage tournaments</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-custom">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-custom">
                    <i class="bi bi-check-circle-fill"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="create-account-form">
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
                            <i class="bi bi-envelope-fill"></i>
                        </span>
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="bi bi-person-badge-fill"></i>
                        </span>
                        <input type="text" name="full_name" class="form-control" placeholder="Full Name" required>
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

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-create">
                    <span>Create Account</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="create-account-footer">
                <p>Already have an account?</p>
                <a href="login.php" class="login-link">
                    Login here
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="particles-container" id="particles-js"></div>

    <script src="https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js"></script>
    <script src="js/create_account.js"></script>
</body>
</html> 