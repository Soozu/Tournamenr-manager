<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

if (isset($_GET['id'])) {
    try {
        $conn->begin_transaction();
        
        $game_id = (int)$_GET['id'];
        
        // Check if game is being used in tournaments
        $check_stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM tournaments 
            WHERE game_id = ?
        ");
        $check_stmt->bind_param("i", $game_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            throw new Exception("Cannot delete game: It is being used in tournaments");
        }

        // Get game icon for deletion
        $icon_stmt = $conn->prepare("SELECT icon FROM games WHERE id = ?");
        $icon_stmt->bind_param("i", $game_id);
        $icon_stmt->execute();
        $game = $icon_stmt->get_result()->fetch_assoc();

        // Delete game
        $delete_stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
        $delete_stmt->bind_param("i", $game_id);
        $delete_stmt->execute();

        // Delete game icon file
        if ($game['icon']) {
            $icon_path = "../images/games/" . $game['icon'];
            if (file_exists($icon_path)) {
                unlink($icon_path);
            }
        }

        $conn->commit();
        header("Location: manage_games.php?success=Game deleted successfully");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: manage_games.php?error=" . urlencode($e->getMessage()));
    }
} else {
    header("Location: manage_games.php?error=Invalid request");
} 