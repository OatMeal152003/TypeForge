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
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `typing_texts`
--

LOCK TABLES `typing_texts` WRITE;
/*!40000 ALTER TABLE `typing_texts` DISABLE KEYS */;
INSERT INTO `typing_texts` VALUES (1,'quote','intermediate','Optimism & Hardware','Programming is the race between software engineers who strive to build bigger and better idiot-proof programs and the Universe trying to produce bigger and better idiots. So far, the Universe is winning.',29,1,'2026-09-22 07:12:28'),(2,'quote','advanced','Clean Code Discipline','Any fool can write code that a computer can understand. Good programmers write code that humans can understand. Simplicity is prerequisite for reliability.',22,1,'2026-09-22 07:12:28'),(3,'code','intermediate','PHP Prepared PDO Statement','<?php $stmt = $pdo->prepare(\"SELECT id, username FROM users WHERE status = :status\"); $stmt->execute([\"status\" => \"active\"]); $users =$stmt->fetchAll(); ?>',22,1,'2026-09-22 07:12:28'),(4,'code','advanced','JavaScript Modern Fetch','async function fetchMetrics(userId) { try { const response = await fetch(`/api/stats.php?id=${userId}`); return await response.json(); } catch (err) { console.error(err); } }',24,1,'2026-09-22 07:12:28'),(5,'words','beginner','Common Core Words','system scale velocity database function array focus index query dynamic execute server client stream memory render engine logic modern simple quick',22,1,'2026-09-22 07:12:28'),(6,'quote','beginner','My Family','My family is very important to me. We spend time together, help each other, and share many happy moments. Every member of my family has a different personality, but we always try to understand and support one another.\r\n\r\nAt home, we help with simple responsibilities such as cleaning, preparing food, and organizing our things. During our free time, we may watch television, talk about our day, or simply relax together. These small moments make our family closer.\r\n\r\nFamily teaches us important values such as kindness, respect, patience, and responsibility. Even when we have problems, we can learn to communicate and solve them together.',103,1,'2026-09-30 07:09:40'),(7,'quote','intermediate','The Importance of Reading','Reading is a useful habit that can improve a person\'s knowledge and imagination. Books, articles, and other reading materials can introduce us to different ideas, places, cultures, and experiences.\r\n\r\nWhen we read regularly, we can learn new words and improve our ability to understand information. Reading can also help students perform better when writing essays, answering questions, or preparing for presentations.\r\n\r\nA good reading habit does not require many hours every day. Even a few minutes of reading can be helpful when done consistently. The important thing is to choose something interesting and make reading part of your daily routine.',100,1,'2026-09-30 07:10:05'),(8,'quote','intermediate','Critical Thinking and Problem Solving','Critical thinking involves carefully examining information before accepting a conclusion. Instead of immediately believing every statement, a critical thinker considers evidence, identifies assumptions, compares different perspectives, and determines whether the available information is reliable.\r\n\r\nProblem solving requires a similar approach. A difficult problem can often be divided into smaller parts so that each issue can be examined separately. After identifying possible solutions, a person can compare their advantages and disadvantages before deciding what action to take.\r\n\r\nThese abilities are useful in both academic and everyday situations. Whether a student is completing a research project, resolving a disagreement, or making an important decision, careful reasoning can help prevent unnecessary mistakes and produce more thoughtful results.',114,1,'2026-09-30 07:10:32'),(9,'code','beginner','Simple Calculation','first_number = 10\r\nsecond_number = 5\r\n\r\nsum_result = first_number + second_number\r\ndifference = first_number - second_number\r\n\r\nprint(\"Sum:\", sum_result)\r\nprint(\"Difference:\", difference)',20,1,'2026-09-30 07:11:25'),(10,'code','intermediate','Functions','def calculate_average(first, second, third):\r\n    total = first + second + third\r\n    average = total / 3\r\n    return average\r\n\r\nresult = calculate_average(85, 90, 88)\r\n\r\nprint(\"Average:\", result)',25,1,'2026-09-30 07:12:03'),(11,'words','beginner','Technology Words','computer keyboard monitor software hardware internet website application database network browser program digital information technology',15,1,'2026-09-30 07:13:29'),(12,'words','intermediate','Academic Words','knowledge development responsibility environment education opportunity experience improvement creativity collaboration organization communication achievement understanding preparation',15,1,'2026-09-30 07:13:51'),(13,'words','advanced','Expert Words','characterization computational interdisciplinary authentication synchronization telecommunications configuration implementation interoperability cybersecurity documentation accessibility troubleshooting optimization sustainability',15,1,'2026-09-30 07:14:16');
/*!40000 ALTER TABLE `typing_texts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `achievements`
--

LOCK TABLES `achievements` WRITE;
/*!40000 ALTER TABLE `achievements` DISABLE KEYS */;
INSERT INTO `achievements` VALUES (1,'FIRST_STEP','First Step','Complete your very first typing test on TypeForge.','fa-flag-checkered','tests_count',1.00,10,1),(2,'SPEED_STARTER','Speed Starter','Reach a speed of 30 Words Per Minute.','fa-bolt','wpm_reach',30.00,20,1),(3,'SPEED_MASTER','Speed Master','Reach a speed of 60 Words Per Minute.','fa-gauge-high','wpm_reach',60.00,50,1),(4,'KEYBOARD_WARRIOR','Keyboard Warrior','Complete 50 individual typing tests.','fa-keyboard','tests_count',50.00,100,1),(5,'ACCURACY_EXPERT','Accuracy Expert','Achieve 98% accuracy or higher in any completed test.','fa-bullseye','accuracy_reach',98.00,40,1),(6,'SPEED_DEMON','Speed Demon','Reach 80 words per minute in any test.','fa-fire','wpm_reach',80.00,60,1),(7,'MARATHON','Marathon','Complete 100 typing tests.','fa-road','tests_count',100.00,80,1),(8,'PERFECTIONIST','Perfectionist','Finish any test with 100% accuracy.','fa-gem','accuracy_reach',100.00,70,1),(9,'STREAK_7','Week Warrior','Type on 7 consecutive days.','fa-calendar-check','streak_days',7.00,90,1),(10,'INVADER_SLAYER','Invader Slayer','Score 500 in a single Pixel Invaders run.','fa-rocket','invaders_score',500.00,70,1),(11,'BLOCK_SMASHER','Block Smasher','Score 300 in a single Block Drop run.','fa-cubes','blocks_score',300.00,70,1);
/*!40000 ALTER TABLE `achievements` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-02 12:03:05
