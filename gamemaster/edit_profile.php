<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

// Add this code at the beginning of the file, after the require statements
$upload_dir = 'uploads/avatars';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Get current user data
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        
        // Validate current password
        if (!password_verify($current_password, $user['password'])) {
            throw new Exception("Current password is incorrect");
        }

        // Start transaction
        $conn->begin_transaction();

        // Check if username is taken by another user
        $check_username = "SELECT id FROM users WHERE username = ? AND id != ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param("si", $username, $_SESSION['user_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Username is already taken");
        }

        // Check if email is taken by another user
        $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($check_email);
        $stmt->bind_param("si", $email, $_SESSION['user_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email is already taken");
        }

        // Handle avatar upload
        $avatar_path = $user['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['avatar']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                throw new Exception("Invalid file format. Allowed formats: " . implode(', ', $allowed));
            }

            $new_filename = uniqid() . '.' . $ext;
            $upload_path = $upload_dir . '/' . $new_filename;

            // Add error checking
            if (!is_writable($upload_dir)) {
                throw new Exception("Upload directory is not writable");
            }

            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                throw new Exception("Failed to upload avatar: " . error_get_last()['message']);
            }

            $avatar_path = $new_filename;
        }

        // Update user data
        $update_query = "
            UPDATE users 
            SET username = ?,
                email = ?,
                avatar = ?
            WHERE id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sssi", $username, $email, $avatar_path, $_SESSION['user_id']);
        $stmt->execute();

        $conn->commit();
        $_SESSION['success_message'] = "Profile updated successfully";
        header("Location: profile.php");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Tournament Manager</title>
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
                <h1><i class="bi bi-pencil-square"></i> Edit Profile</h1>
                <p>Update your profile information</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="edit-profile-form">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-section">
                        <div class="avatar-upload">
                            <div class="current-avatar">
                                <img src="<?php echo $user['avatar'] ? 'uploads/avatars/' . $user['avatar'] : 'images/default-avatar.png'; ?>" 
                                     alt="Current Avatar" id="avatar-preview">
                            </div>
                            <div class="avatar-controls">
                                <label for="avatar" class="btn btn-secondary">
                                    <i class="bi bi-camera"></i> Change Avatar
                                </label>
                                <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden-input">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" 
                                   class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   class="form-control" required>
                        </div>

                        <div class="form-group">
                            <label for="current_password">Current Password (required to save changes)</label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-control" required>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Save Changes
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
        // Avatar preview
        document.getElementById('avatar').onchange = function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatar-preview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        };
    </script>
</body>
</html> 