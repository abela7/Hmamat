-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 14, 2025 at 07:52 AM
-- Server version: 10.11.11-MariaDB-cll-lve
-- PHP Version: 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `abunetdg_hmamat`
--

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `default_points` int(11) NOT NULL DEFAULT 5,
  `day_of_week` int(11) DEFAULT NULL CHECK (`day_of_week` between 1 and 7),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `name`, `description`, `default_points`, `day_of_week`, `created_at`) VALUES
(1, 'ጸሎት', 'በየቀኑ መጸለይ', 10, NULL, '2025-04-13 13:37:22'),
(2, 'ጾም', 'ለ 18 ሰዓታት ማለትም ከ ምሽቱ 9pm  - 5pm ድረስ መጾም', 10, NULL, '2025-04-13 15:12:51'),
(3, 'የጠዋት 9 ሰዓት ስግደት', 'በተቻለ መጠን ጠዋት ተነስቶ መስገድ', 5, NULL, '2025-04-13 15:14:48'),
(4, 'በየቀኑ የቻሉትን ያክል ምጽዋት መስጠት', 'መመጽዎት', 10, NULL, '2025-04-13 15:16:02'),
(5, 'መጽሐፍ ቅዱስ ማንበብ', 'መጽሐፍ ቅዱስን እንዲሁም መንፈሳዊ መጽሐፍትን ማንበብ', 8, NULL, '2025-04-13 15:17:12');

-- --------------------------------------------------------

--
-- Table structure for table `activity_miss_reasons`
--

CREATE TABLE `activity_miss_reasons` (
  `id` int(11) NOT NULL,
  `reason_text` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_miss_reasons`
--

INSERT INTO `activity_miss_reasons` (`id`, `reason_text`, `created_at`) VALUES
(1, 'ጊዜ ማጣት', '2025-04-13 15:35:11'),
(2, 'ስንፍና', '2025-04-13 15:35:19'),
(3, 'ህመም', '2025-04-13 15:35:26'),
(4, 'ድካም', '2025-04-13 15:35:32'),
(5, 'የቦታ አለመመቻቸት', '2025-04-13 15:35:51');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'admin',
  `password` varchar(255) NOT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `role`, `password`, `last_ip`, `last_login`, `created_at`) VALUES
(1, 'Amhaslassie', 'admin', '$2y$10$TTUTcF4Yc00X8VeB5p2LZOCwIxNxEIF7xCetvmVb.yy8Ome5u0.5q', NULL, NULL, '2025-04-13 13:28:18'),
(2, 'Gebremar', 'admin', '$2y$10$vsnDrM98cawKWvuPUbewJueNVrNmhHL4Gyn72vFk1Th97ORliIZWW', '193.237.166.126', '2025-04-13 15:11:14', '2025-04-13 15:11:14');

-- --------------------------------------------------------

--
-- Table structure for table `daily_messages`
--

CREATE TABLE `daily_messages` (
  `id` int(11) NOT NULL,
  `day_of_week` int(11) DEFAULT NULL CHECK (`day_of_week` between 1 and 7),
  `message_text` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_messages`
--

INSERT INTO `daily_messages` (`id`, `day_of_week`, `message_text`, `created_at`) VALUES
(1, 1, 'የህማማት የመጀመሪያ ቀን ሰኞ', '2025-04-13 15:36:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `baptism_name` varchar(50) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `password` varchar(255) NOT NULL,
  `unique_id` varchar(64) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `baptism_name`, `role`, `password`, `unique_id`, `email`, `last_ip`, `user_agent`, `last_login`, `created_at`) VALUES
(3, 'Askal', 'user', '$2y$10$ybWgB2BfQEF/I9ipoN265uLhHLV.fJB.wfr3G6iqml8iMGNg3ed9a', 'e0815fd829a808280fc9999f89ae53c5', NULL, '92.40.182.79', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36', '2025-04-14 07:27:18', '2025-04-13 14:47:29'),
(4, 'Welde Amanuel', 'user', '$2y$10$I5A8g83B5glABSG3Fg7FzuFAYaAWabFKow02l.ghjdA0HQlnqFccG', '10d345933703e7c2c0154d1361eacfa3', NULL, '92.40.182.85', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36', '2025-04-13 16:23:29', '2025-04-13 16:23:29'),
(5, 'Welde Yohannes', 'user', '$2y$10$NXVfj5GUWlSXvhuJwRLRQ.ssn96O8XcuuJzOS45/OAS/ONM7Wzgp6', '97e3eb4372bc9e7e22ba50a3e161b1a1', NULL, '104.28.86.81', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_3_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.3.1 Mobile/15E148 Safari/604.1', '2025-04-13 19:39:47', '2025-04-13 19:39:47'),
(7, 'wel', 'user', '$2y$10$dc2/m1EMsjMUDhBYDzCps.GM5kXk3/IB0wwq4ugcjwKxxDh11xcTq', 'a489284149f29910698a0e85d7de0e52', NULL, '92.40.182.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-14 07:23:21', '2025-04-14 06:31:04');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `date_completed` date NOT NULL,
  `status` enum('done','missed') NOT NULL DEFAULT 'done',
  `reason_id` int(11) DEFAULT NULL,
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_devices`
--

CREATE TABLE `user_devices` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `device_token` varchar(255) NOT NULL,
  `device_fingerprint` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `last_used` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_devices`
--

INSERT INTO `user_devices` (`id`, `user_id`, `device_token`, `device_fingerprint`, `ip_address`, `user_agent`, `created_at`, `last_used`) VALUES
(10, 7, 'ff98eae613e00ac62d2968c96f3c06a977f3dabdacf3fa4e708076eeec323061', '8eb9e7301ef82b5e807359b28354cf13', '92.40.182.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', '2025-04-14 06:31:04', '2025-04-14 07:23:21'),
(13, 3, '671e9e5b5a8fc41f5cf03b5eb05468ba293361f5f1296c4324e677e27b720d44', 'fe59bc056ef02f92759185ba88c7c26c', '92.40.182.79', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36', '2025-04-14 07:27:18', '2025-04-14 07:27:18');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `language` varchar(10) DEFAULT 'en',
  `show_on_leaderboard` tinyint(1) DEFAULT 1,
  `email_notifications` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `user_id`, `language`, `show_on_leaderboard`, `email_notifications`, `created_at`, `updated_at`) VALUES
(1, 3, 'en', 1, 1, '2025-04-14 05:48:46', '2025-04-14 05:49:00'),
(3, 4, 'en', 1, 1, '2025-04-14 06:11:54', '2025-04-14 06:11:54'),
(4, 5, 'en', 1, 1, '2025-04-14 06:11:54', '2025-04-14 06:11:54');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `device_info` text DEFAULT NULL,
  `fingerprint` varchar(128) DEFAULT NULL,
  `last_active` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_sessions`
--

INSERT INTO `user_sessions` (`id`, `user_id`, `session_token`, `ip_address`, `device_info`, `fingerprint`, `last_active`) VALUES
(33, 7, '357d9b6580529cdcf095ca5f6a9dd3a858aa6121e5e2e4f0eb6ad057b20830ea', '92.40.182.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', NULL, '2025-04-14 06:31:04'),
(36, 7, 'c80b396c79f1de57784d2a0d8f58eadbc2ef5268afd726ef1f525ef40e04d597', '92.40.182.78', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Safari/537.36', NULL, '2025-04-14 07:23:21'),
(37, 3, '6af4e3be26c31dd086c1346aab0a89770ed29e99b263c88bf8a95d99ca025fc2', '92.40.182.79', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/135.0.0.0 Mobile Safari/537.36', NULL, '2025-04-14 07:27:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_miss_reasons`
--
ALTER TABLE `activity_miss_reasons`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `daily_messages`
--
ALTER TABLE `daily_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `baptism_name` (`baptism_name`),
  ADD UNIQUE KEY `unique_id_UNIQUE` (`unique_id`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `activity_id` (`activity_id`),
  ADD KEY `reason_id` (`reason_id`);

--
-- Indexes for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `device_token` (`device_token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `device_fingerprint` (`device_fingerprint`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `activity_miss_reasons`
--
ALTER TABLE `activity_miss_reasons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `daily_messages`
--
ALTER TABLE `daily_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `user_devices`
--
ALTER TABLE `user_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_activity_log_ibfk_2` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_activity_log_ibfk_3` FOREIGN KEY (`reason_id`) REFERENCES `activity_miss_reasons` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_devices`
--
ALTER TABLE `user_devices`
  ADD CONSTRAINT `user_devices_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
