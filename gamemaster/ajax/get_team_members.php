<?php
session_start();
require_once('../../config/database.php');
require_once('../../includes/auth_check.php');
requireGamemaster();

if (!isset($_GET['team_id'])) {
    echo json_encode(['success' => false, 'message' => 'Team ID is required']);
    exit;
}

$team_id = (int)$_GET['team_id'];

// Verify the team belongs to a tournament managed by this gamemaster
$check_query = "
    SELECT t.id 
    FROM teams t
    INNER JOIN tournament_teams tt ON t.id = tt.team_id
    INNER JOIN tournaments tour ON tt.tournament_id = tour.id
    INNER JOIN tournament_game_masters tgm ON tour.id = tgm.tournament_id
    WHERE t.id = ? AND tgm.user_id = ?
    LIMIT 1";

$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $team_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get team members
$members_query = "
    SELECT 
        m.id,
        m.membership_id,
        m.full_name,
        tm.is_captain
    FROM members m
    INNER JOIN team_members tm ON m.id = tm.member_id
    WHERE tm.team_id = ?
    ORDER BY tm.is_captain DESC, m.full_name ASC";

$stmt = $conn->prepare($members_query);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

echo json_encode(['success' => true, 'members' => $members]); 