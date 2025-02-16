-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 28, 2024 at 04:45 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tournament_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `gamemaster_games`
--

CREATE TABLE `gamemaster_games` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gamemaster_games`
--

INSERT INTO `gamemaster_games` (`id`, `user_id`, `game_id`, `created_at`, `assigned_date`) VALUES
(4, 2, 3, '2024-11-27 05:34:24', '2024-11-27 05:34:24'),
(5, 3, 3, '2024-11-28 14:38:34', '2024-11-28 14:38:34'),
(6, 3, 1, '2024-11-28 14:38:34', '2024-11-28 14:38:34'),
(7, 3, 5, '2024-11-28 14:38:34', '2024-11-28 14:38:34');

-- --------------------------------------------------------

--
-- Table structure for table `games`
--

CREATE TABLE `games` (
  `id` int(11) NOT NULL,
  `game_code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) NOT NULL,
  `team_size` varchar(20) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `games`
--

INSERT INTO `games` (`id`, `game_code`, `name`, `icon`, `description`, `color`, `team_size`, `platform`, `status`, `created_at`, `category`) VALUES
(1, 'ml', 'Mobile Legends', 'mobile.png', NULL, '#ff4655', '5v5', 'Mobile', 'active', '2024-11-25 03:56:35', NULL),
(2, 'tekken', 'Tekken 8', 'tekken.png', NULL, '#ffd700', '1v1', 'Console/PC', 'active', '2024-11-25 03:56:35', NULL),
(3, 'codm', 'Call of Duty Mobile', 'codm.png', NULL, '#00ff00', '5v5', 'Mobile', 'active', '2024-11-25 03:56:35', NULL),
(4, 'wildrift', 'Wild Rift', 'wildrift.png', NULL, '#1ca0f2', '5v5', 'Mobile', 'active', '2024-11-25 03:56:35', NULL),
(5, 'valorant', 'Valorant', 'valorant.png', NULL, '#ff4655', '5v5', 'PC', 'active', '2024-11-25 03:56:35', NULL),
(6, 'lol', 'League of Legends', 'lol.png', NULL, '#c9aa71', '5v5', 'PC', 'active', '2024-11-25 03:56:35', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) DEFAULT NULL,
  `team1_id` int(11) DEFAULT NULL,
  `team2_id` int(11) DEFAULT NULL,
  `match_date` date DEFAULT NULL,
  `round_number` int(11) DEFAULT 1,
  `bracket_position` int(11) DEFAULT 0,
  `match_time` time DEFAULT NULL,
  `team1_score` int(11) DEFAULT 0,
  `team2_score` int(11) DEFAULT 0,
  `status` enum('pending','ongoing','completed') DEFAULT 'pending',
  `winner_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gamemaster_id` int(11) DEFAULT NULL,
  `start_time` timestamp NULL DEFAULT NULL,
  `end_time` timestamp NULL DEFAULT NULL,
  `score_team1` int(11) DEFAULT 0,
  `score_team2` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `matches`
--

INSERT INTO `matches` (`id`, `tournament_id`, `team1_id`, `team2_id`, `match_date`, `round_number`, `bracket_position`, `match_time`, `team1_score`, `team2_score`, `status`, `winner_id`, `created_at`, `gamemaster_id`, `start_time`, `end_time`, `score_team1`, `score_team2`) VALUES
(2, 4, 5, 6, '2024-11-26', 1, 0, '21:54:56', 0, 0, 'pending', NULL, '2024-11-26 13:54:56', NULL, NULL, NULL, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `match_reports`
--

CREATE TABLE `match_reports` (
  `id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `gamemaster_id` int(11) NOT NULL,
  `report_type` enum('incident','result','dispute','other') NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','reviewed','resolved') NOT NULL DEFAULT 'pending',
  `attachments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `membership_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `membership_id`, `full_name`, `status`, `created_at`) VALUES
(1, '5', 'qwe', 'active', '2024-11-28 15:44:31'),
(2, '1', 'ewq', 'active', '2024-11-28 15:44:31'),
(3, '2', 'wweqq', 'active', '2024-11-28 15:44:31'),
(4, '3', 'asd', 'active', '2024-11-28 15:44:31'),
(5, '4', 'xcz', 'active', '2024-11-28 15:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `reference_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `reference_id`, `message`, `is_read`, `created_at`) VALUES
(1, 'team_registration', 15, 'New team registration: kingking for CODM Winter Cup', 0, '2024-11-28 15:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `system_alerts`
--

CREATE TABLE `system_alerts` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('warning','critical') NOT NULL DEFAULT 'warning',
  `status` enum('active','resolved') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) DEFAULT NULL,
  `team_name` varchar(255) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `captain_name` varchar(100) DEFAULT NULL,
  `member_count` int(11) DEFAULT 0,
  `team_logo` varchar(255) DEFAULT 'default-team-logo.png',
  `players` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teams`
--

INSERT INTO `teams` (`id`, `tournament_id`, `team_name`, `logo`, `captain_name`, `member_count`, `team_logo`, `players`, `created_at`) VALUES
(5, NULL, 'Team Alpha', NULL, NULL, 0, 'default-team-logo.png', '', '2024-11-26 13:50:48'),
(6, NULL, 'Team Beta', NULL, NULL, 0, 'default-team-logo.png', '', '2024-11-26 13:50:48'),
(7, NULL, 'Team Gamma', NULL, NULL, 0, 'default-team-logo.png', '', '2024-11-26 13:50:48'),
(8, NULL, 'Team Delta', NULL, NULL, 0, 'default-team-logo.png', '', '2024-11-26 13:50:48'),
(9, NULL, 'King', 'default.png', 'pacifico', 0, 'default-team-logo.png', '', '2024-11-27 05:55:55'),
(10, NULL, 'kingking', 'default.png', 'pacifico1', 0, 'default-team-logo.png', '', '2024-11-27 05:56:56'),
(15, NULL, 'kingking', NULL, 'qwe', 5, '67488fdf6ce80.png', '', '2024-11-28 15:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `team_games`
--

CREATE TABLE `team_games` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `game_id` int(11) NOT NULL,
  `joined_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_games`
--

INSERT INTO `team_games` (`id`, `team_id`, `game_id`, `joined_date`) VALUES
(1, 9, 3, '2024-11-27 05:55:55'),
(2, 10, 3, '2024-11-27 05:56:56'),
(7, 15, 3, '2024-11-28 15:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `is_captain` tinyint(1) DEFAULT 0,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `team_id`, `member_id`, `is_captain`, `joined_at`) VALUES
(1, 15, 1, 1, '2024-11-28 15:44:31'),
(2, 15, 2, 0, '2024-11-28 15:44:31'),
(3, 15, 3, 0, '2024-11-28 15:44:31'),
(4, 15, 4, 0, '2024-11-28 15:44:31'),
(5, 15, 5, 0, '2024-11-28 15:44:31');

-- --------------------------------------------------------

--
-- Table structure for table `tournaments`
--

CREATE TABLE `tournaments` (
  `id` int(11) NOT NULL,
  `game_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('registration','ongoing','completed') DEFAULT 'registration',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `max_teams` int(11) NOT NULL DEFAULT 8,
  `prize_pool` decimal(10,2) NOT NULL DEFAULT 0.00,
  `registration_deadline` date DEFAULT NULL,
  `rules` text DEFAULT NULL,
  `registration_open` tinyint(1) DEFAULT 1,
  `team_size` int(11) DEFAULT 5,
  `auto_assign_gm` tinyint(1) DEFAULT 0,
  `match_duration` int(11) DEFAULT 60,
  `gamemaster_id` int(11) DEFAULT NULL,
  `tournament_type` enum('1v1','3v3','5v5') NOT NULL DEFAULT '5v5',
  `winner_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`id`, `game_id`, `name`, `start_date`, `end_date`, `status`, `created_at`, `max_teams`, `prize_pool`, `registration_deadline`, `rules`, `registration_open`, `team_size`, `auto_assign_gm`, `match_duration`, `gamemaster_id`, `tournament_type`, `winner_id`) VALUES
(4, 1, 'Mobile Legends Championship', '2024-12-01', '2024-12-15', 'registration', '2024-11-25 14:12:30', 16, 10000.00, '2024-11-30', NULL, 1, 5, 0, 60, NULL, '5v5', NULL),
(6, 5, 'Valorant Pro League', '2024-12-10', '2024-12-25', 'registration', '2024-11-25 14:12:30', 8, 20000.00, '2024-12-09', NULL, 1, 5, 0, 60, NULL, '5v5', NULL),
(7, 1, 'Mobile Legends Championship', '2024-12-01', '2024-12-15', 'registration', '2024-11-25 14:30:11', 16, 10000.00, '2024-11-30', 'test', 1, 5, 0, 60, NULL, '5v5', NULL),
(8, 2, 'Tekken 8 Tournament', '2024-12-05', '2024-12-20', 'registration', '2024-11-25 14:30:11', 4, 15000.00, '2024-12-04', 'asdasd', 1, 5, 0, 60, NULL, '5v5', NULL),
(9, 3, 'CODM Winter Cup', '2024-12-10', '2024-12-25', 'registration', '2024-11-25 14:30:11', 8, 20000.00, '2024-12-09', 'ahahaha', 1, 5, 0, 60, NULL, '5v5', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tournament_brackets`
--

CREATE TABLE `tournament_brackets` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `round_number` int(11) NOT NULL,
  `match_order` int(11) NOT NULL,
  `team1_id` int(11) DEFAULT NULL,
  `team2_id` int(11) DEFAULT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `status` enum('pending','ongoing','completed') DEFAULT 'pending',
  `match_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_game_masters`
--

CREATE TABLE `tournament_game_masters` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournament_game_masters`
--

INSERT INTO `tournament_game_masters` (`id`, `tournament_id`, `user_id`, `status`, `assigned_at`) VALUES
(1, 4, 2, 'active', '2024-11-26 11:16:52'),
(2, 4, 3, 'active', '2024-11-26 11:16:52'),
(10, 8, 2, 'active', '2024-11-27 03:32:32'),
(14, 7, 3, 'active', '2024-11-27 05:25:16'),
(15, 9, 2, 'active', '2024-11-27 05:34:39');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_matches`
--

CREATE TABLE `tournament_matches` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `team1_id` int(11) NOT NULL,
  `team2_id` int(11) NOT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `match_date` datetime DEFAULT NULL,
  `status` enum('pending','ongoing','completed','cancelled') DEFAULT 'pending',
  `score_team1` int(11) DEFAULT 0,
  `score_team2` int(11) DEFAULT 0,
  `round` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tournament_settings`
--

CREATE TABLE `tournament_settings` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `registration_open` tinyint(1) DEFAULT 1,
  `team_size` int(11) DEFAULT 5,
  `auto_assign_gm` tinyint(1) DEFAULT 0,
  `match_duration` int(11) DEFAULT 60,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournament_settings`
--

INSERT INTO `tournament_settings` (`id`, `tournament_id`, `registration_open`, `team_size`, `auto_assign_gm`, `match_duration`, `created_at`, `updated_at`) VALUES
(1, 4, 1, 5, 0, 60, '2024-11-26 11:16:52', '2024-11-26 11:16:52'),
(2, 7, 1, 5, 0, 60, '2024-11-26 11:16:52', '2024-11-26 11:16:52'),
(3, 8, 1, 5, 0, 60, '2024-11-26 11:16:52', '2024-11-26 11:16:52'),
(6, 9, 1, 5, 0, 60, '2024-11-26 11:16:52', '2024-11-26 11:16:52'),
(7, 6, 1, 5, 0, 60, '2024-11-26 11:16:52', '2024-11-26 11:16:52');

-- --------------------------------------------------------

--
-- Table structure for table `tournament_teams`
--

CREATE TABLE `tournament_teams` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `team_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournament_teams`
--

INSERT INTO `tournament_teams` (`id`, `tournament_id`, `team_id`, `registration_date`, `status`) VALUES
(1, 9, 15, '2024-11-28 15:44:31', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT 'default.png',
  `email_notifications` tinyint(1) DEFAULT 1,
  `match_alerts` tinyint(1) DEFAULT 1,
  `tournament_updates` tinyint(1) DEFAULT 1,
  `dark_mode` tinyint(1) DEFAULT 0,
  `compact_view` tinyint(1) DEFAULT 0,
  `two_factor` tinyint(1) DEFAULT 0,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','gamemaster','user') NOT NULL DEFAULT 'user',
  `status` enum('active','inactive','banned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `email`, `avatar`, `email_notifications`, `match_alerts`, `tournament_updates`, `dark_mode`, `compact_view`, `two_factor`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'admin', NULL, '', 'default.png', 1, 1, 1, 0, 0, 0, '$2a$12$OyE4ZzpLmLo3BqvBjiLboec8wHlKjsDhEtA6E.8pzWYttn0vD3hfa', 'admin', 'active', '2024-11-25 03:56:35'),
(2, 'soozu', NULL, '', 'default.png', 1, 1, 1, 0, 0, 0, '$2a$12$OyE4ZzpLmLo3BqvBjiLboec8wHlKjsDhEtA6E.8pzWYttn0vD3hfa', 'gamemaster', 'active', '2024-11-25 04:34:43'),
(3, 'gamemaster', NULL, '', 'default.png', 1, 1, 1, 0, 0, 0, '$2y$10$r0aCpJaeiW3xyUsm/TTpn.4lM6hd.S0G2PSWCdXpXDkiNKdUbutB.', 'gamemaster', 'active', '2024-11-25 14:45:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `gamemaster_games`
--
ALTER TABLE `gamemaster_games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_gamemaster_game` (`user_id`,`game_id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indexes for table `games`
--
ALTER TABLE `games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `game_code` (`game_code`);

--
-- Indexes for table `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tournament_id` (`tournament_id`),
  ADD KEY `team1_id` (`team1_id`),
  ADD KEY `team2_id` (`team2_id`),
  ADD KEY `matches_gamemaster_fk` (`gamemaster_id`);

--
-- Indexes for table `match_reports`
--
ALTER TABLE `match_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `match_id` (`match_id`),
  ADD KEY `gamemaster_id` (`gamemaster_id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `membership_id` (`membership_id`),
  ADD KEY `idx_membership` (`membership_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `system_alerts`
--
ALTER TABLE `system_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tournament_id` (`tournament_id`);

--
-- Indexes for table `team_games`
--
ALTER TABLE `team_games`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_game` (`team_id`,`game_id`),
  ADD KEY `game_id` (`game_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_member` (`team_id`,`member_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `idx_team_member` (`team_id`,`member_id`,`is_captain`);

--
-- Indexes for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_id` (`game_id`),
  ADD KEY `gamemaster_id` (`gamemaster_id`),
  ADD KEY `winner_id` (`winner_id`);

--
-- Indexes for table `tournament_brackets`
--
ALTER TABLE `tournament_brackets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tournament_id` (`tournament_id`),
  ADD KEY `team1_id` (`team1_id`),
  ADD KEY `team2_id` (`team2_id`),
  ADD KEY `winner_id` (`winner_id`);

--
-- Indexes for table `tournament_game_masters`
--
ALTER TABLE `tournament_game_masters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tournament_id` (`tournament_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tournament_matches`
--
ALTER TABLE `tournament_matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tournament_id` (`tournament_id`),
  ADD KEY `team1_id` (`team1_id`),
  ADD KEY `team2_id` (`team2_id`),
  ADD KEY `winner_id` (`winner_id`);

--
-- Indexes for table `tournament_settings`
--
ALTER TABLE `tournament_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tournament_id` (`tournament_id`);

--
-- Indexes for table `tournament_teams`
--
ALTER TABLE `tournament_teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_tournament_team` (`tournament_id`,`team_id`),
  ADD KEY `team_id` (`team_id`),
  ADD KEY `idx_tournament_team` (`tournament_id`,`team_id`,`status`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `gamemaster_games`
--
ALTER TABLE `gamemaster_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `match_reports`
--
ALTER TABLE `match_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `system_alerts`
--
ALTER TABLE `system_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `team_games`
--
ALTER TABLE `team_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tournament_brackets`
--
ALTER TABLE `tournament_brackets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tournament_game_masters`
--
ALTER TABLE `tournament_game_masters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tournament_matches`
--
ALTER TABLE `tournament_matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tournament_settings`
--
ALTER TABLE `tournament_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tournament_teams`
--
ALTER TABLE `tournament_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `gamemaster_games`
--
ALTER TABLE `gamemaster_games`
  ADD CONSTRAINT `gamemaster_games_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gamemaster_games_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `matches_gamemaster_fk` FOREIGN KEY (`gamemaster_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_ibfk_3` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `matches_ibfk_4` FOREIGN KEY (`gamemaster_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `match_reports`
--
ALTER TABLE `match_reports`
  ADD CONSTRAINT `match_reports_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `match_reports_ibfk_2` FOREIGN KEY (`gamemaster_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_alerts`
--
ALTER TABLE `system_alerts`
  ADD CONSTRAINT `system_alerts_ibfk_1` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_games`
--
ALTER TABLE `team_games`
  ADD CONSTRAINT `team_games_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_games_ibfk_2` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `team_members`
--
ALTER TABLE `team_members`
  ADD CONSTRAINT `team_members_ibfk_1` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `team_members_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournaments`
--
ALTER TABLE `tournaments`
  ADD CONSTRAINT `tournaments_ibfk_1` FOREIGN KEY (`game_id`) REFERENCES `games` (`id`),
  ADD CONSTRAINT `tournaments_ibfk_2` FOREIGN KEY (`gamemaster_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `tournaments_ibfk_3` FOREIGN KEY (`winner_id`) REFERENCES `teams` (`id`);

--
-- Constraints for table `tournament_brackets`
--
ALTER TABLE `tournament_brackets`
  ADD CONSTRAINT `tournament_brackets_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`),
  ADD CONSTRAINT `tournament_brackets_ibfk_2` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `tournament_brackets_ibfk_3` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `tournament_brackets_ibfk_4` FOREIGN KEY (`winner_id`) REFERENCES `teams` (`id`);

--
-- Constraints for table `tournament_game_masters`
--
ALTER TABLE `tournament_game_masters`
  ADD CONSTRAINT `tournament_game_masters_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_game_masters_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_matches`
--
ALTER TABLE `tournament_matches`
  ADD CONSTRAINT `tournament_matches_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_matches_ibfk_2` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `tournament_matches_ibfk_3` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `tournament_matches_ibfk_4` FOREIGN KEY (`winner_id`) REFERENCES `teams` (`id`);

--
-- Constraints for table `tournament_settings`
--
ALTER TABLE `tournament_settings`
  ADD CONSTRAINT `tournament_settings_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tournament_teams`
--
ALTER TABLE `tournament_teams`
  ADD CONSTRAINT `tournament_teams_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_teams_ibfk_2` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
