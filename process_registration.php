<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Add at the top of the file
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'logs/registration.log');
}

// Get form data
$tournament_id = isset($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : 0;
$team_name = trim($_POST['team_name']);
$captain_name = trim($_POST['captain_name']);
$captain_id = trim($_POST['captain_id']);
$member_names = $_POST['member_name'] ?? [];
$member_ids = $_POST['member_id'] ?? [];

// Start transaction
$conn->begin_transaction();

try {
    logError("Starting team registration for tournament: $tournament_id");
    
    // Get tournament and game info
    $tournament_query = "
        SELECT t.*, g.id as game_id, g.team_size 
        FROM tournaments t 
        JOIN games g ON t.game_id = g.id 
        WHERE t.id = ? AND t.registration_open = 1";
    $stmt = $conn->prepare($tournament_query);
    $stmt->bind_param("i", $tournament_id);
    $stmt->execute();
    $tournament = $stmt->get_result()->fetch_assoc();

    if (!$tournament) {
        throw new Exception("Invalid tournament or registration closed.");
    }

    // Handle team logo upload
    $logo_path = 'default-team-logo.png';
    if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'images/team-logos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_info = pathinfo($_FILES['team_logo']['name']);
        $logo_path = uniqid() . '.' . $file_info['extension'];
        $upload_path = $upload_dir . $logo_path;
        
        if (!move_uploaded_file($_FILES['team_logo']['tmp_name'], $upload_path)) {
            throw new Exception("Failed to upload team logo.");
        }
    }

    // Create team
    $team_query = "
        INSERT INTO teams (
            team_name, 
            captain_name, 
            team_logo, 
            member_count,
            created_at
        ) VALUES (?, ?, ?, ?, NOW())";
    $member_count = count($member_ids) + 1; // Including captain
    $stmt = $conn->prepare($team_query);
    $stmt->bind_param("sssi", $team_name, $captain_name, $logo_path, $member_count);
    $stmt->execute();
    $team_id = $conn->insert_id;
    logError("Team created with ID: $team_id");

    // Link team to game
    $team_game_query = "INSERT INTO team_games (team_id, game_id) VALUES (?, ?)";
    $stmt = $conn->prepare($team_game_query);
    $stmt->bind_param("ii", $team_id, $tournament['game_id']);
    $stmt->execute();
    logError("Team linked to game: {$tournament['game_id']}");

    // Register team in tournament
    $register_query = "
        INSERT INTO tournament_teams (
            tournament_id, 
            team_id, 
            status,
            registration_date
        ) VALUES (?, ?, 'pending', NOW())";
    $stmt = $conn->prepare($register_query);
    $stmt->bind_param("ii", $tournament_id, $team_id);
    $stmt->execute();
    logError("Team registered in tournament");

    // Store all members (including captain)
    $all_member_names = array_merge([$captain_name], $member_names);
    $all_member_ids = array_merge([$captain_id], $member_ids);
    
    foreach ($all_member_names as $index => $name) {
        $is_captain = $index === 0;
        $member_id = $all_member_ids[$index];
        
        // Create or update member record
        $member_query = "
            INSERT INTO members (membership_id, full_name, status)
            VALUES (?, ?, 'active')
            ON DUPLICATE KEY UPDATE full_name = VALUES(full_name)";
        $stmt = $conn->prepare($member_query);
        $stmt->bind_param("ss", $member_id, $name);
        $stmt->execute();
        
        $member_db_id = $stmt->insert_id ?: $conn->query("SELECT id FROM members WHERE membership_id = '$member_id'")->fetch_object()->id;
        
        // Link member to team
        $team_member_query = "
            INSERT INTO team_members (team_id, member_id, is_captain)
            VALUES (?, ?, ?)";
        $stmt = $conn->prepare($team_member_query);
        $stmt->bind_param("iii", $team_id, $member_db_id, $is_captain);
        $stmt->execute();
    }
    logError("Members processed: " . count($all_member_ids));

    // Create notification
    $notification_query = "
        INSERT INTO notifications (
            type, 
            reference_id, 
            message, 
            created_at
        ) VALUES ('team_registration', ?, ?, NOW())";
    $message = "New team registration: $team_name for " . $tournament['name'];
    $stmt = $conn->prepare($notification_query);
    $stmt->bind_param("is", $team_id, $message);
    $stmt->execute();

    // Commit transaction
    $conn->commit();
    logError("Registration completed successfully");

    $_SESSION['success'] = [
        'title' => 'Registration Successful!',
        'message' => 'Your team has been registered successfully. Please wait for admin approval.',
        'team_name' => $team_name,
        'tournament' => $tournament['name']
    ];
    header("Location: tournament_details.php?id=$tournament_id");
    exit;

} catch (Exception $e) {
    logError("Error during registration: " . $e->getMessage());
    $conn->rollback();
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    $_SESSION['error'] = [
        'title' => 'Registration Failed',
        'message' => $e->getMessage(),
        'type' => 'error'
    ];
    header("Location: register_team.php?tournament=$tournament_id");
    exit;
} 