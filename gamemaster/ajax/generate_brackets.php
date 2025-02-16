<?php
session_start();
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireGamemaster();

// Set JSON header
header('Content-Type: application/json');

// Error handling
function returnError($message) {
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

$tournament_id = $_POST['tournament_id'] ?? 0;

if (!$tournament_id) {
    returnError("Invalid tournament ID");
}

try {
    $conn->begin_transaction();

    // Check if tournament is in active status and belongs to the gamemaster
    $status_check = "
        SELECT t.status 
        FROM tournaments t
        INNER JOIN tournament_game_masters tgm ON t.id = tgm.tournament_id
        WHERE t.id = ? 
        AND tgm.user_id = ?
        AND (t.status = 'active' OR (t.status = 'ongoing' AND t.brackets_generated = 1))";
    
    $stmt = $conn->prepare($status_check);
    $stmt->bind_param("ii", $tournament_id, $_SESSION['user_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Tournament must be in active status or ongoing with generated brackets");
    }

    // Get tournament info and approved teams
    $tournament_query = "
        SELECT t.*, COUNT(tt.team_id) as team_count
        FROM tournaments t
        LEFT JOIN tournament_teams tt ON t.id = tt.tournament_id 
        WHERE t.id = ? AND tt.status = 'approved'
        GROUP BY t.id";
    
    $stmt = $conn->prepare($tournament_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();

    if (!$tournament) {
        throw new Exception("Tournament not found");
    }

    // Get approved teams
    $teams_query = "
        SELECT t.* 
        FROM teams t
        JOIN tournament_teams tt ON t.id = tt.team_id
        WHERE tt.tournament_id = ? AND tt.status = 'approved'
        ORDER BY RAND()";
    
    $stmt = $conn->prepare($teams_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $teams = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $team_count = count($teams);
    if ($team_count < 2) {
        throw new Exception("Not enough teams to generate brackets");
    }

    // Clear existing brackets
    $clear_query = "DELETE FROM tournament_single_elimination WHERE tournament_id = ?";
    $stmt = $conn->prepare($clear_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();

    // Generate brackets based on team count
    switch($team_count) {
        case 4:
            generateFourTeamBracket($conn, $tournament_id, $teams);
            break;
        case 8:
            generateEightTeamBracket($conn, $tournament_id, $teams);
            break;
        case 12:
            generateTwelveTeamBracket($conn, $tournament_id, $teams);
            break;
        default:
            throw new Exception("Unsupported number of teams. Must be 4, 8, or 12 teams.");
    }

    // Update tournament status
    $update_query = "
        UPDATE tournaments 
        SET status = 'ongoing',
            brackets_generated = 1,
            bracket_type = 'single_elimination'
        WHERE id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();

    // Add notification
    $notification_query = "
        INSERT INTO notifications (type, reference_id, message, user_id)
        VALUES ('tournament_brackets_generated', ?, 'Tournament brackets have been generated', ?)";
    
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("ii", $tournament_id, $_SESSION['user_id']);
    $stmt->execute();

    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Brackets generated successfully'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    returnError($e->getMessage());
} catch (Error $e) {
    $conn->rollback();
    returnError("An unexpected error occurred: " . $e->getMessage());
}

// Add these functions after the main try-catch block

function generateFourTeamBracket($conn, $tournament_id, $teams) {
    // Round 1 (Semifinals)
    $stmt = $conn->prepare("
        INSERT INTO tournament_single_elimination 
        (tournament_id, round_number, match_order, team1_id, team2_id, bracket_position, best_of)
        VALUES (?, 1, ?, ?, ?, ?, 3)
    ");
    
    // Generate semifinals (2 matches)
    for ($i = 0; $i < 2; $i++) {
        $team1_id = $teams[$i * 2]['id'];
        $team2_id = $teams[$i * 2 + 1]['id'];
        $stmt->bind_param("iiiii", $tournament_id, $i, $team1_id, $team2_id, $i);
        $stmt->execute();
    }
    
    // Generate finals (1 match)
    $stmt = $conn->prepare("
        INSERT INTO tournament_single_elimination 
        (tournament_id, round_number, match_order, bracket_position, best_of)
        VALUES (?, 2, 0, 0, 3)
    ");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
}

function generateEightTeamBracket($conn, $tournament_id, $teams) {
    // Round 1 (Quarterfinals)
    $stmt = $conn->prepare("
        INSERT INTO tournament_single_elimination 
        (tournament_id, round_number, match_order, team1_id, team2_id, bracket_position, best_of)
        VALUES (?, 1, ?, ?, ?, ?, 3)
    ");
    
    // Generate quarterfinals (4 matches)
    for ($i = 0; $i < 4; $i++) {
        $team1_id = $teams[$i * 2]['id'];
        $team2_id = $teams[$i * 2 + 1]['id'];
        $stmt->bind_param("iiiii", $tournament_id, $i, $team1_id, $team2_id, $i);
        $stmt->execute();
    }
    
    // Generate semifinals (2 matches)
    $stmt = $conn->prepare("
        INSERT INTO tournament_single_elimination 
        (tournament_id, round_number, match_order, bracket_position, best_of)
        VALUES (?, 2, ?, 0, 3)
    ");
    for ($i = 0; $i < 2; $i++) {
        $stmt->bind_param("ii", $tournament_id, $i);
        $stmt->execute();
    }
    
    // Generate finals (1 match)
    $stmt = $conn->prepare("
        INSERT INTO tournament_single_elimination 
        (tournament_id, round_number, match_order, bracket_position, best_of)
        VALUES (?, 3, 0, 0, 3)
    ");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
}

function generateTwelveTeamBracket($conn, $tournament_id, $teams) {
    // Round 1 (4 matches with 4 byes)
    $stmt = $conn->prepare("
        INSERT INTO tournament_single_elimination 
        (tournament_id, round_number, match_order, team1_id, team2_id, bracket_position, best_of)
        VALUES (?, 1, ?, ?, ?, ?, 3)
    ");
    
    // First 8 teams play (4 matches)
    for ($i = 0; $i < 4; $i++) {
        $team1_id = $teams[$i * 2]['id'];
        $team2_id = $teams[$i * 2 + 1]['id'];
        $stmt->bind_param("iiiii", $tournament_id, $i, $team1_id, $team2_id, $i);
        $stmt->execute();
    }
    
    // Round 2 (Quarterfinals - 4 matches)
    $stmt = $conn->prepare("
        INSERT INTO tournament_single_elimination 
        (tournament_id, round_number, match_order, team1_id, bracket_position, best_of, bye_match)
        VALUES (?, 2, ?, ?, ?, 3, 1)
    ");
    
    // Add bye teams to quarterfinals
    for ($i = 0; $i < 4; $i++) {
        $bye_team_id = $teams[8 + $i]['id'];
        $stmt->bind_param("iiii", $tournament_id, $i, $bye_team_id, $i);
        $stmt->execute();
    }
    
    // Generate semifinals (2 matches)
    $stmt = $conn->prepare("
        INSERT INTO tournament_single_elimination 
        (tournament_id, round_number, match_order, bracket_position, best_of)
        VALUES (?, 3, ?, 0, 3)
    ");
    for ($i = 0; $i < 2; $i++) {
        $stmt->bind_param("ii", $tournament_id, $i);
        $stmt->execute();
    }
    
    // Generate finals (1 match)
    $stmt = $conn->prepare("
        INSERT INTO tournament_single_elimination 
        (tournament_id, round_number, match_order, bracket_position, best_of)
        VALUES (?, 4, 0, 0, 3)
    ");
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
} 