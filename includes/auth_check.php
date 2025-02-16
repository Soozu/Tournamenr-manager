<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

function requireAdmin() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ../login.php');
        exit();
    }
}

function requireGamemaster() {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'gamemaster') {
        header('Location: ../login.php');
        exit();
    }
}
?> 