-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 18, 2025 at 05:10 AM
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
(5, 3, 3, '2024-11-28 14:38:34', '2024-11-28 14:38:34'),
(6, 3, 1, '2024-11-28 14:38:34', '2024-11-28 14:38:34'),
(7, 3, 5, '2024-11-28 14:38:34', '2024-11-28 14:38:34'),
(11, 2, 3, '2024-12-02 09:23:02', '2024-12-02 09:23:02'),
(12, 2, 2, '2024-12-02 09:23:02', '2024-12-02 09:23:02'),
(13, 2, 5, '2024-12-02 09:23:02', '2024-12-02 09:23:02');

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
(6, 'lol', 'League of Legends', 'lol.png', NULL, '#c9aa71', '5v5', 'PC', 'active', '2024-11-25 03:56:35', NULL),
(9, 'soccer', 'Soccer', 'soccer.png', 'A team sport played between two teams of eleven players with a spherical ball.', '#ff4655', '11v11', 'Physical', 'active', '2025-01-18 03:28:00', 'Physical Sports'),
(10, 'basketball', 'Basketball', 'basketball.png', 'A team sport in which two teams, most commonly of five players each, oppose each other on a rectangular court.', '#ffd700', '5v5', 'Physical', 'active', '2025-01-18 03:28:00', 'Physical Sports'),
(11, 'volleyball', 'Volleyball', 'volleyball.png', 'A team sport in which two teams of six players are separated by a net.', '#00ff00', '6v6', 'Physical', 'active', '2025-01-18 03:28:00', 'Physical Sports'),
(12, 'table_tennis', 'Table Tennis', 'table_tennis.png', 'A sport in which two or four players hit a lightweight ball, also known as a ping-pong ball, back and forth across a table.', '#1ca0f2', '1v1', 'Physical', 'active', '2025-01-18 03:28:00', 'Physical Sports'),
(13, '', 'TEST', '678b295c13173.jpg', 'asdasdasd', '', '', '', 'active', '2025-01-18 04:09:00', NULL);

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
(5, '4', 'xcz', 'active', '2024-11-28 15:44:31'),
(6, '66', 'qwe', 'active', '2024-11-28 15:50:01'),
(7, '7', 'ewq', 'active', '2024-11-28 15:50:01'),
(8, '8', 'qwe', 'active', '2024-11-28 15:50:01'),
(9, '9', 'ewq', 'active', '2024-11-28 15:50:01'),
(10, '10', 'qwe', 'active', '2024-11-28 15:50:01'),
(11, '11', 'qa', 'active', '2024-11-28 15:50:28'),
(12, '12', 'ws', 'active', '2024-11-28 15:50:28'),
(13, '13', 'ed', 'active', '2024-11-28 15:50:28'),
(14, '14', 'rf', 'active', '2024-11-28 15:50:28'),
(15, '15', 'tg', 'active', '2024-11-28 15:50:28'),
(16, '16', 'aq', 'active', '2024-11-28 15:51:01'),
(17, '17', 'sw', 'active', '2024-11-28 15:51:01'),
(18, '18', 'de', 'active', '2024-11-28 15:51:01'),
(19, '19', 'fr', 'active', '2024-11-28 15:51:01'),
(20, '20', 'gt', 'active', '2024-11-28 15:51:01'),
(21, '21', 'zx', 'active', '2024-11-28 15:51:32'),
(22, '22', 'xz', 'active', '2024-11-28 15:51:32'),
(23, '23', 'xc', 'active', '2024-11-28 15:51:32'),
(24, '24', 'cx', 'active', '2024-11-28 15:51:32'),
(25, '25', 'cv', 'active', '2024-11-28 15:51:32'),
(26, '26', 'dsadas', 'active', '2024-11-28 15:52:01'),
(27, '27', 'asdasd', 'active', '2024-11-28 15:52:01'),
(28, '28', 'dsada', 'active', '2024-11-28 15:52:01'),
(29, '29', 'asdasd', 'active', '2024-11-28 15:52:01'),
(30, '30', 'asdasd', 'active', '2024-11-28 15:52:01'),
(36, '31', 'qweqweq', 'active', '2024-11-28 15:52:59'),
(37, '32', 'weqweqweq', 'active', '2024-11-28 15:52:59'),
(38, '33', 'qweqweasdac', 'active', '2024-11-28 15:52:59'),
(39, '34', 'zsdczdcx', 'active', '2024-11-28 15:52:59'),
(40, '35', 'dcxcx', 'active', '2024-11-28 15:52:59'),
(41, '101', 'Ross', 'active', '2024-11-29 03:20:16'),
(42, '1926', 'Francis Crus', 'active', '2024-11-29 03:20:32'),
(43, '28563', 'Clarence Malapitan', 'active', '2024-11-29 03:20:53'),
(44, '1205734', 'Edrian Pacifico', 'active', '2024-11-29 03:21:09'),
(45, '99', 'Francis', 'active', '2024-12-02 09:20:01'),
(46, '98', 'Pacifico', 'active', '2024-12-02 09:20:01'),
(47, '97', 'Earl', 'active', '2024-12-02 09:20:01'),
(48, '96', 'Vince', 'active', '2024-12-02 09:20:01'),
(49, '65', 'Juvyb', 'active', '2024-12-02 09:20:01'),
(51, '102', 'Clar', 'active', '2024-12-02 09:21:05'),
(52, '103', 'Joric', 'active', '2024-12-02 09:21:05'),
(53, '104', 'Lhiam', 'active', '2024-12-02 09:21:05'),
(54, '105', 'Bryan', 'active', '2024-12-02 09:21:05'),
(55, '201', 'King', 'active', '2024-12-02 09:21:42'),
(56, '202', 'Kong', 'active', '2024-12-02 09:21:42'),
(57, '203', 'Keng', 'active', '2024-12-02 09:21:42'),
(58, '204', 'Kung', 'active', '2024-12-02 09:21:42'),
(59, '205', 'Leng', 'active', '2024-12-02 09:21:42'),
(60, '301', 'LOL', 'active', '2024-12-02 09:22:11'),
(61, '302', 'LEL', 'active', '2024-12-02 09:22:11'),
(62, '303', 'LIL', 'active', '2024-12-02 09:22:11'),
(63, '304', 'LUL', 'active', '2024-12-02 09:22:11'),
(64, '305', 'KAK', 'active', '2024-12-02 09:22:11'),
(65, '223', 'asd', 'active', '2024-12-02 09:29:02'),
(66, '231231231', 'asd', 'active', '2024-12-02 09:29:02'),
(67, '23123', 'dsa', 'active', '2024-12-02 09:29:02'),
(68, '12312', 'asd', 'active', '2024-12-02 09:29:02'),
(69, '3123123', 'asd', 'active', '2024-12-02 09:29:02'),
(70, '12331', '12das', 'active', '2024-12-02 09:29:26'),
(71, '21234', 'asdasdasd', 'active', '2024-12-02 09:29:26'),
(72, '1233425', 'qwdawdaw', 'active', '2024-12-02 09:29:26'),
(73, '1231', 'dasdasdasd', 'active', '2024-12-02 09:29:26'),
(74, '213', 'asdasd1', 'active', '2024-12-02 09:29:26'),
(75, '3213123', 'dasdasdasdas', 'active', '2024-12-02 09:29:39'),
(76, '3123', 'dasdasd', 'active', '2024-12-02 09:29:39'),
(77, '23312', 'sdasa', 'active', '2024-12-02 09:29:39'),
(79, '123', 'asd', 'active', '2024-12-02 09:29:39');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `reference_id`, `message`, `is_read`, `created_at`, `user_id`) VALUES
(1, 'team_registration', 15, 'New team registration: kingking for CODM Winter Cup', 1, '2024-11-28 15:44:31', 2),
(2, 'team_registration', 16, 'New team registration: Edrian Team for CODM Winter Cup', 1, '2024-11-28 15:50:01', 2),
(3, 'team_registration', 17, 'New team registration: king team for CODM Winter Cup', 1, '2024-11-28 15:50:28', 2),
(4, 'team_registration', 18, 'New team registration: Cruz Team for CODM Winter Cup', 1, '2024-11-28 15:51:01', 2),
(5, 'team_registration', 19, 'New team registration: Francis Team for CODM Winter Cup', 1, '2024-11-28 15:51:32', 2),
(6, 'team_registration', 20, 'New team registration: Jam Team for CODM Winter Cup', 1, '2024-11-28 15:52:01', 2),
(7, 'team_registration', 21, 'New team registration: Ross Team for CODM Winter Cup', 1, '2024-11-28 15:52:39', 2),
(8, 'team_registration', 22, 'New team registration: Clarence Team for CODM Winter Cup', 1, '2024-11-28 15:52:59', 2),
(9, 'team_status_update', 15, 'Team status updated to Approved', 1, '2024-11-28 16:34:35', 2),
(10, 'team_status_update', 16, 'Team status updated to Approved', 1, '2024-11-28 16:34:38', 2),
(11, 'team_status_update', 17, 'Team status updated to Approved', 1, '2024-11-28 16:34:40', 2),
(12, 'team_status_update', 18, 'Team status updated to Approved', 1, '2024-11-28 16:34:42', 2),
(13, 'team_status_update', 19, 'Team status updated to Approved', 1, '2024-11-28 16:34:49', 2),
(14, 'team_status_update', 20, 'Team status updated to Approved', 1, '2024-11-28 16:34:50', 2),
(15, 'team_status_update', 21, 'Team status updated to Approved', 1, '2024-11-28 16:34:51', 2),
(16, 'tournament_status_update', 9, 'Tournament is now active with all teams approved', 1, '2024-11-28 16:34:52', 2),
(17, 'team_status_update', 22, 'Team status updated to Approved', 1, '2024-11-28 16:34:52', 2),
(18, 'match_update', 326, 'Match completed: Team 1 won with score 3-0', 1, '2024-11-28 20:26:13', 2),
(19, 'match_update', 327, 'Match completed: Team 2 won with score 2-3', 1, '2024-11-28 20:26:19', 2),
(20, 'match_update', 328, 'Match completed: Team 1 won with score 3-2', 1, '2024-11-28 20:26:23', 2),
(21, 'match_update', 333, 'Match completed: Team 1 won with score 3-2', 1, '2024-11-28 20:26:31', 2),
(22, 'match_update', 329, 'Match completed: Team 1 won with score 3-2', 1, '2024-11-28 20:26:42', 2),
(23, 'match_update', 334, 'Match completed: Team 1 won with score 3-2', 1, '2024-11-28 20:26:47', 2),
(24, 'match_update', 330, 'Match completed: Team 1 won with score 3-2', 1, '2024-11-28 20:27:55', 2),
(25, 'match_update', 331, 'Match completed: Team 1 won with score 3-2', 1, '2024-11-28 20:27:59', 2),
(26, 'match_update', 332, 'Match completed: Team 1 won with score 3-2', 1, '2024-11-28 20:28:03', 2),
(27, 'match_update', 337, 'Match completed: Team 1 won with score 3-2', 1, '2024-11-28 20:28:08', 2),
(28, 'match_update', 341, 'Match completed: Team 1 won with score 3-0', 1, '2024-11-28 20:36:40', 2),
(29, 'match_update', 342, 'Match completed: Team 2 won with score 2-0', 1, '2024-11-28 20:36:43', 2),
(30, 'match_update', 342, 'Match completed: Team 1 won with score 3-0', 1, '2024-11-28 20:36:47', 2),
(31, 'match_update', 343, 'Match completed: Team 1 won with score 3-0', 1, '2024-11-28 20:36:51', 2),
(32, 'match_update', 344, 'Match completed: Team 1 won with score 3-0', 1, '2024-11-28 20:36:55', 2),
(33, 'match_update', 346, 'Match completed: Team 1 won with score 3-0', 1, '2024-11-28 20:36:59', 2),
(34, 'match_update', 345, 'Match completed: Team 2 won with score 0-3', 1, '2024-11-28 20:37:02', 2),
(35, 'match_update', 347, 'Match completed: Team 1 won with score 3-0', 1, '2024-11-28 20:37:05', 2),
(36, 'tournament_brackets_cleared', 9, 'Tournament brackets have been cleared', 1, '2024-11-28 20:42:35', 2),
(37, 'tournament_completed', 9, 'Tournament has been completed', 1, '2024-11-28 20:43:05', 2),
(38, 'team_registration', 23, 'New team registration: King for Tekken 8 Tournament', 0, '2024-11-29 03:20:16', NULL),
(39, 'team_registration', 24, 'New team registration: Francis Team for Tekken 8 Tournament', 0, '2024-11-29 03:20:32', NULL),
(40, 'team_registration', 25, 'New team registration: Clarence Team for Tekken 8 Tournament', 0, '2024-11-29 03:20:53', NULL),
(41, 'team_registration', 26, 'New team registration: Edrian Team for Tekken 8 Tournament', 0, '2024-11-29 03:21:09', NULL),
(42, 'team_status_update', 23, 'Team status updated to Approved', 0, '2024-11-29 03:21:48', NULL),
(43, 'team_status_update', 24, 'Team status updated to Approved', 0, '2024-11-29 03:21:49', NULL),
(44, 'team_status_update', 25, 'Team status updated to Approved', 0, '2024-11-29 03:21:50', NULL),
(45, 'tournament_status_update', 8, 'Tournament is now active with all teams approved', 1, '2024-11-29 03:21:51', NULL),
(46, 'team_status_update', 26, 'Team status updated to Approved', 0, '2024-11-29 03:21:51', NULL),
(47, 'tournament_brackets_cleared', 8, 'Tournament brackets have been cleared', 1, '2024-11-29 03:25:28', 2),
(48, 'tournament_brackets_cleared', 8, 'Tournament brackets have been cleared', 1, '2024-11-29 03:27:57', 2),
(49, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:33:38', 2),
(50, 'tournament_brackets_cleared', 8, 'Tournament brackets have been cleared', 1, '2024-11-29 03:37:20', 2),
(51, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:37:27', 2),
(52, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:38:00', 2),
(53, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:38:29', 2),
(54, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:40:52', 2),
(55, 'tournament_brackets_cleared', 8, 'Tournament brackets have been cleared', 1, '2024-11-29 03:41:26', 2),
(56, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:42:38', 2),
(57, 'tournament_brackets_cleared', 8, 'Tournament brackets have been cleared', 1, '2024-11-29 03:43:05', 2),
(58, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:43:07', 2),
(59, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:46:16', 2),
(60, 'match_update', 22, 'Match updated: Score 3-0', 1, '2024-11-29 03:50:17', 2),
(61, 'match_update', 23, 'Match updated: Score 3-0', 1, '2024-11-29 03:50:25', 2),
(62, 'match_update', 24, 'Match updated: Score 3-0', 1, '2024-11-29 03:50:31', 2),
(63, 'tournament_brackets_cleared', 8, 'Tournament brackets have been cleared', 1, '2024-11-29 03:52:21', 2),
(64, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:53:36', 2),
(65, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:53:37', 2),
(66, 'tournament_brackets_cleared', 8, 'Tournament brackets have been cleared', 1, '2024-11-29 03:53:45', 2),
(67, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:53:48', 2),
(68, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 03:53:48', 2),
(69, 'match_update', 34, 'Match updated: Score -', 1, '2024-11-29 03:56:14', 2),
(70, 'match_update', 35, 'Match updated: Score 2-1', 1, '2024-11-29 03:56:26', 2),
(71, 'tournament_brackets_cleared', 8, 'Tournament brackets have been cleared', 1, '2024-11-29 04:00:09', 2),
(72, 'tournament_brackets_generated', 9, 'Tournament brackets have been generated', 1, '2024-11-29 04:00:16', 2),
(73, 'tournament_brackets_generated', 9, 'Tournament brackets have been generated', 1, '2024-11-29 04:00:16', 2),
(74, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 04:00:35', 2),
(75, 'tournament_brackets_generated', 8, 'Tournament brackets have been generated', 1, '2024-11-29 04:00:37', 2),
(76, 'match_update', 44, 'Match updated: Score 1-2', 1, '2024-11-29 04:01:02', 2),
(77, 'match_update', 45, 'Match updated: Score 0-3', 1, '2024-11-29 04:01:07', 2),
(78, 'match_update', 46, 'Match updated: Score 2-1', 1, '2024-11-29 04:01:13', 2),
(79, 'match_update', 47, 'Match updated: Score 3-0', 1, '2024-11-29 04:01:19', 2),
(80, 'match_update', 48, 'Match updated: Score 2-1', 1, '2024-11-29 04:01:23', 2),
(81, 'match_update', 49, 'Match updated: Score 2-1', 1, '2024-11-29 04:01:29', 2),
(82, 'tournament_completed', 9, 'Tournament has been completed', 1, '2024-11-29 04:01:34', 2),
(83, 'match_update', 50, 'Match updated: Score 3-0', 1, '2024-11-29 04:01:34', 2),
(84, 'match_update', 54, 'Match updated: Score 3-0', 1, '2024-11-29 04:21:03', 2),
(85, 'match_update', 55, 'Match updated: Score 3-0', 1, '2024-11-29 04:21:07', 2),
(86, 'tournament_completed', 8, 'Tournament has been completed', 1, '2024-11-29 04:21:11', 2),
(87, 'match_update', 56, 'Match updated: Score 2-1', 1, '2024-11-29 04:21:11', 2),
(88, 'team_registration', 27, 'New team registration: Team Francis for Valorant Pro League', 0, '2024-12-02 09:20:01', NULL),
(89, 'team_registration', 28, 'New team registration: Team Ross for Valorant Pro League', 0, '2024-12-02 09:21:05', NULL),
(90, 'team_registration', 29, 'New team registration: Team King for Valorant Pro League', 0, '2024-12-02 09:21:42', NULL),
(91, 'team_registration', 30, 'New team registration: Team LOL for Valorant Pro League', 0, '2024-12-02 09:22:11', NULL),
(92, 'team_status_update', 27, 'Team status updated to Approved', 0, '2024-12-02 09:24:17', NULL),
(93, 'team_status_update', 28, 'Team status updated to Approved', 0, '2024-12-02 09:24:19', NULL),
(94, 'team_status_update', 29, 'Team status updated to Approved', 0, '2024-12-02 09:24:20', NULL),
(95, 'tournament_status_update', 6, 'Tournament is now active with all teams approved', 1, '2024-12-02 09:24:21', NULL),
(96, 'team_status_update', 30, 'Team status updated to Approved', 0, '2024-12-02 09:24:21', NULL),
(97, 'tournament_brackets_generated', 6, 'Tournament brackets have been generated', 1, '2024-12-02 09:25:22', 2),
(98, 'tournament_brackets_generated', 6, 'Tournament brackets have been generated', 1, '2024-12-02 09:25:23', 2),
(99, 'team_registration', 31, 'New team registration: asd for Mobile Legends Championship', 0, '2024-12-02 09:29:02', NULL),
(100, 'team_registration', 32, 'New team registration: daasdac for Mobile Legends Championship', 0, '2024-12-02 09:29:26', NULL),
(101, 'team_registration', 33, 'New team registration: dasdaasdsdas for Mobile Legends Championship', 0, '2024-12-02 09:29:39', NULL),
(102, 'team_status_update', 31, 'Team status updated to Approved', 0, '2024-12-02 09:29:54', NULL),
(103, 'team_status_update', 32, 'Team status updated to Approved', 0, '2024-12-02 09:29:55', NULL),
(104, 'team_status_update', 33, 'Team status updated to Approved', 0, '2024-12-02 09:29:56', NULL),
(105, 'match_update', 60, 'Match updated: Score 3-0', 1, '2024-12-02 09:31:42', 2),
(106, 'match_update', 61, 'Match updated: Score 1-2', 1, '2024-12-02 09:31:57', 2),
(107, 'tournament_completed', 6, 'Tournament has been completed', 1, '2024-12-02 09:34:26', 2),
(108, 'match_update', 62, 'Match updated: Score 3-0', 1, '2024-12-02 09:34:26', 2);

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
(15, 9, 'kingking', NULL, 'qwe', 5, '67488fdf6ce80.png', '', '2024-11-28 15:44:31'),
(16, 9, 'Edrian Team', NULL, 'qwe', 5, '67489129a6b89.png', '', '2024-11-28 15:50:01'),
(17, 9, 'king team', NULL, 'qa', 5, '674891447693c.png', '', '2024-11-28 15:50:28'),
(18, 9, 'Cruz Team', NULL, 'aq', 5, '674891650f152.png', '', '2024-11-28 15:51:01'),
(19, 9, 'Francis Team', NULL, 'zx', 5, '6748918485112.png', '', '2024-11-28 15:51:32'),
(20, 9, 'Jam Team', NULL, 'po', 5, '674891a1b39db.png', '', '2024-11-28 15:52:01'),
(21, 9, 'Ross Team', NULL, 'dsadas', 5, '674891c7de7ce.png', '', '2024-11-28 15:52:39'),
(22, 9, 'Clarence Team', NULL, 'qweqweq', 5, 'default-team-logo.png', '', '2024-11-28 15:52:59'),
(23, 8, 'King', NULL, 'Christian Pacifico', 1, '674932f066a5f.png', '', '2024-11-29 03:20:16'),
(24, 8, 'Francis Team', NULL, 'Francis Crus', 1, 'default-team-logo.png', '', '2024-11-29 03:20:32'),
(25, 8, 'Clarence Team', NULL, 'Clarence Malapitan', 1, 'default-team-logo.png', '', '2024-11-29 03:20:52'),
(26, 8, 'Edrian Team', NULL, 'Edrian Pacifico', 1, 'default-team-logo.png', '', '2024-11-29 03:21:09'),
(27, 6, 'Team Francis', NULL, 'Francis', 5, 'default-team-logo.png', '', '2024-12-02 09:20:01'),
(28, 6, 'Team Ross', NULL, 'Ross', 5, 'default-team-logo.png', '', '2024-12-02 09:21:05'),
(29, 6, 'Team King', NULL, 'King', 5, 'default-team-logo.png', '', '2024-12-02 09:21:42'),
(30, 6, 'Team LOL', NULL, 'LOL', 5, 'default-team-logo.png', '', '2024-12-02 09:22:11'),
(31, 4, 'asd', NULL, 'asd', 5, 'default-team-logo.png', '', '2024-12-02 09:29:02'),
(32, 4, 'daasdac', NULL, '12das', 5, 'default-team-logo.png', '', '2024-12-02 09:29:26'),
(33, 4, 'dasdaasdsdas', NULL, 'dasdasdasdas', 5, 'default-team-logo.png', '', '2024-12-02 09:29:39');

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
(7, 15, 3, '2024-11-28 15:44:31'),
(8, 16, 3, '2024-11-28 15:50:01'),
(9, 17, 3, '2024-11-28 15:50:28'),
(10, 18, 3, '2024-11-28 15:51:01'),
(11, 19, 3, '2024-11-28 15:51:32'),
(12, 20, 3, '2024-11-28 15:52:01'),
(13, 21, 3, '2024-11-28 15:52:39'),
(14, 22, 3, '2024-11-28 15:52:59'),
(15, 23, 2, '2024-11-29 03:20:16'),
(16, 24, 2, '2024-11-29 03:20:32'),
(17, 25, 2, '2024-11-29 03:20:52'),
(18, 26, 2, '2024-11-29 03:21:09'),
(19, 27, 5, '2024-12-02 09:20:01'),
(20, 28, 5, '2024-12-02 09:21:05'),
(21, 29, 5, '2024-12-02 09:21:42'),
(22, 30, 5, '2024-12-02 09:22:11'),
(23, 31, 1, '2024-12-02 09:29:02'),
(24, 32, 1, '2024-12-02 09:29:26'),
(25, 33, 1, '2024-12-02 09:29:39');

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
(5, 15, 5, 0, '2024-11-28 15:44:31'),
(6, 16, 6, 1, '2024-11-28 15:50:01'),
(7, 16, 7, 0, '2024-11-28 15:50:01'),
(8, 16, 8, 0, '2024-11-28 15:50:01'),
(9, 16, 9, 0, '2024-11-28 15:50:01'),
(10, 16, 10, 0, '2024-11-28 15:50:01'),
(11, 17, 11, 1, '2024-11-28 15:50:28'),
(12, 17, 12, 0, '2024-11-28 15:50:28'),
(13, 17, 13, 0, '2024-11-28 15:50:28'),
(14, 17, 14, 0, '2024-11-28 15:50:28'),
(15, 17, 15, 0, '2024-11-28 15:50:28'),
(16, 18, 16, 1, '2024-11-28 15:51:01'),
(17, 18, 17, 0, '2024-11-28 15:51:01'),
(18, 18, 18, 0, '2024-11-28 15:51:01'),
(19, 18, 19, 0, '2024-11-28 15:51:01'),
(20, 18, 20, 0, '2024-11-28 15:51:01'),
(21, 19, 21, 1, '2024-11-28 15:51:32'),
(22, 19, 22, 0, '2024-11-28 15:51:32'),
(23, 19, 23, 0, '2024-11-28 15:51:32'),
(24, 19, 24, 0, '2024-11-28 15:51:32'),
(25, 19, 25, 0, '2024-11-28 15:51:32'),
(26, 20, 26, 1, '2024-11-28 15:52:01'),
(27, 20, 27, 0, '2024-11-28 15:52:01'),
(28, 20, 28, 0, '2024-11-28 15:52:01'),
(29, 20, 29, 0, '2024-11-28 15:52:01'),
(30, 20, 30, 0, '2024-11-28 15:52:01'),
(31, 21, 26, 1, '2024-11-28 15:52:39'),
(32, 21, 27, 0, '2024-11-28 15:52:39'),
(33, 21, 28, 0, '2024-11-28 15:52:39'),
(34, 21, 29, 0, '2024-11-28 15:52:39'),
(35, 21, 30, 0, '2024-11-28 15:52:39'),
(36, 22, 36, 1, '2024-11-28 15:52:59'),
(37, 22, 37, 0, '2024-11-28 15:52:59'),
(38, 22, 38, 0, '2024-11-28 15:52:59'),
(39, 22, 39, 0, '2024-11-28 15:52:59'),
(40, 22, 40, 0, '2024-11-28 15:52:59'),
(41, 23, 41, 1, '2024-11-29 03:20:16'),
(42, 24, 42, 1, '2024-11-29 03:20:32'),
(43, 25, 43, 1, '2024-11-29 03:20:53'),
(44, 26, 44, 1, '2024-11-29 03:21:09'),
(45, 27, 45, 1, '2024-12-02 09:20:01'),
(46, 27, 46, 0, '2024-12-02 09:20:01'),
(47, 27, 47, 0, '2024-12-02 09:20:01'),
(48, 27, 48, 0, '2024-12-02 09:20:01'),
(49, 27, 49, 0, '2024-12-02 09:20:01'),
(50, 28, 41, 1, '2024-12-02 09:21:05'),
(51, 28, 51, 0, '2024-12-02 09:21:05'),
(52, 28, 52, 0, '2024-12-02 09:21:05'),
(53, 28, 53, 0, '2024-12-02 09:21:05'),
(54, 28, 54, 0, '2024-12-02 09:21:05'),
(55, 29, 55, 1, '2024-12-02 09:21:42'),
(56, 29, 56, 0, '2024-12-02 09:21:42'),
(57, 29, 57, 0, '2024-12-02 09:21:42'),
(58, 29, 58, 0, '2024-12-02 09:21:42'),
(59, 29, 59, 0, '2024-12-02 09:21:42'),
(60, 30, 60, 1, '2024-12-02 09:22:11'),
(61, 30, 61, 0, '2024-12-02 09:22:11'),
(62, 30, 62, 0, '2024-12-02 09:22:11'),
(63, 30, 63, 0, '2024-12-02 09:22:11'),
(64, 30, 64, 0, '2024-12-02 09:22:11'),
(65, 31, 65, 1, '2024-12-02 09:29:02'),
(66, 31, 66, 0, '2024-12-02 09:29:02'),
(67, 31, 67, 0, '2024-12-02 09:29:02'),
(68, 31, 68, 0, '2024-12-02 09:29:02'),
(69, 31, 69, 0, '2024-12-02 09:29:02'),
(70, 32, 70, 1, '2024-12-02 09:29:26'),
(71, 32, 71, 0, '2024-12-02 09:29:26'),
(72, 32, 72, 0, '2024-12-02 09:29:26'),
(73, 32, 73, 0, '2024-12-02 09:29:26'),
(74, 32, 74, 0, '2024-12-02 09:29:26'),
(75, 33, 75, 1, '2024-12-02 09:29:39'),
(76, 33, 76, 0, '2024-12-02 09:29:39'),
(77, 33, 77, 0, '2024-12-02 09:29:39'),
(78, 33, 73, 0, '2024-12-02 09:29:39'),
(79, 33, 79, 0, '2024-12-02 09:29:39');

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
  `status` enum('registration','ongoing','completed','active') DEFAULT 'registration',
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
  `winner_id` int(11) DEFAULT NULL,
  `bracket_type` enum('single_elimination','double_elimination') DEFAULT 'single_elimination',
  `brackets_generated` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `platform` enum('Physical','Online') NOT NULL DEFAULT 'Online'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournaments`
--

INSERT INTO `tournaments` (`id`, `game_id`, `name`, `start_date`, `end_date`, `status`, `created_at`, `max_teams`, `prize_pool`, `registration_deadline`, `rules`, `registration_open`, `team_size`, `auto_assign_gm`, `match_duration`, `gamemaster_id`, `tournament_type`, `winner_id`, `bracket_type`, `brackets_generated`, `completed_at`, `platform`) VALUES
(4, 1, 'Mobile Legends Championship', '2024-12-01', '2024-12-15', 'registration', '2024-11-25 14:12:30', 4, 10000.00, '2024-11-30', NULL, 1, 5, 0, 60, NULL, '5v5', NULL, 'single_elimination', 0, NULL, 'Online'),
(6, 5, 'Valorant Pro League', '2024-12-10', '2024-12-25', 'completed', '2024-11-25 14:12:30', 4, 20000.00, '2024-12-09', 'a', 1, 5, 0, 60, NULL, '5v5', 29, 'single_elimination', 1, '2024-12-02 17:34:26', 'Online'),
(7, 1, 'Mobile Legends Championship', '2024-12-01', '2024-12-15', 'registration', '2024-11-25 14:30:11', 8, 10000.00, '2024-11-30', 'test', 1, 5, 0, 60, NULL, '5v5', NULL, 'single_elimination', 0, NULL, 'Online'),
(8, 2, 'Tekken 8 Tournament', '2024-12-05', '2024-12-20', 'completed', '2024-11-25 14:30:11', 4, 15000.00, '2024-12-04', 'asdasd', 1, 5, 0, 60, NULL, '5v5', 26, 'single_elimination', 1, '2024-11-29 12:21:11', 'Online'),
(9, 3, 'CODM Winter Cup', '2024-12-10', '2024-12-25', 'completed', '2024-11-25 14:30:11', 8, 20000.00, '2024-12-09', 'ahahaha', 1, 5, 0, 60, NULL, '', 16, 'single_elimination', 1, '2024-11-29 12:01:34', 'Online'),
(11, 9, 'Soccer Championship', '2025-01-15', '2025-01-20', 'registration', '2025-01-18 03:36:35', 16, 10000.00, '2025-01-10', NULL, 1, 5, 0, 60, NULL, '5v5', NULL, 'single_elimination', 0, NULL, 'Physical'),
(12, 10, 'Basketball League', '2025-02-01', '2025-02-05', 'registration', '2025-01-18 03:36:35', 12, 15000.00, '2025-01-25', NULL, 1, 5, 0, 60, NULL, '5v5', NULL, 'single_elimination', 0, NULL, 'Physical'),
(13, 11, 'Volleyball Tournament', '2025-03-10', '2025-03-15', 'registration', '2025-01-18 03:36:35', 8, 8000.00, '2025-03-05', NULL, 1, 5, 0, 60, NULL, '5v5', NULL, 'single_elimination', 0, NULL, 'Physical'),
(14, 12, 'Table Tennis Open', '2025-04-01', '2025-04-03', 'registration', '2025-01-18 03:36:35', 32, 5000.00, '2025-03-25', NULL, 1, 5, 0, 60, NULL, '5v5', NULL, 'single_elimination', 0, NULL, 'Physical');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bracket_position` int(11) DEFAULT 0,
  `bracket_type` enum('winners','losers') DEFAULT 'winners',
  `match_time` time DEFAULT NULL,
  `team1_score` int(11) DEFAULT 0,
  `team2_score` int(11) DEFAULT 0,
  `best_of` int(11) DEFAULT 3 COMMENT 'Number of games in the match (e.g., 3 for best of 3)',
  `games_won_team1` int(11) DEFAULT 0 COMMENT 'Number of games won by team 1',
  `games_won_team2` int(11) DEFAULT 0 COMMENT 'Number of games won by team 2',
  `current_game` int(11) DEFAULT 1 COMMENT 'Current game number in the match',
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
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
(15, 9, 2, 'active', '2024-11-27 05:34:39'),
(16, 6, 2, 'active', '2024-12-02 09:23:57');

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
-- Table structure for table `tournament_single_elimination`
--

CREATE TABLE `tournament_single_elimination` (
  `id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `round_number` int(11) NOT NULL,
  `match_order` int(11) NOT NULL,
  `team1_id` int(11) DEFAULT NULL,
  `team2_id` int(11) DEFAULT NULL,
  `winner_id` int(11) DEFAULT NULL,
  `team1_score` int(11) DEFAULT 0,
  `team2_score` int(11) DEFAULT 0,
  `match_date` date DEFAULT NULL,
  `match_time` time DEFAULT NULL,
  `status` enum('pending','ongoing','completed') DEFAULT 'pending',
  `best_of` int(11) DEFAULT 3,
  `bracket_position` int(11) NOT NULL,
  `bye_match` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tournament_single_elimination`
--

INSERT INTO `tournament_single_elimination` (`id`, `tournament_id`, `round_number`, `match_order`, `team1_id`, `team2_id`, `winner_id`, `team1_score`, `team2_score`, `match_date`, `match_time`, `status`, `best_of`, `bracket_position`, `bye_match`, `created_at`, `updated_at`) VALUES
(44, 9, 1, 0, 22, 16, 16, 1, 2, NULL, NULL, 'completed', 3, 0, 0, '2024-11-29 04:00:16', '2024-11-29 04:01:02'),
(45, 9, 1, 1, 18, 21, 21, 0, 3, NULL, NULL, 'completed', 3, 1, 0, '2024-11-29 04:00:16', '2024-11-29 04:01:07'),
(46, 9, 1, 2, 17, 20, 17, 2, 1, NULL, NULL, 'completed', 3, 2, 0, '2024-11-29 04:00:16', '2024-11-29 04:01:13'),
(47, 9, 1, 3, 19, 15, 19, 3, 0, NULL, NULL, 'completed', 3, 3, 0, '2024-11-29 04:00:16', '2024-11-29 04:01:19'),
(48, 9, 2, 0, 16, 21, 16, 2, 1, NULL, NULL, 'completed', 3, 0, 0, '2024-11-29 04:00:16', '2024-11-29 04:01:23'),
(49, 9, 2, 1, 17, 19, 17, 2, 1, NULL, NULL, 'completed', 3, 0, 0, '2024-11-29 04:00:16', '2024-11-29 04:01:29'),
(50, 9, 3, 0, 16, 17, 16, 3, 0, NULL, NULL, 'completed', 3, 0, 0, '2024-11-29 04:00:16', '2024-11-29 04:01:34'),
(54, 8, 1, 0, 26, 24, 26, 3, 0, NULL, NULL, 'completed', 3, 0, 0, '2024-11-29 04:00:37', '2024-11-29 04:21:03'),
(55, 8, 1, 1, 25, 23, 25, 3, 0, NULL, NULL, 'completed', 3, 1, 0, '2024-11-29 04:00:37', '2024-11-29 04:21:07'),
(56, 8, 2, 0, 26, 25, 26, 2, 1, NULL, NULL, 'completed', 3, 0, 0, '2024-11-29 04:00:37', '2024-11-29 04:21:11'),
(60, 6, 1, 0, 29, 30, 29, 3, 0, NULL, NULL, 'completed', 3, 0, 0, '2024-12-02 09:25:23', '2024-12-02 09:31:42'),
(61, 6, 1, 1, 27, 28, 28, 1, 2, NULL, NULL, 'completed', 3, 1, 0, '2024-12-02 09:25:23', '2024-12-02 09:31:57'),
(62, 6, 2, 0, 29, 28, 29, 3, 0, NULL, NULL, 'completed', 3, 0, 0, '2024-12-02 09:25:23', '2024-12-02 09:34:26');

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
(1, 9, 15, '2024-11-28 15:44:31', 'approved'),
(2, 9, 16, '2024-11-28 15:50:01', 'approved'),
(3, 9, 17, '2024-11-28 15:50:28', 'approved'),
(4, 9, 18, '2024-11-28 15:51:01', 'approved'),
(5, 9, 19, '2024-11-28 15:51:32', 'approved'),
(6, 9, 20, '2024-11-28 15:52:01', 'approved'),
(7, 9, 21, '2024-11-28 15:52:39', 'approved'),
(8, 9, 22, '2024-11-28 15:52:59', 'approved'),
(9, 8, 23, '2024-11-29 03:20:16', 'approved'),
(10, 8, 24, '2024-11-29 03:20:32', 'approved'),
(11, 8, 25, '2024-11-29 03:20:52', 'approved'),
(12, 8, 26, '2024-11-29 03:21:09', 'approved'),
(13, 6, 27, '2024-12-02 09:20:01', 'approved'),
(14, 6, 28, '2024-12-02 09:21:05', 'approved'),
(15, 6, 29, '2024-12-02 09:21:42', 'approved'),
(16, 6, 30, '2024-12-02 09:22:11', 'approved'),
(17, 4, 31, '2024-12-02 09:29:02', 'approved'),
(18, 4, 32, '2024-12-02 09:29:26', 'approved'),
(19, 4, 33, '2024-12-02 09:29:39', 'approved');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `email`, `avatar`, `email_notifications`, `match_alerts`, `tournament_updates`, `dark_mode`, `compact_view`, `two_factor`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', NULL, '', 'default.png', 1, 1, 1, 0, 0, 0, '$2a$12$dnAoyLlBusc/NBseIP4CZu/kIKwUIBG8sL4JpcsgOu5GnVYwgLYHO', 'admin', 'active', '2024-11-25 03:56:35', '2025-01-18 04:03:35'),
(2, 'soozu', NULL, 'kingpacifico0021@gmail.com', '6748c7c44303f.png', 1, 1, 1, 0, 0, 0, '$2a$12$dnAoyLlBusc/NBseIP4CZu/kIKwUIBG8sL4JpcsgOu5GnVYwgLYHO', 'gamemaster', 'active', '2024-11-25 04:34:43', '2025-01-18 03:23:58'),
(3, 'gamemaster', NULL, '', 'default.png', 1, 1, 1, 0, 0, 0, '$2a$12$OyE4ZzpLmLo3BqvBjiLboec8wHlKjsDhEtA6E.8pzWYttn0vD3hfa', 'gamemaster', 'active', '2024-11-25 14:45:48', '2024-12-02 09:33:42'),
(12, 'chaw', 'Christian Pacifico', 'kingpacifico0021@gmail.com', 'default.png', 1, 1, 1, 0, 0, 0, '$2y$10$SWnJytkSL49ewI.ivfYcA.PreoKolzssEKnJrQ6ctV9z3u1duCJ5W', 'gamemaster', 'active', '2024-11-29 05:16:04', NULL),
(13, 'king', 'Christian Pacifico', 'eleanorpacifico@gmail.com', 'default.png', 1, 1, 1, 0, 0, 0, '$2y$10$1yfhvr87HJAwkABqvlTiBu9Q8eTRR/nmLiqdDW.MkpjSXeSZBWVjO', 'gamemaster', 'active', '2024-12-02 09:36:13', NULL);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

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
  ADD KEY `winner_id` (`winner_id`),
  ADD KEY `idx_tournament_brackets` (`tournament_id`,`round_number`,`bracket_type`);

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
-- Indexes for table `tournament_single_elimination`
--
ALTER TABLE `tournament_single_elimination`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tournament_id` (`tournament_id`),
  ADD KEY `team1_id` (`team1_id`),
  ADD KEY `team2_id` (`team2_id`),
  ADD KEY `winner_id` (`winner_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `games`
--
ALTER TABLE `games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `system_alerts`
--
ALTER TABLE `system_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `team_games`
--
ALTER TABLE `team_games`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `tournaments`
--
ALTER TABLE `tournaments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tournament_brackets`
--
ALTER TABLE `tournament_brackets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=355;

--
-- AUTO_INCREMENT for table `tournament_game_masters`
--
ALTER TABLE `tournament_game_masters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

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
-- AUTO_INCREMENT for table `tournament_single_elimination`
--
ALTER TABLE `tournament_single_elimination`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `tournament_teams`
--
ALTER TABLE `tournament_teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

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
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `system_alerts`
--
ALTER TABLE `system_alerts`
  ADD CONSTRAINT `system_alerts_ibfk_1` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `teams`
--
ALTER TABLE `teams`
  ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teams_ibfk_2` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `tournaments_ibfk_3` FOREIGN KEY (`winner_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `tournaments_ibfk_4` FOREIGN KEY (`winner_id`) REFERENCES `teams` (`id`);

--
-- Constraints for table `tournament_brackets`
--
ALTER TABLE `tournament_brackets`
  ADD CONSTRAINT `tournament_brackets_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`),
  ADD CONSTRAINT `tournament_brackets_ibfk_2` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `tournament_brackets_ibfk_3` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `tournament_brackets_ibfk_4` FOREIGN KEY (`winner_id`) REFERENCES `teams` (`id`),
  ADD CONSTRAINT `tournament_brackets_ibfk_5` FOREIGN KEY (`winner_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

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
-- Constraints for table `tournament_single_elimination`
--
ALTER TABLE `tournament_single_elimination`
  ADD CONSTRAINT `tournament_single_elimination_ibfk_1` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tournament_single_elimination_ibfk_2` FOREIGN KEY (`team1_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tournament_single_elimination_ibfk_3` FOREIGN KEY (`team2_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tournament_single_elimination_ibfk_4` FOREIGN KEY (`winner_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL;

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
