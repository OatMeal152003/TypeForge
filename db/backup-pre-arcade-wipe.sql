-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: typeforge
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `achievements`
--

DROP TABLE IF EXISTS `achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `achievements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` varchar(255) NOT NULL,
  `icon` varchar(50) NOT NULL,
  `criteria_type` enum('tests_count','wpm_reach','accuracy_reach','streak_days','invaders_score','blocks_score') NOT NULL,
  `criteria_value` decimal(8,2) NOT NULL,
  `points` int(10) unsigned NOT NULL DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_achievements_code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `achievements`
--

LOCK TABLES `achievements` WRITE;
/*!40000 ALTER TABLE `achievements` DISABLE KEYS */;
INSERT INTO `achievements` VALUES (1,'FIRST_STEP','First Step','Complete your very first typing test on TypeForge.','fa-flag-checkered','tests_count',1.00,10,1),(2,'SPEED_STARTER','Speed Starter','Reach a speed of 30 Words Per Minute.','fa-bolt','wpm_reach',30.00,20,1),(3,'SPEED_MASTER','Speed Master','Reach a speed of 60 Words Per Minute.','fa-gauge-high','wpm_reach',60.00,50,1),(4,'KEYBOARD_WARRIOR','Keyboard Warrior','Complete 50 individual typing tests.','fa-keyboard','tests_count',50.00,100,1),(5,'ACCURACY_EXPERT','Accuracy Expert','Achieve 98% accuracy or higher in any completed test.','fa-bullseye','accuracy_reach',98.00,40,1),(6,'SPEED_DEMON','Speed Demon','Reach 80 words per minute in any test.','fa-fire','wpm_reach',80.00,60,1),(7,'MARATHON','Marathon','Complete 100 typing tests.','fa-road','tests_count',100.00,80,1),(8,'PERFECTIONIST','Perfectionist','Finish any test with 100% accuracy.','fa-gem','accuracy_reach',100.00,70,1),(9,'STREAK_7','Week Warrior','Type on 7 consecutive days.','fa-calendar-check','streak_days',7.00,90,1),(10,'INVADER_SLAYER','Invader Slayer','Score 500 in a single Pixel Invaders run.','fa-rocket','invaders_score',500.00,70,1),(11,'BLOCK_SMASHER','Block Smasher','Score 300 in a single Block Drop run.','fa-cubes','blocks_score',300.00,70,1);
/*!40000 ALTER TABLE `achievements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_audit_log`
--

DROP TABLE IF EXISTS `admin_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_audit_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `action` varchar(50) NOT NULL,
  `target_type` varchar(30) NOT NULL DEFAULT 'user',
  `target_id` int(10) unsigned NOT NULL DEFAULT 0,
  `detail` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin` (`admin_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_audit_log`
--

LOCK TABLES `admin_audit_log` WRITE;
/*!40000 ALTER TABLE `admin_audit_log` DISABLE KEYS */;
INSERT INTO `admin_audit_log` VALUES (1,1,'text_deactivate','text',5,'deactivated','2026-09-30 07:03:49'),(2,1,'text_activate','text',5,'activated','2026-09-30 07:03:49'),(3,1,'text_add','text',6,'quote/beginner: My Family','2026-09-30 07:09:40'),(4,1,'text_add','text',7,'quote/intermediate: The Importance of Reading','2026-09-30 07:10:05'),(5,1,'text_add','text',8,'quote/intermediate: Critical Thinking and Problem Solving','2026-09-30 07:10:32'),(6,1,'text_add','text',9,'code/beginner: Simple Calculation','2026-09-30 07:11:25'),(7,1,'text_add','text',10,'code/intermediate: Functions','2026-09-30 07:12:03'),(8,1,'text_add','text',11,'words/beginner: Technology Words','2026-09-30 07:13:29'),(9,1,'text_add','text',12,'words/intermediate: Academic Words','2026-09-30 07:13:51'),(10,1,'text_add','text',13,'words/advanced: Expert Words','2026-09-30 07:14:16');
/*!40000 ALTER TABLE `admin_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_activity`
--

DROP TABLE IF EXISTS `daily_activity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_activity` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `activity_date` date NOT NULL,
  `tests_completed` int(10) unsigned NOT NULL DEFAULT 0,
  `total_time_seconds` int(10) unsigned NOT NULL DEFAULT 0,
  `avg_wpm` decimal(5,2) NOT NULL DEFAULT 0.00,
  `max_wpm` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_accuracy` decimal(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_daily` (`user_id`,`activity_date`),
  CONSTRAINT `fk_daily_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_activity`
--

LOCK TABLES `daily_activity` WRITE;
/*!40000 ALTER TABLE `daily_activity` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_activity` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `ip` varchar(45) NOT NULL,
  `username` varchar(150) NOT NULL,
  `attempts` int(10) unsigned NOT NULL DEFAULT 1,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ip`,`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
INSERT INTO `login_attempts` VALUES ('::1','081200',3,'2026-10-01 07:17:30'),('::1','ad',1,'2026-10-01 08:36:07');
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_tokens`
--

DROP TABLE IF EXISTS `login_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_token` (`token_hash`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_tokens`
--

LOCK TABLES `login_tokens` WRITE;
/*!40000 ALTER TABLE `login_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reset` (`token_hash`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
INSERT INTO `password_resets` VALUES (1,2,'e280117769763d1e4c7aef4a9dfcf14a5e3b3d48b3932e3579958033b14a720a','2026-09-30 17:27:38',1,'2026-09-30 08:27:38');
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `typing_sessions`
--

DROP TABLE IF EXISTS `typing_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `typing_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `text_id` int(10) unsigned DEFAULT NULL,
  `mode` enum('time','words','quote','custom','sprint','survival','memory','boss','invaders','blocks') NOT NULL,
  `mode_value` int(10) unsigned NOT NULL,
  `has_punctuation` tinyint(1) NOT NULL DEFAULT 0,
  `has_numbers` tinyint(1) NOT NULL DEFAULT 0,
  `wpm` decimal(5,2) NOT NULL,
  `raw_wpm` decimal(5,2) NOT NULL,
  `game_score` int(10) unsigned DEFAULT NULL,
  `accuracy` decimal(5,2) NOT NULL,
  `duration_seconds` int(10) unsigned NOT NULL,
  `total_chars` int(10) unsigned NOT NULL,
  `correct_chars` int(10) unsigned NOT NULL,
  `incorrect_chars` int(10) unsigned NOT NULL,
  `corrected_errors` int(10) unsigned NOT NULL DEFAULT 0,
  `key_errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`key_errors`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sessions_text` (`text_id`),
  KEY `idx_sessions_user_date` (`user_id`,`created_at`),
  KEY `idx_sessions_leaderboard` (`wpm`,`accuracy`),
  CONSTRAINT `fk_sessions_text` FOREIGN KEY (`text_id`) REFERENCES `typing_texts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `typing_sessions`
--

LOCK TABLES `typing_sessions` WRITE;
/*!40000 ALTER TABLE `typing_sessions` DISABLE KEYS */;
INSERT INTO `typing_sessions` VALUES (1,3,NULL,'words',0,0,0,55.00,59.00,NULL,93.00,25,129,120,9,0,NULL,'2026-09-22 08:16:07'),(2,3,NULL,'words',25,0,0,50.00,50.00,NULL,100.00,29,129,129,0,0,NULL,'2026-09-22 08:53:19'),(3,3,NULL,'time',60,0,0,48.00,48.00,NULL,100.00,60,268,268,0,0,NULL,'2026-09-22 08:54:29'),(4,3,NULL,'words',25,0,0,58.00,58.00,NULL,100.00,25,128,128,0,0,NULL,'2026-09-22 09:03:05'),(5,3,NULL,'time',15,0,0,41.00,45.00,NULL,98.00,15,67,66,1,0,NULL,'2026-09-22 09:22:53'),(6,3,NULL,'words',25,0,0,52.00,52.00,NULL,100.00,30,130,130,0,0,NULL,'2026-09-30 07:32:02'),(7,3,7,'quote',25,0,0,44.00,47.00,NULL,98.88,184,716,708,8,0,NULL,'2026-09-30 07:36:11'),(8,2,NULL,'words',15,0,0,64.00,65.00,NULL,100.00,15,81,81,0,0,NULL,'2026-09-30 08:30:12'),(9,2,NULL,'words',15,0,0,54.00,54.00,NULL,100.00,20,90,90,0,0,NULL,'2026-10-01 04:20:38'),(10,4,NULL,'words',25,0,0,26.00,27.00,NULL,100.00,60,134,134,0,0,NULL,'2026-10-01 07:19:44'),(11,4,NULL,'words',25,0,0,28.00,29.00,NULL,99.25,55,134,133,1,0,NULL,'2026-10-01 07:20:48'),(13,3,NULL,'survival',5,0,0,2.00,2.00,5,100.00,5,1,1,0,0,NULL,'2026-10-01 09:18:09'),(14,3,NULL,'survival',22,0,0,0.00,1.00,5,100.00,22,1,1,0,0,NULL,'2026-10-01 09:18:33'),(15,3,NULL,'survival',12,0,0,1.00,1.00,5,100.00,12,1,1,0,0,NULL,'2026-10-01 09:18:52'),(16,3,NULL,'words',25,0,0,68.00,68.00,NULL,100.00,25,142,142,0,0,NULL,'2026-10-01 09:34:35'),(17,3,NULL,'words',25,0,0,73.00,73.00,NULL,100.00,21,128,128,0,0,NULL,'2026-10-01 09:36:33'),(18,3,NULL,'words',25,0,0,73.00,74.00,NULL,100.00,21,129,129,0,0,NULL,'2026-10-01 09:37:44'),(19,3,NULL,'words',25,0,0,71.00,74.00,NULL,99.30,23,142,141,1,0,NULL,'2026-10-01 09:38:59'),(20,3,NULL,'words',25,0,0,70.00,71.00,NULL,100.00,23,136,136,0,0,NULL,'2026-10-01 09:39:34'),(21,3,NULL,'survival',3,0,0,4.00,4.00,5,100.00,3,1,1,0,0,NULL,'2026-10-02 03:10:00');
/*!40000 ALTER TABLE `typing_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `typing_texts`
--

DROP TABLE IF EXISTS `typing_texts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `typing_texts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` enum('words','quote','code','custom') NOT NULL,
  `difficulty` enum('beginner','intermediate','advanced') NOT NULL DEFAULT 'intermediate',
  `title` varchar(150) NOT NULL DEFAULT 'Untitled Passage',
  `content` text NOT NULL,
  `length_words` int(10) unsigned NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_texts_lookup` (`category`,`difficulty`,`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `typing_texts`
--

LOCK TABLES `typing_texts` WRITE;
/*!40000 ALTER TABLE `typing_texts` DISABLE KEYS */;
INSERT INTO `typing_texts` VALUES (1,'quote','intermediate','Optimism & Hardware','Programming is the race between software engineers who strive to build bigger and better idiot-proof programs and the Universe trying to produce bigger and better idiots. So far, the Universe is winning.',29,1,'2026-09-22 07:12:28'),(2,'quote','advanced','Clean Code Discipline','Any fool can write code that a computer can understand. Good programmers write code that humans can understand. Simplicity is prerequisite for reliability.',22,1,'2026-09-22 07:12:28'),(3,'code','intermediate','PHP Prepared PDO Statement','<?php $stmt = $pdo->prepare(\"SELECT id, username FROM users WHERE status = :status\"); $stmt->execute([\"status\" => \"active\"]); $users =$stmt->fetchAll(); ?>',22,1,'2026-09-22 07:12:28'),(4,'code','advanced','JavaScript Modern Fetch','async function fetchMetrics(userId) { try { const response = await fetch(`/api/stats.php?id=${userId}`); return await response.json(); } catch (err) { console.error(err); } }',24,1,'2026-09-22 07:12:28'),(5,'words','beginner','Common Core Words','system scale velocity database function array focus index query dynamic execute server client stream memory render engine logic modern simple quick',22,1,'2026-09-22 07:12:28'),(6,'quote','beginner','My Family','My family is very important to me. We spend time together, help each other, and share many happy moments. Every member of my family has a different personality, but we always try to understand and support one another.\r\n\r\nAt home, we help with simple responsibilities such as cleaning, preparing food, and organizing our things. During our free time, we may watch television, talk about our day, or simply relax together. These small moments make our family closer.\r\n\r\nFamily teaches us important values such as kindness, respect, patience, and responsibility. Even when we have problems, we can learn to communicate and solve them together.',103,1,'2026-09-30 07:09:40'),(7,'quote','intermediate','The Importance of Reading','Reading is a useful habit that can improve a person\'s knowledge and imagination. Books, articles, and other reading materials can introduce us to different ideas, places, cultures, and experiences.\r\n\r\nWhen we read regularly, we can learn new words and improve our ability to understand information. Reading can also help students perform better when writing essays, answering questions, or preparing for presentations.\r\n\r\nA good reading habit does not require many hours every day. Even a few minutes of reading can be helpful when done consistently. The important thing is to choose something interesting and make reading part of your daily routine.',100,1,'2026-09-30 07:10:05'),(8,'quote','intermediate','Critical Thinking and Problem Solving','Critical thinking involves carefully examining information before accepting a conclusion. Instead of immediately believing every statement, a critical thinker considers evidence, identifies assumptions, compares different perspectives, and determines whether the available information is reliable.\r\n\r\nProblem solving requires a similar approach. A difficult problem can often be divided into smaller parts so that each issue can be examined separately. After identifying possible solutions, a person can compare their advantages and disadvantages before deciding what action to take.\r\n\r\nThese abilities are useful in both academic and everyday situations. Whether a student is completing a research project, resolving a disagreement, or making an important decision, careful reasoning can help prevent unnecessary mistakes and produce more thoughtful results.',114,1,'2026-09-30 07:10:32'),(9,'code','beginner','Simple Calculation','first_number = 10\r\nsecond_number = 5\r\n\r\nsum_result = first_number + second_number\r\ndifference = first_number - second_number\r\n\r\nprint(\"Sum:\", sum_result)\r\nprint(\"Difference:\", difference)',20,1,'2026-09-30 07:11:25'),(10,'code','intermediate','Functions','def calculate_average(first, second, third):\r\n    total = first + second + third\r\n    average = total / 3\r\n    return average\r\n\r\nresult = calculate_average(85, 90, 88)\r\n\r\nprint(\"Average:\", result)',25,1,'2026-09-30 07:12:03'),(11,'words','beginner','Technology Words','computer keyboard monitor software hardware internet website application database network browser program digital information technology',15,1,'2026-09-30 07:13:29'),(12,'words','intermediate','Academic Words','knowledge development responsibility environment education opportunity experience improvement creativity collaboration organization communication achievement understanding preparation',15,1,'2026-09-30 07:13:51'),(13,'words','advanced','Expert Words','characterization computational interdisciplinary authentication synchronization telecommunications configuration implementation interoperability cybersecurity documentation accessibility troubleshooting optimization sustainability',15,1,'2026-09-30 07:14:16');
/*!40000 ALTER TABLE `typing_texts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_achievements`
--

DROP TABLE IF EXISTS `user_achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_achievements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `achievement_id` int(10) unsigned NOT NULL,
  `unlocked_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_achievement` (`user_id`,`achievement_id`),
  KEY `fk_ua_achievement` (`achievement_id`),
  CONSTRAINT `fk_ua_achievement` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ua_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_achievements`
--

LOCK TABLES `user_achievements` WRITE;
/*!40000 ALTER TABLE `user_achievements` DISABLE KEYS */;
INSERT INTO `user_achievements` VALUES (5,3,1,'2026-10-01 09:18:09'),(6,3,2,'2026-10-01 09:18:09'),(7,3,5,'2026-10-01 09:18:09'),(8,3,8,'2026-10-01 09:18:09'),(9,3,3,'2026-10-01 09:34:35');
/*!40000 ALTER TABLE `user_achievements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_settings`
--

DROP TABLE IF EXISTS `user_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `theme` varchar(30) NOT NULL DEFAULT 'dark-emerald',
  `default_test_mode` enum('time','words','quote','custom') NOT NULL DEFAULT 'time',
  `default_test_mode_value` int(10) unsigned NOT NULL DEFAULT 30,
  `sound_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `live_wpm_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_user` (`user_id`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_settings`
--

LOCK TABLES `user_settings` WRITE;
/*!40000 ALTER TABLE `user_settings` DISABLE KEYS */;
INSERT INTO `user_settings` VALUES (1,1,'dark','time',30,1,1,'2026-10-02 02:56:32'),(2,2,'dark-emerald','time',30,1,1,'2026-09-22 07:12:28'),(3,3,'dark','time',30,1,1,'2026-10-01 06:24:36'),(13,4,'dark-emerald','time',30,1,1,'2026-10-01 07:18:26'),(19,5,'dark-emerald','time',30,1,1,'2026-10-02 02:26:51');
/*!40000 ALTER TABLE `user_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `profile_picture` varchar(255) NOT NULL DEFAULT 'default.png',
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `status` enum('active','suspended','banned') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role_status` (`role`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin','admin@typeforge.dev','$2y$10$zOoID5tUAywyGGJjyIlqbOwKkL7bPb/3nsgnWQbyIchi7kIWWP7s6','uploads/avatars/957a7a3e43ed19a74b87a4df8417171a.jpg','admin','active','2026-09-22 07:12:28','2026-09-30 07:15:15'),(2,'Othniel Sulpico','othniel','othniel@typeforge.dev','$2y$10$UX0qR52KqiI3OSuo/Rxyi.d8XW9nu3lw4vTsWndW5prJ9jjodd.56','uploads/avatars/fda00c990cbd9ab9ff6ed23d4df536fd.jpg','user','active','2026-09-22 07:12:28','2026-09-30 08:30:48'),(3,'Jephthah Soriano','jephthah0212','jephthah@gmail.com','$2y$10$ibNP/ec69U7M0FUKs/PuS.q9e.lti5WbNel9sCS60Y5AZ3pxUdmnC','uploads/avatars/666b2a02efa3b8c5158b14819cefa0de.jpg','user','active','2026-09-22 08:15:30','2026-09-30 07:21:00'),(4,'hitler adolf','hitler','hitler@gmail.com','$2y$10$vYMXVjNN13AGazkp5WWMaehlzIcaBtDyX5Q1p9FMClS209R4N16.q','default.png','user','active','2026-10-01 07:18:26','2026-10-01 07:18:26'),(5,'IshowSpeed','speed','ishowspeed@typeforge.com','$2y$10$DEDBV5UEodRoz1kTEjEjtejSL9w0k69dK0DIUYzJ.D5F2cMMGZG32','default.png','user','active','2026-10-02 02:26:51','2026-10-02 02:26:51');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-02 11:33:46
