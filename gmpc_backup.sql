-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: gmpc_cms
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_permissions`
--

DROP TABLE IF EXISTS `admin_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `resource_type` enum('page','event','sermon','community','banner') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'page',
  `resource_id` int NOT NULL,
  `can_edit` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_permission` (`admin_id`,`resource_type`,`resource_id`),
  CONSTRAINT `fk_perm_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_permissions`
--

LOCK TABLES `admin_permissions` WRITE;
/*!40000 ALTER TABLE `admin_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `admin_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `appointment` varchar(100) DEFAULT NULL,
  `role` enum('super_admin','admin','editor') DEFAULT 'editor',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','admin@garrisonmpc.org','$2y$10$RnDM/oCyn.je0qeSRVwqHuW7f0LN4e6N.r..sYhHL1bSnG3O4OxYu','System Administrator',NULL,NULL,NULL,'super_admin','active','2025-12-03 17:14:06','2025-11-27 13:28:19','2025-12-03 17:14:06'),(2,'kwame_wilder','kwame.wilder@gmail.com','$2y$10$EovT550wkPN4r5K5Dm2CqO1XJ9ThjL6fNRm65plrIXcvpDB3NZOla','Emmanuel Oheneba Gyasi-Ampomah','http://localhost/gmpc/uploads/images/avatar_2_8fc2fa5b.png','00233545667788','Developer','super_admin','active','2025-12-07 21:00:30','2025-12-01 10:18:20','2025-12-07 21:00:30');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `fk_audit_admin` (`admin_id`),
  CONSTRAINT `fk_audit_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,2,'profile_update','admin',2,'Updated profile details','::1','2025-12-03 21:08:05'),(2,2,'update','page',3,'Updated page Contact Us','::1','2025-12-03 21:30:28'),(3,2,'update','event',3,'Updated event Carols Night','::1','2025-12-05 19:40:48'),(4,2,'profile_update','admin',2,'Updated profile details','::1','2025-12-05 20:23:11'),(5,2,'update','page',4,'Updated page Church History','::1','2025-12-06 20:32:49'),(6,2,'update','page',4,'Updated page Church History','::1','2025-12-06 20:32:54'),(7,2,'update','page',4,'Updated page Church History','::1','2025-12-06 20:34:39'),(8,2,'update','page',4,'Updated page Church History','::1','2025-12-06 20:36:04'),(9,2,'update','page',4,'Updated page Church History','::1','2025-12-06 20:40:39'),(10,2,'update','page',4,'Updated page Church History','::1','2025-12-06 20:42:03'),(11,2,'update','page',4,'Updated page Church History','::1','2025-12-06 20:48:59'),(12,2,'update','page',4,'Updated page Church History','::1','2025-12-06 20:58:40'),(13,2,'update','page',4,'Updated page Church History','::1','2025-12-06 20:59:53'),(14,2,'update','page',4,'Updated page Church History','::1','2025-12-06 21:03:06'),(15,2,'update','page',4,'Updated page Church History','::1','2025-12-06 21:12:04'),(16,2,'update','page',4,'Updated page Church History','::1','2025-12-06 21:18:35'),(17,2,'update','page',4,'Updated page Church History','::1','2025-12-06 21:19:30'),(18,2,'update','page',4,'Updated page Church History','::1','2025-12-06 21:20:43');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communities`
--

DROP TABLE IF EXISTS `communities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `communities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text,
  `full_content` longtext,
  `meeting_day` varchar(50) DEFAULT NULL,
  `meeting_time` varchar(50) DEFAULT NULL,
  `meeting_location` varchar(100) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `display_order` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communities`
--

LOCK TABLES `communities` WRITE;
/*!40000 ALTER TABLE `communities` DISABLE KEYS */;
INSERT INTO `communities` VALUES (1,'Ark of Praise','ark-of-praise','Ark of Praise community.','',NULL,NULL,NULL,NULL,NULL,NULL,'active',10,'2025-12-03 17:36:55','2025-12-03 17:36:55'),(2,'Audio Visual Teams','audio-visual-team','Audio Visual Team community.','[{\"type\":\"heading\",\"data\":{\"level\":\"2\",\"text\":\"About the Audio Visual Team\",\"align\":\"center\"}},{\"type\":\"image\",\"data\":{\"url\":\"http://localhost/gmpc/uploads/images/211e6213ebfb9ca8.jpg\",\"caption\":\"\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"The Audio Visual Team plays a vital role in ensuring that every worship experience is clear, engaging, and impactful. We handle all sound, media, projection, livestream, and technical requirements needed to create an atmosphere where God’s Word can be heard and hearts can be reached.\\nOur desire is to serve God with our skills, creativity, and dedication, helping the church deliver excellence in every service and event.\",\"align\":\"center\"}},{\"type\":\"divider\",\"data\":{}},{\"type\":\"heading\",\"data\":{\"level\":\"2\",\"text\":\"Our Mission\",\"align\":\"center\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"To enhance worship and ministry through professional, spirit-led use of audio, video, and digital technology while creating an environment where people can worship without distraction.\",\"align\":\"center\"}},{\"type\":\"divider\",\"data\":{}},{\"type\":\"heading\",\"data\":{\"level\":2,\"text\":\"What We Do\",\"align\":\"center\"}},{\"type\":\"list\",\"data\":{\"style\":\"unordered\",\"items\":[\"Sound Engineering – Setting up microphones, mixers, speakers, and ensuring clear and balanced audio.\",\"Projection & Media – Managing lyrics, scriptures, announcements, and presentations.\",\"Livestream & Recording – Capturing services for online platforms and archiving.\",\"Lighting Control – Enhancing the worship environment through proper lighting.\",\"Technical Support – Assisting pastors, worship leaders, and departments with media needs.\",\"Equipment Management – Maintaining, organizing, and upgrading A/V equipment.\"],\"align\":\"left\"}},{\"type\":\"image\",\"data\":{\"url\":\"http://localhost/gmpc/uploads/images/726a77b09fbe7501.jpg\",\"caption\":\"\"}},{\"type\":\"heading\",\"data\":{\"level\":2,\"text\":\"Who Can Join\",\"align\":\"center\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"We welcome individuals who have:\",\"align\":\"left\"}},{\"type\":\"list\",\"data\":{\"style\":\"unordered\",\"items\":[\"A passion for technology, media, or music\",\"A willingness to learn new skills\",\"A heart to serve behind the scenes\",\"Basic technical understanding (not required but helpful)\"],\"align\":\"left\"}},{\"type\":\"divider\",\"data\":{}},{\"type\":\"heading\",\"data\":{\"level\":\"4\",\"text\":\"Training is provided for new volunteers.\",\"align\":\"center\"}}]','','','',NULL,'','','active',1,'2025-12-03 17:36:55','2025-12-06 23:21:11'),(3,'Children Service','children-service','Children Service community.','',NULL,NULL,NULL,NULL,NULL,NULL,'active',8,'2025-12-03 17:36:55','2025-12-03 17:36:55'),(4,'Choir','choir','Choir community.','',NULL,NULL,NULL,NULL,NULL,NULL,'active',2,'2025-12-03 17:36:55','2025-12-03 17:36:55'),(5,'Israel','israel','Israel community.','',NULL,NULL,NULL,NULL,NULL,NULL,'active',3,'2025-12-03 17:36:55','2025-12-03 17:36:55'),(6,'Junior Youth','junior-youth','Junior Youth community.','',NULL,NULL,NULL,NULL,NULL,NULL,'active',4,'2025-12-03 17:36:55','2025-12-03 17:36:55'),(7,'Men\'s Fellowship','mens-fellowship','Men\'s Fellowship community.','',NULL,NULL,NULL,NULL,NULL,NULL,'active',7,'2025-12-03 17:36:55','2025-12-03 17:36:55'),(8,'Women\'s Fellowship','womens-fellowship','Women\'s Fellowship community.','',NULL,NULL,NULL,NULL,NULL,NULL,'active',6,'2025-12-03 17:36:55','2025-12-03 17:36:55'),(9,'Youth Fellowship','youth-fellowship','Youth Fellowship community.','',NULL,NULL,NULL,NULL,NULL,NULL,'active',5,'2025-12-03 17:36:55','2025-12-03 17:36:55'),(10,'Singing Band','singing-band','Singing Band community.','',NULL,NULL,NULL,NULL,NULL,NULL,'active',9,'2025-12-03 17:36:55','2025-12-03 17:36:55');
/*!40000 ALTER TABLE `communities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_media`
--

DROP TABLE IF EXISTS `community_media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `community_media` (
  `id` int NOT NULL AUTO_INCREMENT,
  `community_slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `uploaded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `community_slug` (`community_slug`),
  KEY `fk_cm_admin` (`uploaded_by`),
  CONSTRAINT `fk_cm_admin` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_media`
--

LOCK TABLES `community_media` WRITE;
/*!40000 ALTER TABLE `community_media` DISABLE KEYS */;
INSERT INTO `community_media` VALUES (1,'audio-visual-team','Prayer','https://images.pexels.com/photos/2014774/pexels-photo-2014774.jpeg','',2,'2025-12-07 21:55:39'),(2,'audio-visual-team','Cross','https://images.pexels.com/photos/3073565/pexels-photo-3073565.jpeg','',2,'2025-12-07 21:57:27'),(3,'audio-visual-team','Bible reading','https://images.pexels.com/photos/1296720/pexels-photo-1296720.jpeg','',2,'2025-12-07 21:58:10'),(4,'audio-visual-team','Prayer','https://images.pexels.com/photos/2774576/pexels-photo-2774576.jpeg','',2,'2025-12-07 21:58:53'),(5,'audio-visual-team','Prayer','https://images.pexels.com/photos/5418299/pexels-photo-5418299.jpeg','',2,'2025-12-07 22:00:33');
/*!40000 ALTER TABLE `community_media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `elders`
--

DROP TABLE IF EXISTS `elders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `elders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT '',
  `email` varchar(255) DEFAULT '',
  `phone` varchar(100) DEFAULT '',
  `location` varchar(255) DEFAULT '',
  `image_url` varchar(500) DEFAULT '',
  `bio` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `elders`
--

LOCK TABLES `elders` WRITE;
/*!40000 ALTER TABLE `elders` DISABLE KEYS */;
INSERT INTO `elders` VALUES (1,'Elder Joseph Tetteh','Elder / Head of Men’s Fellowship','joseph.tetteh@gmpc.com','+233 24 119 5501','Accra, Ghana','http://localhost/gmpc/uploads/images/64dbc598d231478d.jpg','Elder Tetteh has served faithfully for over 20 years. He provides mentoring for young men, supports leadership development, and oversees church welfare initiatives.','active','2025-12-06 15:38:11','2025-12-06 15:43:54',0),(2,'Elder Joseph Tetteh','Elder / Head of Men’s Fellowship','joseph.tetteh@gmpc.com','+233 24 119 5501','Accra, Ghana','http://localhost/gmpc/uploads/images/64dbc598d231478d.jpg','Elder Tetteh has served faithfully for over 20 years. He provides mentoring for young men, supports leadership development, and oversees church welfare initiatives.','active','2025-12-06 15:42:49','2025-12-06 15:43:46',0);
/*!40000 ALTER TABLE `elders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `events`
--

DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events`
--

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
INSERT INTO `events` VALUES (1,'Sunday Worship Service','Join us for our weekly worship service with uplifting music and inspiring messages.','2025-12-01','09:00:00','Main Sanctuary','https://images.pexels.com/photos/8923734/pexels-photo-8923734.jpeg','published',1,'2025-11-28 18:39:23','2025-11-28 18:39:23'),(2,'Sample Event','Sample Event text','2025-12-01','20:50:00','Burma Camp','https://images.pexels.com/photos/27629352/pexels-photo-27629352.jpeg','published',1,'2025-11-28 18:49:03','2025-11-28 18:49:03'),(3,'Carols Night','[{\"type\":\"heading\",\"data\":{\"level\":2,\"text\":\"\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Join us for a heart-warming Carols Night, a special evening filled with music, reflection, and the joyful spirit of Christmas. The event will feature soul-lifting performances from the choir, children’s ministry, and guest singers as we come together to celebrate the birth of Christ through timeless carols and contemporary Christmas worship.\\n\\nGuests will enjoy a serene, candle-lit atmosphere, Scripture readings that recount the Christmas story, and moments of communal singing that remind us of peace, hope, and unity. Families, friends, and visitors are all invited to experience the beauty of the season and share in a night of joy and fellowship.\\n\\nCome ready to sing along, rejoice, and create unforgettable memories as we usher in the Christmas season together.\\n\\n🎄🎁❄️⭐🌟🔔🕯️⛄🎅🤶🦌🧦🍪🧁🕊️\\n\"}},{\"type\":\"image\",\"data\":{\"url\":\"/uploads/images/e47e8a076c9a3398.jpg\",\"caption\":\"\"}},{\"type\":\"location\",\"data\":{\"address\":\"Garrison Methodist Presbyterian Church, Burma Camp\",\"mapUrl\":\"\"}}]','2025-12-25','19:00:00','Church Hall','https://images.pexels.com/photos/27629352/pexels-photo-27629352.jpeg','published',2,'2025-12-03 14:49:41','2025-12-05 19:40:48'),(4,'Night of Hope: A Christmas Worship Experience','[{\"type\":\"paragraph\",\"data\":{\"text\":\"Join us for Night of Hope, a beautiful Christmas worship experience designed to bring families, friends, and the entire community together. The evening will feature:\\n\\n🎶 Live Christmas Choir Performances\\n\\n🕯️ Candlelight Worship Moment\\n\\n📖 Scripture Readings of the Nativity Story\\n\\n🌟 Drama Presentation: “The Promise Fulfilled”\\n\\n🤲 Community Giving Moment — donations collected for families in need\\n\\n🎁 Children’s Special Ministration\\n\\n☕ Post-event Refreshments (hot chocolate & snacks)\\n\\nThis is a night of joy, reflection, and celebration as we remember the birth of our Savior. All are invited to come and encounter the true meaning of Christmas—hope, light, and love.\"}},{\"type\":\"image\",\"data\":{\"url\":\"/uploads/images/d65ee057561096f7.jpg\",\"caption\":\"\"}},{\"type\":\"location\",\"data\":{\"address\":\"Garrison Methodist Presbyterian Church, Burma Camp\",\"mapUrl\":\"\"}}]','2025-12-22','18:30:00','Chapel Auditorium','','published',2,'2025-12-03 14:53:58','2025-12-03 15:18:08');
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `media` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `file_url` text NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `description` text,
  `uploaded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `media_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `media`
--

LOCK TABLES `media` WRITE;
/*!40000 ALTER TABLE `media` DISABLE KEYS */;
INSERT INTO `media` VALUES (2,'Test image','https://images.pexels.com/photos/2014773/pexels-photo-2014773.jpeg','image','This is a test image for the media page',1,'2025-12-01 12:39:22'),(6,'Sunday Church Service','https://www.youtube.com/watch?v=0oEZzb59nog','video','Sunday Church Service',2,'2025-12-05 20:36:33'),(9,'Image','http://localhost/gmpc/uploads/images/577f019f79fc2652.jpg','image','',2,'2025-12-05 20:58:06'),(16,'Mercy Chinwo - Heaven and Earth Adore You','http://localhost/gmpc/uploads/audio/1f3689739f551315.mp3','audio','',2,'2025-12-05 21:31:53');
/*!40000 ALTER TABLE `media` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `page_name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `title` varchar(200) NOT NULL,
  `meta_description` text,
  `content` longtext,
  `show_in_menu` tinyint(1) DEFAULT '0',
  `menu_order` int DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_slug` (`slug`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `pages_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'Test','test','Test','','Test page',1,1,'inactive',2,'2025-12-01 17:52:43','2025-12-01 18:58:37'),(2,'About','about','About Us','Learn about the history, mission, and beliefs of Garrison Methodist Presbyterian Church.','[{\"type\":\"heading\",\"data\":{\"level\":2,\"text\":\"Welcome to Garrison Methodist Presbyterian Church\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"We are a vibrant community of believers committed to worship, fellowship, and service.\"}},{\"type\":\"image\",\"data\":{\"url\":\"/images/hero.jpg\",\"caption\":\"Our church family\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Founded in 1892, we blend Methodist and Presbyterian traditions to honor our diverse heritage.\"}},{\"type\":\"quote\",\"data\":{\"text\":\"A community of faith, hope, and love serving God together.\",\"caption\":\"Church Motto\"}}]',1,1,'active',1,'2025-12-03 13:17:13','2025-12-03 13:17:13'),(3,'Contact Us','contact','Contact Us','Contact Garrison Methodist Presbyterian Church. Find our address, phone number, email, and service times.','[{\"type\":\"heading\",\"data\":{\"level\":2,\"text\":\"Get in Touch\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Whether you need prayer, information, or want to visit, we are here for you.\"}},{\"type\":\"list\",\"data\":{\"style\":\"unordered\",\"items\":[\"Address: Garrison Methodist Presby Church, Burma Camp, Accra\",\"Phone: (+233) 243-3689\",\"Email: info@garrisonmpc.org\"]}},{\"type\":\"button\",\"data\":{\"text\":\"Email Us\",\"url\":\"mailto:info@garrisonmpc.org\",\"style\":\"primary\"}},{\"type\":\"button\",\"data\":{\"text\":\"Call Us\",\"url\":\"tel:+2332433689\",\"style\":\"secondary\"}},{\"type\":\"location\",\"data\":{\"address\":\"Garrison Methodist Presbyterian Church, Burma Camp\",\"mapUrl\":\"\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Service Times: Sunday School 10:00 AM · Morning Worship 9:00 AM · Contemporary Service 11:00 AM\"}},{\"type\":\"quote\",\"data\":{\"text\":\"We’d love to welcome you in person.\",\"caption\":\"\"}}]',1,2,'active',1,'2025-12-03 13:17:13','2025-12-03 21:30:28'),(4,'Church History','church-history','Church History','Learn about the rich history of Garrison Methodist Presbyterian Church and how we have grown over the years.','[{\"type\":\"heading\",\"data\":{\"level\":2,\"text\":\"Welcome to Garrison Methodist Presbyterian Church\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Welcome to Garrison Methodist Presbyterian Church — a community of believers committed to worshiping God, serving one another, and impacting our community with love and grace. Whether you’re exploring faith for the first time or looking for a spiritual home, we’re glad you’re here.\"}},{\"type\":\"spacer\",\"data\":{\"height\":24}},{\"type\":\"image\",\"data\":{\"url\":\"https://upload.wikimedia.org/wikipedia/commons/thumb/4/49/Kotoka_International_Airport%2C_Accra%2C_Ghana.JPG/760px-Mapcarta.jpg\",\"caption\":\"\"}},{\"type\":\"spacer\",\"data\":{\"height\":24}},{\"type\":\"image\",\"data\":{\"url\":\"https://images.pexels.com/photos/1173794/pexels-photo-1173794.jpeg\",\"caption\":\"\"}},{\"type\":\"spacer\",\"data\":{\"height\":24}},{\"type\":\"heading\",\"data\":{\"level\":2,\"text\":\"Our Story\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"1985 – The Beginning\\nGarrison Methodist Presbyterian Church began in 1985, when a small group of believers from Garrison Estate in Takoradi realised the need for a local fellowship. Meeting first under a tree and later in a rented community hall, the early congregation was just a handful of families seeking to worship together and support one another.\\n\\n\"}},{\"type\":\"spacer\",\"data\":{\"height\":24}},{\"type\":\"image\",\"data\":{\"url\":\"https://images.pexels.com/photos/3724758/pexels-photo-3724758.jpeg\",\"caption\":\"\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"1992 – First Building Established\\nAs membership grew, the church community pooled resources and formally acquired land in Garrison Estate. With the help of dedicated volunteers and generous donations, the first modest wooden chapel was constructed — a humble yet powerful sign of commitment and faith.\\n\\n\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"2001 – Expansion and New Sanctuary\\nBy the turn of the millennium, the congregation had outgrown the wooden structure. In 2001, after several fundraising efforts, the church began building a more permanent sanctuary made of concrete and laterite. The new building was completed in late 2002 and dedicated in January 2003 with a joyful commissioning service.\\n\\n\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"2008–2015 – Deepening Ministries & Community Impact\\nThe church began several ministries — youth fellowship, women’s ministry, Sunday school for children, outreach to the poor, and community health/education drives. These ministries helped the church become a centre of spiritual growth and community support in Garrison Estate and neighbouring communities.\\n\\n\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"2020 – Present: A Vibrant, Growing Community\\nToday, Garrison Methodist Presbyterian Church has grown into a vibrant community of over 200 active members. With regular worship services, Sunday school, youth and women groups, outreach programmes, and community events, the church continues to strive toward its vision: growing believers who love God and serve their neighbours.\"}},{\"type\":\"spacer\",\"data\":{\"height\":24}},{\"type\":\"image\",\"data\":{\"url\":\"https://images.pexels.com/photos/32940344/pexels-photo-32940344.jpeg\",\"caption\":\"\"}},{\"type\":\"spacer\",\"data\":{\"height\":24}},{\"type\":\"heading\",\"data\":{\"level\":3,\"text\":\"Our Legacy — And Yours\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"The journey of Garrison Methodist Presbyterian Church is a story of faith, hope, and community. From humble beginnings under a tree to a growing sanctuary and dynamic ministries, every chapter has been written in trust, commitment, and love.\\n\\nWe believe the best is still ahead. Maybe you are part of the next chapter.\\n\\nCome be a part of our story.\"}}]',0,0,'active',1,'2025-12-03 13:17:13','2025-12-06 21:20:43'),(5,'Elders','elders','Church Elders','Meet the elders of Garrison Methodist Presbyterian Church who provide spiritual leadership and guidance.','[{\"type\":\"heading\",\"data\":{\"level\":2,\"text\":\"Our Elders\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Meet the dedicated elders providing spiritual leadership and guidance.\"}},{\"type\":\"list\",\"data\":{\"style\":\"unordered\",\"items\":[\"Elder Name One\",\"Elder Name Two\",\"Elder Name Three\"]}},{\"type\":\"quote\",\"data\":{\"text\":\"Serving with humility and faith.\",\"caption\":\"GMPC Elders\"}}]',0,0,'active',1,'2025-12-03 13:17:13','2025-12-03 13:17:13'),(6,'Pastoral Team','pastoral-team','Pastoral Team','Meet the pastoral team of Garrison Methodist Presbyterian Church who serve and lead our congregation.','[{\"type\":\"heading\",\"data\":{\"level\":2,\"text\":\"Our Pastoral Team\",\"align\":\"center\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Called to serve, equipped to lead, and passionate about ministry.\",\"align\":\"center\"}},{\"type\":\"image\",\"data\":{\"url\":\"http://localhost/gmpc/uploads/images/6969c7607ead873d.jpg\",\"caption\":\"\"}},{\"type\":\"heading\",\"data\":{\"level\":\"4\",\"text\":\"Pastor John Mensah\",\"align\":\"center\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"Pastor John has been serving our congregation for over 15 years, bringing wisdom, compassion, and biblical teaching to our community.\",\"align\":\"center\"}}]',0,0,'active',1,'2025-12-03 13:17:13','2025-12-03 20:07:21');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pastors`
--

DROP TABLE IF EXISTS `pastors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pastors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT '',
  `email` varchar(255) DEFAULT '',
  `phone` varchar(100) DEFAULT '',
  `location` varchar(255) DEFAULT '',
  `image_url` varchar(500) DEFAULT '',
  `bio` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sort_order` int DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pastors`
--

LOCK TABLES `pastors` WRITE;
/*!40000 ALTER TABLE `pastors` DISABLE KEYS */;
INSERT INTO `pastors` VALUES (1,'Pastor Linda Osei','Worship and Creative Arts Pastor','linda.osei@gmpc.com','+233 20 668 4412','Accra, Ghana','http://localhost/gmpc/uploads/images/15a74c4768ee9022.jpg','Pastor Linda oversees worship, media, and creative ministries. She is known for cultivating a genuine atmosphere of worship and mentoring young worship leaders.','active','2025-12-06 14:44:49','2025-12-06 15:19:35',5),(2,'Rev. Michael Boateng','Senior Pastor','michael.boateng@gmpc.com','+233 24 815 9031','Accra, Ghana','http://localhost/gmpc/uploads/images/54aecb97fafd84e8.jpg','Rev. Boateng is a passionate teacher of the Word with over 15 years in ministry. He leads with a strong focus on discipleship, leadership development, and community outreach.','active','2025-12-06 14:59:24','2025-12-06 15:13:37',0),(3,'Bishop Samuel Adu','Bishop / Regional Overseer','bishop.adu@gmpc.com','+233 27 911 3400','Tema, Ghana','http://localhost/gmpc/uploads/images/f51187a48c92347f.jpg','Bishop Adu oversees multiple branches across the region. He is a respected counselor, strategist, and father figure to many pastors under his leadership.','active','2025-12-06 15:01:46','2025-12-06 15:18:55',4),(4,'Pastor Evelyn Agyapong','Women’s Ministry Pastor','evelyn.agyapong@gmpc.com','+233 26 317 4509','Burma Camp, Accra','http://localhost/gmpc/uploads/images/23fb49de9366bd50.jpg','Pastor Evelyn is dedicated to empowering women spiritually, professionally, and emotionally. She leads retreats, mentorship events, and community support programs.','active','2025-12-06 15:03:16','2025-12-06 15:18:44',3),(5,'Pastor Kenneth Owuraku','Associate Pastor / Missions','kenneth.owuraku@gmpc.com','+233 59 224 5731','Madina, Ghana','http://localhost/gmpc/uploads/images/f41b44ea1c0d272d.jpg','Pastor Kenneth coordinates missions outreach across rural communities and leads initiatives focused on evangelism, discipleship, and humanitarian support.','active','2025-12-06 15:04:52','2025-12-06 15:13:45',1);
/*!40000 ALTER TABLE `pastors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `community_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `excerpt` text,
  `content` text,
  `image_url` varchar(500) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `community_id` (`community_id`),
  KEY `status` (`status`),
  KEY `published_at` (`published_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,2,'Sample','sample','ggjkikihiuhk','gdtyfhjkguk dtcdvyjrv6rkvhj','http://localhost/gmpc/uploads/images/07edf829ae116df3.jpg','','published',NULL,'2025-12-06 22:29:16','2025-12-06 22:38:03'),(2,2,'The Power of Consistent Prayer','the-power-of-consistent-prayer','Prayer remains one of the most powerful tools given to believers. It is not merely an activity but a relationship-building channel between God and His children.','Prayer remains one of the most powerful tools given to believers. It is not merely an activity but a relationship-building channel between God and His children. Through prayer, we express our gratitude, present our needs, and align our hearts with God’s will.\r\n\r\nConsistent prayer shapes our character. It teaches patience, deepens trust, and builds spiritual resilience. Even in difficult times, prayer reminds us that we are never alone — God hears, sees, and answers according to His perfect wisdom.\r\n\r\nThe early church in Acts thrived because they prayed persistently. Their unity and boldness were rooted in their devotion to fellowship and prayer. Like them, we too can experience revival and spiritual growth when we remain committed to daily communion with God.\r\n\r\nLet us make prayer a lifestyle, not an emergency tool. God desires to speak with us — not occasionally, but continually.','https://images.pexels.com/photos/2927676/pexels-photo-2927676.jpeg','','published',NULL,'2025-12-06 22:43:13','2025-12-07 21:40:09'),(3,2,'Why Fellowship Matters in the Modern Church','why-fellowship-matters-in-the-modern-church','In a world that celebrates individuality, Christian fellowship remains a powerful expression of unity and love.','Many people today struggle with isolation and loneliness, even while surrounded by technology. For Christians, fellowship is God’s remedy to this emotional and spiritual disconnect.\r\n\r\nThe Bible encourages believers not to forsake assembling together (Hebrews 10:25). Fellowship is more than just gathering — it is sharing life, praying together, bearing each other’s burdens, and celebrating victories as one body.\r\n\r\nChurch community provides support in hard times, accountability in spiritual growth, and encouragement when faith feels weak. God designed us to grow together, not alone.\r\n\r\nAt Garrison Methodist Presbyterian Church, fellowship groups — from youth gatherings to women’s and men’s ministries — help build stronger relationships and deeper spiritual roots.','https://images.unsplash.com/photo-1524504388940-b1c1722653e1','','published',NULL,'2025-12-06 22:45:02','2025-12-06 22:45:02'),(4,2,'Serving with a Purpose: Using Your Gifts for God','serving-with-a-purpose-using-your-gifts-for-god','Every believer has a God-given gift. Here’s how your talent can make a difference in the church and beyond.','Service is one of the greatest expressions of our love for God. Jesus Himself said He came “not to be served, but to serve” (Mark 10:45), demonstrating the ultimate example of humility.\r\n\r\nThe Holy Spirit equips every believer with spiritual gifts — teaching, encouragement, leadership, administration, music, compassion, and more. These gifts are not meant to be hidden but used to edify the church.\r\n\r\nWhen you serve, you step into your divine purpose. You help strengthen the church, support its mission, and bring glory to God. Service also produces personal growth, fulfillment, and joy.\r\n\r\nWhether you are on the ushering team, in the choir, media ministry, children’s ministry, or prayer team, your contribution matters. No role is too small in God’s house.','https://images.unsplash.com/photo-1529333166437-7750a6dd5a70','','published',NULL,'2025-12-06 22:46:34','2025-12-06 22:46:34'),(5,2,'Raising Godly Children in a Digital World','raising-godly-children-in-a-digital-world','The digital age brings both opportunities and challenges. Here’s how parents can raise spiritually grounded children in today’s world.','Children today grow up surrounded by smartphones, tablets, social media, and endless digital entertainment. While technology has many benefits, it also introduces distractions and influences that can pull them away from spiritual values.\r\n\r\nChristian parents must take an active role in guiding their children. This begins with creating a Christ-centered home environment — one where love, prayer, discipline, and example shape their worldview.\r\n\r\nLimitless technology can expose children to unhealthy behaviors, comparison pressures, and harmful content. Setting boundaries, encouraging family devotions, involving children in church programs, and maintaining open conversations can help them grow confidently and spiritually healthy.\r\n\r\nMost importantly, children imitate what they see. Let your life reflect Christ, and your home will become a place of spiritual planting.','https://images.unsplash.com/photo-1511632765486-a01980e01a18','','published',NULL,'2025-12-06 22:48:33','2025-12-06 22:48:33'),(6,2,'Finding Hope in Difficult Times','finding-hope-in-difficult-times','Life is full of challenges, but God remains faithful. Here’s how believers can find strength during tough seasons.','Every believer goes through seasons of difficulty — sickness, job loss, grief, disappointment, or uncertainty. Yet the Bible reminds us that God is “our refuge and strength, a very present help in trouble” (Psalm 46:1).\r\n\r\nHope is not the absence of trouble, but the confidence that God is with us through it all. When everything seems overwhelming, prayer, worship, and Scripture remind us of God’s promises.\r\n\r\nGod uses difficult times to strengthen our faith, build endurance, and draw us closer to Him. What seems like a setback today may be preparing you for greater testimony tomorrow.\r\n\r\nNo matter what you are facing, know this: God sees you, loves you, and will never leave you.','https://images.unsplash.com/photo-1519681393784-d120267933ba','','published',NULL,'2025-12-06 23:12:02','2025-12-06 23:12:02'),(7,2,'Building a Stronger Walk with God Through Scripture','building-a-stronger-walk-with-god-through-scripture','Reading the Bible is more than a daily habit — it is the foundation of a healthy Christian life.','Christians who desire spiritual growth must stay rooted in the Word of God. Scripture reveals God’s heart, His promises, and His expectations for us. It is where we find wisdom, instruction, correction, and encouragement.\r\n\r\nMany believers struggle with consistency, not because they lack desire, but because they lack structure. Setting aside a specific time, using Bible reading plans, and journaling insights can help build a meaningful Bible routine.\r\n\r\nThe Word gives clarity in confusion, comfort in sorrow, strength in temptation, and direction in decision-making. When we meditate on Scripture, our minds are renewed and our faith grows stronger.\r\n\r\nAs the Psalmist said, “Your word is a lamp to my feet and a light to my path” (Psalm 119:105). Make the Word your daily companion, not an occasional visitor.','https://images.unsplash.com/photo-1501612780327-45045538702b','','published',NULL,'2025-12-06 23:13:14','2025-12-06 23:13:14');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scrolling_banners`
--

DROP TABLE IF EXISTS `scrolling_banners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scrolling_banners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `page_location` varchar(100) NOT NULL,
  `banner_text` text NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `text_color` varchar(20) DEFAULT '#ffffff',
  `background_color` varchar(20) DEFAULT '#5DADEC',
  `display_order` int DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_page_status` (`page_location`,`status`),
  CONSTRAINT `scrolling_banners_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scrolling_banners`
--

LOCK TABLES `scrolling_banners` WRITE;
/*!40000 ALTER TABLE `scrolling_banners` DISABLE KEYS */;
INSERT INTO `scrolling_banners` VALUES (1,'homepage','Welcome to Garrison Methodist Presbyterian Church - A community of faith, hope, and love! 🙏',NULL,'#ff0000','#dcec5f',1,'active',NULL,'2025-11-27 16:36:15','2025-12-01 18:43:17'),(2,'homepage','Join us this Sunday at 9:00 AM for worship service! 📅',NULL,'#ffffff','#0A0A33',2,'active',NULL,'2025-11-27 16:36:15','2025-11-27 16:36:15'),(3,'homepage','New members are always welcome - Come grow with us in faith! ✨',NULL,'#ffffff','#5DADEC',3,'active',NULL,'2025-11-27 16:36:15','2025-11-27 16:36:15'),(4,'communities','Explore our vibrant communities and find your place to serve! 👥',NULL,'#ffffff','#5DADEC',1,'active',NULL,'2025-11-27 16:36:15','2025-11-27 16:36:15'),(5,'communities','Get involved in our ministries and make a difference! 🌟',NULL,'#ffffff','#0A0A33',2,'active',NULL,'2025-11-27 16:36:15','2025-11-27 16:36:15'),(6,'homepage','This is a test',NULL,'#ffffff','#5dadec',1,'active',1,'2025-11-27 16:38:57','2025-11-27 16:38:57'),(7,'israel','Zoom meeting at 3pm on Thursday','https://www.google.com','#810404','#eaec5f',1,'active',1,'2025-11-27 21:26:57','2025-11-27 21:35:49'),(9,'sermons','Listen to inspiring messages from our pastoral team! 🎤',NULL,'#ffffff','#5DADEC',1,'active',NULL,'2025-11-28 11:11:07','2025-11-28 11:11:07'),(10,'homepage','Explore our media gallery - photos, videos, and more! 📸',NULL,'#ffffff','#0A0A33',4,'active',NULL,'2025-11-28 11:11:30','2025-11-28 11:11:30'),(11,'events','Join us for upcoming church events and fellowship activities! 📅',NULL,'#ffffff','#5DADEC',1,'active',NULL,'2025-11-28 19:23:21','2025-11-28 19:23:21'),(12,'about','Test Banner',NULL,'#ffffff','#5dadec',1,'active',1,'2025-12-01 11:52:39','2025-12-01 11:52:39'),(13,'contact','Test Banner for Contact page',NULL,'#ffffff','#5dadec',1,'active',1,'2025-12-01 11:52:59','2025-12-01 11:52:59');
/*!40000 ALTER TABLE `scrolling_banners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sections`
--

DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `section_key` varchar(100) NOT NULL,
  `heading` varchar(200) DEFAULT NULL,
  `subheading` varchar(300) DEFAULT NULL,
  `content` longtext,
  `button_text` varchar(50) DEFAULT NULL,
  `button_link` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `display_order` int DEFAULT '0',
  `status` enum('active','inactive') DEFAULT 'active',
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_key` (`section_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sections`
--

LOCK TABLES `sections` WRITE;
/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
/*!40000 ALTER TABLE `sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sermons`
--

DROP TABLE IF EXISTS `sermons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sermons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `preacher` varchar(100) DEFAULT NULL,
  `sermon_date` date NOT NULL,
  `description` text,
  `video_url` varchar(255) DEFAULT NULL,
  `audio_url` varchar(255) DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `scripture_reference` varchar(100) DEFAULT NULL,
  `series` varchar(100) DEFAULT NULL,
  `status` enum('published','draft') DEFAULT 'draft',
  `featured` tinyint(1) DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `sermons_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sermons`
--

LOCK TABLES `sermons` WRITE;
/*!40000 ALTER TABLE `sermons` DISABLE KEYS */;
INSERT INTO `sermons` VALUES (1,'Test Sermon: Faith in Action','Rev. Dr. G.N.N. Odonkor','2025-11-28','A powerful message about trusting God in all circumstances and seeing how He works all things together for good.','https://www.youtube.com/watch?v=wgfgLfZWXWQ',NULL,NULL,'Romans 8:28','Walking in Faith','published',0,1,'2025-11-28 11:06:40','2025-12-01 18:22:18'),(2,'GMPC YOUTH MIDWEEK SERVICE','Test Preacher','2025-11-26','[{\"type\":\"paragraph\",\"data\":{\"text\":\"Sample\"}},{\"type\":\"paragraph\",\"data\":{\"text\":\"This is  test\"}}]','https://www.youtube.com/watch?v=0goBKXHKwAA','',NULL,'test 1:1','MIDWEEK SERVICE','published',0,1,'2025-11-28 11:07:52','2025-12-05 19:18:22'),(3,'GMPC MENS FELLOWSHIP FESTIVAL OF NINE LESSONS AND CAROLS','Test Preacher','2025-12-03','[{\"type\":\"paragraph\",\"data\":{\"text\":\"Sample Text\"}}]','https://www.youtube.com/watch?v=s4q7tjuNowc','',NULL,'Test 1:1','Carols Night','published',1,2,'2025-12-05 19:01:38','2025-12-05 19:18:22'),(4,'SUNDAY SERVICE','Sample Preacher','2025-11-30','[{\"type\":\"paragraph\",\"data\":{\"text\":\"Sample text\"}}]','https://www.youtube.com/watch?v=pHuZ0U55Ok0','',NULL,'Test 1:1','Sunday Service','published',0,2,'2025-12-05 19:05:28','2025-12-05 19:05:28'),(5,'SUNDAY SERVICE','Test Preacher','2025-11-23','[{\"type\":\"paragraph\",\"data\":{\"text\":\"Add your main sermon summary here.\"}}]','https://www.youtube.com/watch?v=1-lzQWYy3bE','',NULL,'Test 1:1','Sunday Service','published',0,2,'2025-12-05 19:07:34','2025-12-05 19:07:34');
/*!40000 ALTER TABLE `sermons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` varchar(50) DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'church_name','Garrison Methodist Presbyterian Church','text','Church Name','2025-11-27 13:28:19'),(2,'church_address','Burma Camp, Accra','text','Church Address','2025-11-27 13:28:19'),(3,'church_phone','(+233) 243-3689','text','Church Phone','2025-11-27 13:28:19'),(4,'church_email','info@garrisonmpc.org','email','Church Email','2025-11-27 13:28:19'),(5,'sunday_service_time','10:00 AM','text','Sunday Service Time','2025-12-01 17:55:24'),(6,'tuesday_service_time','9:00 AM','text','Tuesday Morning Service Time','2025-11-27 13:28:19'),(7,'counselling_time','12:00 AM','text','Counselling Session Time','2025-12-06 22:07:30'),(8,'facebook_url','https://www.facebook.com/gmpcburmacamp','url','Facebook URL','2025-11-27 13:28:19'),(9,'youtube_url','https://www.youtube.com/@gmpcburmacamp3405','url','YouTube URL','2025-11-27 13:28:19'),(10,'tiktok_url','https://www.tiktok.com/@gmpcburmacamp','url','TikTok URL','2025-11-27 13:28:19');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-08 10:09:39
