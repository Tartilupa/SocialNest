-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 13, 2025 at 11:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `linknest`
--

-- --------------------------------------------------------

--
-- Table structure for table `advanced_user_interactions`
--

CREATE TABLE `advanced_user_interactions` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `post_id` int(11) NOT NULL,
  `interaction_type` enum('like','view','dwell','share','comment','save') NOT NULL,
  `interaction_strength` float DEFAULT 1,
  `time_spent` int(11) DEFAULT 0,
  `context_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hashtag_cooccurrence`
--

CREATE TABLE `hashtag_cooccurrence` (
  `id` int(11) NOT NULL,
  `hashtag1` varchar(255) NOT NULL,
  `hashtag2` varchar(255) NOT NULL,
  `cooccurrence_count` int(11) DEFAULT 1,
  `mutual_strength` float DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hashtag_semantics`
--

CREATE TABLE `hashtag_semantics` (
  `id` int(11) NOT NULL,
  `hashtag` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sentiment_score` float DEFAULT 0,
  `popularity_trend` float DEFAULT 0,
  `quality_score` float DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `author` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `likes` int(11) DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `content`, `author`, `created_at`, `likes`) VALUES
(21, 'hi i am a admin and you are a user. please donate', 'Lenart Lindic', '2025-02-28 12:33:57', 1),
(22, 'test', 'Admin', '2025-02-28 12:38:56', 1),
(23, 'hello\n#hello', 'test', '2025-09-13 09:12:43', 1),
(24, 'hello\n#hello', 'test', '2025-09-13 09:20:11', 1),
(25, 'hello\n#hello', 'test', '2025-09-13 09:22:12', 1),
(26, 'hello\n#hello', 'test', '2025-09-13 09:37:33', 1);

-- --------------------------------------------------------

--
-- Table structure for table `post_hashtags`
--

CREATE TABLE `post_hashtags` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `hashtag` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_hashtags`
--

INSERT INTO `post_hashtags` (`id`, `post_id`, `hashtag`, `created_at`) VALUES
(1, 23, 'hello', '2025-09-13 09:12:43'),
(7, 24, 'hello', '2025-09-13 09:20:12'),
(24, 25, 'hello', '2025-09-13 09:22:12'),
(34, 26, 'hello', '2025-09-13 09:38:27');

-- --------------------------------------------------------

--
-- Table structure for table `reported_posts`
--

CREATE TABLE `reported_posts` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `reported_by` varchar(255) NOT NULL,
  `report_reason` text DEFAULT NULL,
  `reported_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `reported_posts`
--

INSERT INTO `reported_posts` (`id`, `post_id`, `reported_by`, `report_reason`, `reported_at`) VALUES
(1, 3, 'honda', 'ker je moteÄ‡', '2025-01-03 18:15:23');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `name` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified` tinyint(1) DEFAULT 0,
  `is_premium` tinyint(1) DEFAULT 0,
  `premium_expires` date DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `name`, `created_at`, `verified`, `is_premium`, `premium_expires`, `ip`) VALUES
(34, 'q', 'l@gmail.com', '$2y$10$C54XmXilws/kBEL.Ur3ok.e2J6TrYzCla0VgRUYdDDZFja28/WixO', 'Admin', '2025-02-28 12:38:30', 1, 0, NULL, NULL),
(1, 'Admin', 'lenartlindic27@gmail.com', '$2y$10$zFYyHcYSJzee.3B4VmvhYOV39aOGbRV.ulVs3ZsBszhDby14mQ/da', 'Lenart Lindic', '2025-02-28 12:31:05', 1, 0, NULL, NULL),
(35, 'test', 'l@vv.v', '$2y$10$xT1j3fAwISwgt4g/6DkKa.P1ZaDt3XXBE8AJZ9UISpW6T8i5zkUsW', 'test test', '2025-03-13 13:28:34', 0, 0, NULL, NULL),
(36, '<a href=\"http://example.com\">Here</a>', 'l@g.c', '$2y$10$jtGkIlrGa9kRqMqKgaKpCOdLKpanhtOa4RIUohMHAsx46.TGQA.V2', 'test', '2025-04-06 19:09:21', 0, 0, NULL, NULL),
(37, 'test1', 'test@test12', '$2y$10$GLOV5dUF7ETtSLS7CyuT/u8irDerL96kP4ThhHBZjMrayz24lR/0u', 'test', '2025-09-13 08:42:31', 0, 0, NULL, '127.0.0.1');

-- --------------------------------------------------------

--
-- Table structure for table `user_author_preferences`
--

CREATE TABLE `user_author_preferences` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `preferred_author` varchar(255) NOT NULL,
  `preference_score` float DEFAULT 1,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_author_preferences`
--

INSERT INTO `user_author_preferences` (`id`, `username`, `preferred_author`, `preference_score`, `last_updated`) VALUES
(1, 'test1', 'test', 22, '2025-09-13 09:38:36'),
(9, 'test1', 'Lenart Lindic', 3, '2025-09-13 09:22:24'),
(10, 'test1', 'Admin', 3, '2025-09-13 09:22:24');

-- --------------------------------------------------------

--
-- Table structure for table `user_behavior_clusters`
--

CREATE TABLE `user_behavior_clusters` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `cluster_id` int(11) NOT NULL,
  `cluster_confidence` float DEFAULT 0,
  `behavior_vector` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`behavior_vector`)),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_engagement_patterns`
--

CREATE TABLE `user_engagement_patterns` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `time_of_day` tinyint(4) NOT NULL,
  `day_of_week` tinyint(4) NOT NULL,
  `engagement_score` float DEFAULT 1,
  `session_duration` int(11) DEFAULT 0,
  `scroll_depth` float DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_hashtag_interests`
--

CREATE TABLE `user_hashtag_interests` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `hashtag` varchar(255) NOT NULL,
  `weight` float DEFAULT 1,
  `interaction_count` int(11) DEFAULT 1,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_hashtag_interests`
--

INSERT INTO `user_hashtag_interests` (`id`, `username`, `hashtag`, `weight`, `interaction_count`, `last_updated`) VALUES
(1, 'test1', 'hello', 46.5, 17, '2025-09-13 09:38:36');

-- --------------------------------------------------------

--
-- Table structure for table `user_likes`
--

CREATE TABLE `user_likes` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `post_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_likes`
--

INSERT INTO `user_likes` (`id`, `username`, `post_id`, `created_at`) VALUES
(6, 'test1', 26, '2025-09-13 09:38:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `advanced_user_interactions`
--
ALTER TABLE `advanced_user_interactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_type` (`username`,`interaction_type`),
  ADD KEY `idx_post_interaction` (`post_id`,`interaction_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `hashtag_cooccurrence`
--
ALTER TABLE `hashtag_cooccurrence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_pair` (`hashtag1`,`hashtag2`),
  ADD KEY `idx_hashtag1` (`hashtag1`),
  ADD KEY `idx_hashtag2` (`hashtag2`);

--
-- Indexes for table `hashtag_semantics`
--
ALTER TABLE `hashtag_semantics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `hashtag` (`hashtag`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_popularity` (`popularity_trend`),
  ADD KEY `idx_quality` (`quality_score`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `post_hashtags`
--
ALTER TABLE `post_hashtags`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_post_hashtag` (`post_id`,`hashtag`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_hashtag` (`hashtag`);

--
-- Indexes for table `reported_posts`
--
ALTER TABLE `reported_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_author_preferences`
--
ALTER TABLE `user_author_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_author_pref` (`username`,`preferred_author`),
  ADD KEY `idx_username_author` (`username`,`preferred_author`);

--
-- Indexes for table `user_behavior_clusters`
--
ALTER TABLE `user_behavior_clusters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_cluster` (`username`),
  ADD KEY `idx_cluster` (`cluster_id`);

--
-- Indexes for table `user_engagement_patterns`
--
ALTER TABLE `user_engagement_patterns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_time` (`username`,`time_of_day`),
  ADD KEY `idx_user_day` (`username`,`day_of_week`);

--
-- Indexes for table `user_hashtag_interests`
--
ALTER TABLE `user_hashtag_interests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_hashtag_interest` (`username`,`hashtag`),
  ADD KEY `idx_username_hashtag` (`username`,`hashtag`),
  ADD KEY `idx_hashtag` (`hashtag`);

--
-- Indexes for table `user_likes`
--
ALTER TABLE `user_likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`username`,`post_id`),
  ADD KEY `idx_post_id` (`post_id`),
  ADD KEY `idx_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `advanced_user_interactions`
--
ALTER TABLE `advanced_user_interactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hashtag_cooccurrence`
--
ALTER TABLE `hashtag_cooccurrence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hashtag_semantics`
--
ALTER TABLE `hashtag_semantics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `post_hashtags`
--
ALTER TABLE `post_hashtags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `reported_posts`
--
ALTER TABLE `reported_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `user_author_preferences`
--
ALTER TABLE `user_author_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_behavior_clusters`
--
ALTER TABLE `user_behavior_clusters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_engagement_patterns`
--
ALTER TABLE `user_engagement_patterns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_hashtag_interests`
--
ALTER TABLE `user_hashtag_interests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `user_likes`
--
ALTER TABLE `user_likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
