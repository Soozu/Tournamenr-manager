<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

try {
    $conn->begin_transaction();

    $gamemaster_id = $_POST['gamemaster_id'];
    $games = $_POST['games'] ?? [];

    // Remove existing assignments
    $delete_stmt = $conn->prepare("DELETE FROM gamemaster_games WHERE user_id = ?");
    $delete_stmt->bind_param("i", $gamemaster_id);
    $delete_stmt->execute();

    // Add new assignments
    if (!empty($games)) {
        $insert_stmt = $conn->prepare("INSERT INTO gamemaster_games (user_id, game_id) VALUES (?, ?)");
        foreach ($games as $game_id) {
            $insert_stmt->bind_param("ii", $gamemaster_id, $game_id);
            $insert_stmt->execute();
        }
    }

    $conn->commit();
    header("Location: assign_games.php?success=Games updated successfully");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    header("Location: assign_games.php?error=" . urlencode($e->getMessage()));
    exit;
} 