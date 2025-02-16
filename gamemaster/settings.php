<?php
session_start();
require_once('../config/database.php');  // Add database connection
require_once('../includes/auth_check.php');
requireGamemaster();

// Get gamemaster settings
$settings_query = $conn->query("
    SELECT 
        u.*,
        COUNT(DISTINCT gg.game_id) as managed_games
    FROM users u
    LEFT JOIN gamemaster_games gg ON u.id = gg.user_id
    WHERE u.id = {$_SESSION['user_id']}
    GROUP BY u.id
");

if (!$settings_query) {
    die("Error fetching settings: " . $conn->error);
}

$settings = $settings_query->fetch_assoc();

if (!$settings) {
    die("Error: User settings not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Game Manager Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/navigation.css" rel="stylesheet">
    <link href="css/settings.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/navigation.php'; ?>
    
    <div class="main-content">
        <div class="settings-header">
            <div class="header-title">
                <h1><i class="bi bi-gear"></i> Settings</h1>
                <p>Manage your preferences and system settings</p>
            </div>
        </div>

        <div class="settings-grid">
            <div class="settings-section notifications">
                <h2><i class="bi bi-bell"></i> Notification Settings</h2>
                <div class="development-banner">
                    <i class="bi bi-tools"></i> Currently in Development
                </div>
                <form id="notifications-form" class="settings-form">
                    <div class="setting-item">
                        <div class="setting-info">
                            <label>Email Notifications</label>
                            <span class="setting-description">Receive updates about tournaments and matches</span>
                        </div>
                        <div class="toggle-switch">
                            <input type="checkbox" id="email_notifications" name="email_notifications" disabled>
                            <label for="email_notifications"></label>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <label>Match Alerts</label>
                            <span class="setting-description">Get notified before scheduled matches</span>
                        </div>
                        <div class="toggle-switch">
                            <input type="checkbox" id="match_alerts" name="match_alerts" disabled>
                            <label for="match_alerts"></label>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <label>Tournament Updates</label>
                            <span class="setting-description">Receive updates about tournament progress</span>
                        </div>
                        <div class="toggle-switch">
                            <input type="checkbox" id="tournament_updates" name="tournament_updates" disabled>
                            <label for="tournament_updates"></label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-save" disabled>
                        <i class="bi bi-check2"></i> Save Notification Settings
                    </button>
                </form>
            </div>

            <div class="settings-section appearance">
                <h2><i class="bi bi-palette"></i> Appearance</h2>
                <div class="development-banner">
                    <i class="bi bi-tools"></i> Currently in Testing
                </div>
                <form id="appearance-form" class="settings-form">
                    <div class="setting-item">
                        <div class="setting-info">
                            <label>Dark Mode</label>
                            <span class="setting-description">Switch between light and dark themes</span>
                        </div>
                        <div class="toggle-switch">
                            <input type="checkbox" id="dark_mode" name="dark_mode" disabled>
                            <label for="dark_mode"></label>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <label>Compact View</label>
                            <span class="setting-description">Display more content in less space</span>
                        </div>
                        <div class="toggle-switch">
                            <input type="checkbox" id="compact_view" name="compact_view" disabled>
                            <label for="compact_view"></label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-save" disabled>
                        <i class="bi bi-check2"></i> Save Appearance Settings
                    </button>
                </form>
            </div>

            <div class="settings-section privacy">
                <h2><i class="bi bi-shield-lock"></i> Privacy & Security</h2>
                <div class="development-banner">
                    <i class="bi bi-tools"></i> Currently in Development
                </div>
                <form id="privacy-form" class="settings-form">
                    <div class="setting-item">
                        <div class="setting-info">
                            <label>Two-Factor Authentication</label>
                            <span class="setting-description">Add an extra layer of security</span>
                        </div>
                        <div class="toggle-switch">
                            <input type="checkbox" id="two_factor" name="two_factor" disabled>
                            <label for="two_factor"></label>
                        </div>
                    </div>
                    
                    <div class="setting-item">
                        <div class="setting-info">
                            <label>Activity Log</label>
                            <span class="setting-description">Track your account activity</span>
                        </div>
                        <button type="button" class="btn-view-log" disabled>
                            <i class="bi bi-clock-history"></i> View Log
                        </button>
                    </div>
                    
                    <button type="submit" class="btn-save" disabled>
                        <i class="bi bi-check2"></i> Save Privacy Settings
                    </button>
                </form>
            </div>

            <div class="settings-section data">
                <h2><i class="bi bi-database"></i> Data Management</h2>
                <div class="development-banner">
                    <i class="bi bi-tools"></i> Currently in Testing
                </div>
                <form id="data-form" class="settings-form">
                    <div class="setting-item">
                        <div class="setting-info">
                            <label>Export Data</label>
                            <span class="setting-description">Download your tournament and match data</span>
                        </div>
                        <button type="button" class="btn-export" disabled>
                            <i class="bi bi-download"></i> Export
                        </button>
                    </div>
                    
                    <div class="setting-item danger-zone">
                        <div class="setting-info">
                            <label class="text-danger">Delete Account</label>
                            <span class="setting-description">Permanently remove your account and data</span>
                        </div>
                        <button type="button" class="btn-danger" disabled>
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
    .development-banner {
        background-color: #fff3cd;
        color: #856404;
        padding: 8px 16px;
        border-radius: 4px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
    }

    .development-banner i {
        font-size: 1.1rem;
    }

    /* Style for disabled buttons and inputs */
    button:disabled,
    input:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .toggle-switch input:disabled + label {
        opacity: 0.6;
        cursor: not-allowed;
    }

    @keyframes pulse {
        0% { opacity: 0.8; }
        50% { opacity: 1; }
        100% { opacity: 0.8; }
    }

    .development-banner {
        animation: pulse 2s infinite;
    }
    </style>
</body>
</html> 