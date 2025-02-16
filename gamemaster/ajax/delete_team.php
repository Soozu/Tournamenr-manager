<?php
session_start();
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireGamemaster();

header('Content-Type: application/json');

$team_id = $_POST['team_id'] ?? 0;

try {
    $conn->begin_transaction();

    // Verify the team belongs to a completed tournament managed by this gamemaster
    $verify_query = "
        SELECT t.id 
        FROM teams tm
        JOIN tournaments t ON tm.tournament_id = t.id
        JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        WHERE tm.id = ? 
        AND tgm.user_id = ?
        AND t.status = 'completed'";
    
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $team_id, $_SESSION['user_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Team not found or not authorized to delete");
    }

    // Delete team members
    $delete_members = "DELETE FROM team_members WHERE team_id = ?";
    $stmt = $conn->prepare($delete_members);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();

    // Delete team games
    $delete_games = "DELETE FROM team_games WHERE team_id = ?";
    $stmt = $conn->prepare($delete_games);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();

    // Delete team from tournament_teams
    $delete_tournament = "DELETE FROM tournament_teams WHERE team_id = ?";
    $stmt = $conn->prepare($delete_tournament);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();

    // Finally delete the team
    $delete_team = "DELETE FROM teams WHERE id = ?";
    $stmt = $conn->prepare($delete_team);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();

    // Create notification
    $notification_query = "
        INSERT INTO notifications 
        (type, reference_id, message, user_id)
        VALUES ('team_deleted', ?, 'Team has been deleted', ?)";
    
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("ii", $team_id, $_SESSION['user_id']);
    $stmt->execute();

    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} 