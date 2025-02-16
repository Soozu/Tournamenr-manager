<?php
require_once('../config/database.php');
require_once('../includes/auth_check.php');
requireAdmin();

header('Content-Type: application/json');

try {
    // Validate required fields
    if (empty($_POST['gameId']) || empty($_POST['name']) || empty($_POST['description']) || empty($_POST['status']) || empty($_POST['platform'])) {
        throw new Exception('Required fields are missing');
    }

    $gameId = $_POST['gameId'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $status = $_POST['status'];
    $platform = $_POST['platform'];

    // Start transaction
    $conn->begin_transaction();

    // Check if file is uploaded
    $icon_update = '';
    if (!empty($_FILES['icon']['name'])) {
        $file = $_FILES['icon'];
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileError = $file['error'];

        // Generate unique filename
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = uniqid('game_') . '.' . $fileExt;
        $uploadPath = '../images/games/' . $newFileName;

        // Validate file
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExt, $allowedExtensions)) {
            throw new Exception('Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.');
        }

        if ($fileError !== 0) {
            throw new Exception('Error uploading file.');
        }

        // Move uploaded file
        if (!move_uploaded_file($fileTmpName, $uploadPath)) {
            throw new Exception('Error saving file.');
        }

        $icon_update = ", icon = ?";
    }

    // Update game details
    $query = "UPDATE games SET 
              name = ?, 
              description = ?, 
              status = ? 
              $icon_update
              WHERE id = ?";

    $stmt = $conn->prepare($query);

    // Bind parameters based on whether there's a new icon
    if (!empty($icon_update)) {
        $stmt->bind_param('ssssi', $name, $description, $status, $newFileName, $gameId);
    } else {
        $stmt->bind_param('sssi', $name, $description, $status, $gameId);
    }

    if (!$stmt->execute()) {
        throw new Exception('Error updating game: ' . $stmt->error);
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Game updated successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 