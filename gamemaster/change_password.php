<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Get current user data
        $user_query = "SELECT password FROM users WHERE id = ?";
        $stmt = $conn->prepare($user_query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Validate current password
        if (!password_verify($current_password, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }

        // Validate new password
        if (strlen($new_password) < 8) {
            throw new Exception("New password must be at least 8 characters long");
        }

        if ($new_password !== $confirm_password) {
            throw new Exception("New passwords do not match");
        }

        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password
        $update_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Password updated successfully";
            header("Location: profile.php");
            exit;
        } else {
            throw new Exception("Failed to update password");
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Tournament Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/profile.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>

    <div class="main-content">
        <div class="profile-container">
            <div class="content-header">
                <h1><i class="bi bi-lock"></i> Change Password</h1>
                <p>Update your account password</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="edit-profile-form">
                <form action="" method="POST" class="password-form">
                    <div class="form-section">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <div class="password-input">
                                <input type="password" id="current_password" name="current_password" 
                                       class="form-control" required>
                                <button type="button" class="toggle-password" data-target="current_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <div class="password-input">
                                <input type="password" id="new_password" name="new_password" 
                                       class="form-control" required minlength="8">
                                <button type="button" class="toggle-password" data-target="new_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <small class="password-hint">Must be at least 8 characters long</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <div class="password-input">
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control" required>
                                <button type="button" class="toggle-password" data-target="confirm_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Update Password
                            </button>
                            <a href="profile.php" class="btn btn-secondary">
                                <i class="bi bi-x-lg"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
    </script>
</body>
</html> 