-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: studenthub
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
-- Current Database: `studenthub`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `studenthub` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;

USE `studenthub`;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_role` varchar(20) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,'khushi alpesh patel','Admin','ADMIN_DASHBOARD_VIEW','Admin opened the dashboard.','::1','2026-10-22 12:36:25'),(2,1,'khushi alpesh patel','Admin','ADMIN_DASHBOARD_VIEW','Admin opened the dashboard.','::1','2026-10-22 12:37:31'),(3,1,'khushi alpesh patel','Admin','ADMIN_DASHBOARD_VIEW','Admin opened the dashboard.','::1','2026-10-22 12:55:03'),(4,1,'khushi alpesh patel','Admin','EVENT_CREATED','Created event: rsetdf','::1','2026-10-22 12:55:24'),(5,1,'khushi alpesh patel','Admin','EVENT_UPDATED','Updated event ID: 9, Title: rsetdf','::1','2026-10-22 12:55:35'),(6,1,'khushi alpesh patel','Admin','EVENT_DELETED','Deleted event ID: 9','::1','2026-10-22 12:55:41'),(7,1,'khushi alpesh patel','Admin','ADMIN_DASHBOARD_VIEW','Admin opened the dashboard.','::1','2026-10-23 07:30:22'),(8,1,'khushi alpesh patel','Admin','ADMIN_DASHBOARD_VIEW','Admin opened the dashboard.','::1','2026-10-23 07:36:08'),(9,1,'khushi alpesh patel','Admin','LOGOUT','User logged out: khushipatel7102@gmail.com','::1','2026-10-23 09:01:59'),(10,5,'khushi alpesh patel','Student','EVENT_REGISTERED','Registered for event ID: 1','::1','2026-10-23 09:14:52'),(11,5,'khushi alpesh patel','Student','LOGOUT','User logged out: khuship456@gmail.com','::1','2026-10-23 09:15:58'),(12,1,'khushi alpesh patel','Admin','ADMIN_DASHBOARD_VIEW','Admin opened the dashboard.','::1','2026-10-23 09:16:56'),(13,1,'khushi alpesh patel','Admin','STUDENT_ADDED','Added student: khushipa765@gmail.com','::1','2026-10-23 09:18:20'),(14,1,'khushi alpesh patel','Admin','STUDENT_UPDATED','Updated student ID: 6','::1','2026-10-23 09:18:54'),(15,1,'khushi alpesh patel','Admin','STUDENT_DEACTIVATED','Deactivated student ID: 6','::1','2026-10-23 09:19:21'),(16,1,'khushi alpesh patel','Admin','CSV_EXPORT','Exported student records to CSV.','::1','2026-10-23 09:19:57'),(17,1,'khushi alpesh patel','Admin','ADMIN_DASHBOARD_VIEW','Admin opened the dashboard.','::1','2026-10-23 09:20:38'),(18,1,'khushi alpesh patel','Admin','EVENT_CREATED','Created event: BIOS hackathon','::1','2026-10-23 09:22:38'),(19,1,'khushi alpesh patel','Admin','ADMIN_DASHBOARD_VIEW','Admin opened the dashboard.','::1','2026-10-23 09:31:18'),(20,1,'khushi alpesh patel','Admin','STUDENT_ADDED','Added student: xss@test.com','::1','2026-10-23 09:34:20'),(21,1,'khushi alpesh patel','Admin','STUDENT_DEACTIVATED','Deactivated student ID: 7','::1','2026-10-23 09:34:36'),(22,1,'khushi alpesh patel','Admin','LOGOUT','User logged out: khushipatel7102@gmail.com','::1','2026-10-23 09:35:01'),(23,1,'khushi alpesh patel','Admin','ADMIN_DASHBOARD_VIEW','Admin opened the dashboard.','::1','2026-10-23 11:47:15'),(24,1,'khushi alpesh patel','Admin','LOGIN_SUCCESS','User logged in: khushipatel7102@gmail.com','::1','2026-10-23 12:20:11'),(25,1,'khushi alpesh patel','Admin','ADMIN_DASHBOARD_VIEW','Admin opened the dashboard.','::1','2026-10-23 12:20:11'),(26,1,'khushi alpesh patel','Admin','LOGOUT','User logged out: khushipatel7102@gmail.com','::1','2026-10-23 12:27:41');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_name` varchar(100) NOT NULL,
  `event_date` datetime NOT NULL,
  `venue` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  `poster` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'Web Development Workshop','2026-10-10 00:00:00','Computer Lab 1','Introduction to modern web development','Upcoming','uploads/events/44805f7cc1f1e703c0736cc1d71654e6.png'),(2,'Coding Competition','2026-10-15 00:00:00','Seminar Hall','Programming and problem solving competition','Upcoming','uploads/events/195a6a293cce135d50d54a13e12c47fc.png'),(3,'AI Seminar','2026-10-20 00:00:00','Auditorium(2nd floor rpcp)','Introduction to Artificial Intelligence','Upcoming','uploads/events/97b55066995b91aecf1c5ac79475cc7d.png'),(8,'AI Seminar','2026-10-23 08:00:00','Auditorium(2nd floor rpcp)','ee','Upcoming',NULL),(9,'BIOS hackathon','2026-10-24 11:20:00','TIET','THIS IS AN NATIONAL LEVEL HACKATHON','Upcoming','uploads/events/74f9183d058ead95998d31083051bb98.png');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrations`
--

DROP TABLE IF EXISTS `registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registrations` (
  `registration_id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'Registered',
  PRIMARY KEY (`registration_id`),
  UNIQUE KEY `uq_student_event` (`student_id`,`event_id`),
  KEY `event_id` (`event_id`),
  CONSTRAINT `registrations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `registrations_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrations`
--

LOCK TABLES `registrations` WRITE;
/*!40000 ALTER TABLE `registrations` DISABLE KEYS */;
INSERT INTO `registrations` VALUES (1,2,1,'2026-10-23 12:58:01','Registered'),(2,3,1,'2026-10-23 12:58:01','Registered'),(3,4,1,'2026-10-23 12:58:01','Registered'),(4,5,1,'2026-10-23 14:44:52','Registered');
/*!40000 ALTER TABLE `registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mobile` varchar(10) NOT NULL,
  `password` varchar(255) NOT NULL,
  `course` varchar(10) NOT NULL,
  `year` tinyint(4) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `terms_accepted` tinyint(1) NOT NULL DEFAULT 1,
  `role` enum('Admin','Student') NOT NULL DEFAULT 'Student',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'khushi alpesh patel','khushipatel7102@gmail.com','9274135852','$2y$10$69cT00czffykH5BYqBlVOODY3nJr7SAdJqwTcCUzhSvDplRB3DYmG','CSE',2,'Female',1,'Admin','2026-10-13 18:15:08','active'),(2,'rahul','khushipatel71002@gmail.com','9274135852','$2y$10$69cT00czffykH5BYqBlVOODY3nJr7SAdJqwTcCUzhSvDplRB3DYmG','CSE',2,'Female',1,'Student','2026-10-13 18:15:08','active'),(3,'Khushi Updated Student','khushi.test.2026@example.com','9876543210','$2y$10$W1PrYeZSa0LS7uUY.TpgUuj7McfzshFhU5l5oCfurG.XEK9Pyb.8K','CSE',2,'Female',1,'Student','2026-10-13 20:15:28','active'),(4,'khushi alpesh patel','25cs065@charusat.edu.in','9274135856','$2y$10$Gd/0K4WONuEwHX8M4toWqu..sqg/8uLDbMzRN6lTYBfGa5VktVaCW','CSE',2,'Female',1,'Student','2026-10-22 12:39:23','active'),(5,'khushi alpesh patel','khuship456@gmail.com','9273456754','$2y$10$jJoF507J.UdRQ1KSpkFUcey/BqoiB3StMEeNZnWr4aJYAad.t8zEq','IT',2,'Female',1,'Student','2026-10-23 09:09:16','active'),(6,'khushi@','khushipa765@gmail.com','9274145676','$2y$10$qkug9SATqxuIiekJgsL4Au/Xm/Wa3ZWfG95gk8CATZXMwXQoCVp0W','CSE',2,'Female',1,'Student','2026-10-23 09:18:20','inactive'),(7,'<script>alert(\'XSS\')</script>','xss@test.com','9876543210','$2y$10$J.LQeW3xNgKEWgIZbCypo.pAfIa4AXOp7dTZ8g8AlHw2E/j097Sh.','ECE',2,'Other',1,'Student','2026-10-23 09:34:20','inactive');
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

-- Dump completed on 2026-10-23 18:14:53
