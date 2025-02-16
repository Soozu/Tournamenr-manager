<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

try {
    $id = $_POST['id'] ?? null;
    $username = $_POST['username'];
    $email = $_POST['email'];
    $status = $_POST['status'];
    $password = $_POST['password'];

    if ($id) {
        // Update existing facilitator
        $query = "UPDATE users SET username = ?, email = ?, status = ? WHERE id = ?";
        $params = [$username, $email, $status, $id];
        
        if (!empty($password)) {
            $query = "UPDATE users SET username = ?, email = ?, status = ?, password = ? WHERE id = ?";
            $params = [$username, $email, $status, password_hash($password, PASSWORD_DEFAULT), $id];
        }
    } else {
        // Create new facilitator
        $query = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'facilitator', ?)";
        $params = [$username, $email, password_hash($password, PASSWORD_DEFAULT), $status];
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    // Handle avatar upload if provided
    if (!empty($_FILES['avatar']['name'])) {
        $userId = $id ?? $conn->insert_id;
        $avatar = $_FILES['avatar'];
        $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
        $newName = "avatar_{$userId}.{$ext}";
        $targetPath = "../images/avatars/{$newName}";
        
        move_uploaded_file($avatar['tmp_name'], $targetPath);
        $conn->query("UPDATE users SET avatar = '{$newName}' WHERE id = {$userId}");
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 