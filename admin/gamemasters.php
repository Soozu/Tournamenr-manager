<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

try {
    $conn->begin_transaction();

    $id = $_POST['id'] ?? null;
    $username = $_POST['username'];
    $email = $_POST['email'];
    $status = $_POST['status'];
    $password = $_POST['password'];
    $assigned_games = $_POST['games'] ?? []; // Array of game IDs

    if ($id) {
        // Update existing gamemaster
        $query = "UPDATE users SET username = ?, email = ?, status = ? WHERE id = ? AND role = 'gamemaster'";
        $params = [$username, $email, $status, $id];
        
        if (!empty($password)) {
            $query = "UPDATE users SET username = ?, email = ?, status = ?, password = ? WHERE id = ? AND role = 'gamemaster'";
            $params = [$username, $email, $status, password_hash($password, PASSWORD_DEFAULT), $id];
        }
    } else {
        // Create new gamemaster
        $query = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'gamemaster', ?)";
        $params = [$username, $email, password_hash($password, PASSWORD_DEFAULT), $status];
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);

    $gamemaster_id = $id ?? $conn->insert_id;

    // Handle game assignments
    // First, remove all existing game assignments
    $delete_games = $conn->prepare("DELETE FROM gamemaster_games WHERE user_id = ?");
    $delete_games->bind_param("i", $gamemaster_id);
    $delete_games->execute();

    // Then add new game assignments
    if (!empty($assigned_games)) {
        $insert_game = $conn->prepare("INSERT INTO gamemaster_games (user_id, game_id) VALUES (?, ?)");
        foreach ($assigned_games as $game_id) {
            $insert_game->bind_param("ii", $gamemaster_id, $game_id);
            $insert_game->execute();
        }
    }

    // Handle avatar upload if provided
    if (!empty($_FILES['avatar']['name'])) {
        $avatar = $_FILES['avatar'];
        $ext = pathinfo($avatar['name'], PATHINFO_EXTENSION);
        $newName = "avatar_{$gamemaster_id}.{$ext}";
        $targetPath = "../images/avatars/{$newName}";
        
        move_uploaded_file($avatar['tmp_name'], $targetPath);
        
        $update_avatar = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $update_avatar->bind_param("si", $newName, $gamemaster_id);
        $update_avatar->execute();
    }

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 