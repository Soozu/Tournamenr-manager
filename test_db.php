<?php
require_once 'config/database.php';

try {
    // Test teams table
    $result = $conn->query("SELECT COUNT(*) as count FROM teams");
    $teams_count = $result->fetch_object()->count;
    
    // Test related tables
    $tables = ['team_games', 'tournament_teams', 'members', 'team_members'];
    $status = [];
    
    foreach ($tables as $table) {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        $status[$table] = $result->fetch_object()->count;
    }
    
    echo "Database Status:\n";
    echo "Teams: $teams_count\n";
    foreach ($status as $table => $count) {
        echo "$table: $count\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} 