<?php
session_start();
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireGamemaster();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Insert team basic info
        $team_name = $_POST['team_name'];
        $captain_name = $_POST['captain_name'];
        $game_id = $_POST['game_id'];

        // Handle logo upload
        $logo_path = 'default.png'; // Default logo
        if (isset($_FILES['team_logo']) && $_FILES['team_logo']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['team_logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $logo_path = uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['team_logo']['tmp_name'], '../images/teams/' . $logo_path);
            }
        }

        // Insert team
        $stmt = $conn->prepare("INSERT INTO teams (team_name, captain_name, logo) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $team_name, $captain_name, $logo_path);
        $stmt->execute();
        $team_id = $conn->insert_id;

        // Link team to game
        $stmt = $conn->prepare("INSERT INTO team_games (team_id, game_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $team_id, $game_id);
        $stmt->execute();

        $conn->commit();
        header("Location: teams.php?success=Team created successfully");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: teams.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} 