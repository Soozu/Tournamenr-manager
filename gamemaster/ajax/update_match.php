<?php
session_start();
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireGamemaster();

header('Content-Type: application/json');

function returnError($message) {
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

$match_id = $_POST['match_id'] ?? 0;
$team1_score = $_POST['team1_score'] ?? 0;
$team2_score = $_POST['team2_score'] ?? 0;

try {
    $conn->begin_transaction();

    // Verify match belongs to gamemaster's tournament
    $verify_query = "
        SELECT m.*, m.tournament_id
        FROM tournament_single_elimination m
        INNER JOIN tournament_game_masters tgm ON m.tournament_id = tgm.tournament_id
        WHERE m.id = ? 
        AND tgm.user_id = ? 
        AND m.status != 'completed'";
    
    $stmt = $conn->prepare($verify_query);
    $stmt->bind_param("ii", $match_id, $_SESSION['user_id']);
    $stmt->execute();
    $match = $stmt->get_result()->fetch_assoc();

    if (!$match) {
        throw new Exception("Match not found or already completed");
    }

    // Update match scores
    $winner_id = null;
    if ($team1_score > $team2_score) {
        $winner_id = $match['team1_id'];
    } elseif ($team2_score > $team1_score) {
        $winner_id = $match['team2_id'];
    }

    $update_query = "
        UPDATE tournament_single_elimination 
        SET team1_score = ?,
            team2_score = ?,
            winner_id = ?,
            status = 'completed'
        WHERE id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("iiii", $team1_score, $team2_score, $winner_id, $match_id);
    $stmt->execute();

    // If there's a winner, update next round's match
    if ($winner_id) {
        $next_match_query = "
            SELECT id, team1_id, team2_id 
            FROM tournament_single_elimination
            WHERE tournament_id = ? 
            AND round_number = ?
            AND match_order = FLOOR(?/2)";
        
        $next_round = $match['round_number'] + 1;
        $stmt = $conn->prepare($next_match_query);
        $stmt->bind_param("iii", $match['tournament_id'], $next_round, $match['match_order']);
        $stmt->execute();
        $next_match = $stmt->get_result()->fetch_assoc();

        if ($next_match) {
            $update_next_query = "
                UPDATE tournament_single_elimination
                SET " . ($match['match_order'] % 2 == 0 ? "team1_id" : "team2_id") . " = ?
                WHERE id = ?";
            
            $stmt = $conn->prepare($update_next_query);
            $stmt->bind_param("ii", $winner_id, $next_match['id']);
            $stmt->execute();
        }

        // Check if this was the final match
        $check_finals_query = "
            SELECT COUNT(*) as remaining_matches
            FROM tournament_single_elimination
            WHERE tournament_id = ?
            AND status != 'completed'";
        
        $stmt = $conn->prepare($check_finals_query);
        $stmt->bind_param("i", $match['tournament_id']);
        $stmt->execute();
        $remaining = $stmt->get_result()->fetch_assoc();

        // If no remaining matches, update tournament status and winner
        if ($remaining['remaining_matches'] == 0) {
            // Get the winner of the final match
            $final_match_query = "
                SELECT winner_id 
                FROM tournament_single_elimination
                WHERE tournament_id = ?
                AND round_number = (
                    SELECT MAX(round_number) 
                    FROM tournament_single_elimination 
                    WHERE tournament_id = ?
                )";
            
            $stmt = $conn->prepare($final_match_query);
            $stmt->bind_param("ii", $match['tournament_id'], $match['tournament_id']);
            $stmt->execute();
            $final_match = $stmt->get_result()->fetch_assoc();

            if ($final_match && $final_match['winner_id']) {
                // Update tournament status and winner
                $update_tournament_query = "
                    UPDATE tournaments 
                    SET status = 'completed',
                        winner_id = ?,
                        completed_at = CURRENT_TIMESTAMP
                    WHERE id = ?";
                
                $stmt = $conn->prepare($update_tournament_query);
                $stmt->bind_param("ii", $final_match['winner_id'], $match['tournament_id']);
                $stmt->execute();

                // Add tournament completion notification
                $notification_query = "
                    INSERT INTO notifications (type, reference_id, message, user_id)
                    VALUES ('tournament_completed', ?, 'Tournament has been completed', ?)";
                
                $stmt = $conn->prepare($notification_query);
                $stmt->bind_param("ii", $match['tournament_id'], $_SESSION['user_id']);
                $stmt->execute();
            }
        }
    }

    // Add notification
    $notification_query = "
        INSERT INTO notifications (type, reference_id, message, user_id)
        VALUES ('match_update', ?, ?, ?)";
    
    $message = "Match updated: Score " . $team1_score . "-" . $team2_score;
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("isi", $match_id, $message, $_SESSION['user_id']);
    $stmt->execute();

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Match updated successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    returnError($e->getMessage());
} 