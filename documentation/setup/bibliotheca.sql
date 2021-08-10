-- MySQL dump 10.13  Distrib 8.0.25, for Linux (x86_64)
--
-- Host: localhost    Database: bibliotheca
-- ------------------------------------------------------
-- Server version	8.0.25

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
-- Table structure for table `#REPLACEME#_collection`
--

DROP TABLE IF EXISTS `#REPLACEME#_collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `#REPLACEME#_collection` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `defaultSearchField` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `defaultSortField` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `advancedSearchTableFields` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificationuser` int DEFAULT NULL,
  `owner` int NOT NULL,
  `group` int NOT NULL,
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `#REPLACEME#_collection`
--

LOCK TABLES `#REPLACEME#_collection` WRITE;
/*!40000 ALTER TABLE `#REPLACEME#_collection` DISABLE KEYS */;
/*!40000 ALTER TABLE `#REPLACEME#_collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `#REPLACEME#_group`
--

DROP TABLE IF EXISTS `#REPLACEME#_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `#REPLACEME#_group` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `protected` tinyint(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificationuser` int DEFAULT NULL,
  `owner` int NOT NULL,
  `group` int NOT NULL,
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `#REPLACEME#_group`
--

LOCK TABLES `#REPLACEME#_group` WRITE;
/*!40000 ALTER TABLE `#REPLACEME#_group` DISABLE KEYS */;
INSERT INTO `#REPLACEME#_group` VALUES (1,'Administration','Admin group',1,'2020-05-31 21:25:29','2021-08-08 10:52:44',0,1,1,'rwx------'),(2,'Users','Default user group',1,'2020-05-31 21:25:29','2021-08-08 10:52:44',0,1,1,'rwxr--r--'),(3,'Anonymous','Anonymous users',1,'2020-05-31 21:25:29','2021-08-08 10:52:44',0,1,1,'rwxr--r--');
/*!40000 ALTER TABLE `#REPLACEME#_group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `#REPLACEME#_menu`
--

DROP TABLE IF EXISTS `#REPLACEME#_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `#REPLACEME#_menu` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` int NOT NULL DEFAULT '0',
  `group` int NOT NULL DEFAULT '0',
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int NOT NULL DEFAULT '0',
  `category` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `#REPLACEME#_menu`
--

LOCK TABLES `#REPLACEME#_menu` WRITE;
/*!40000 ALTER TABLE `#REPLACEME#_menu` DISABLE KEYS */;
INSERT INTO `#REPLACEME#_menu` VALUES (1,'Dashboard','','home',1,1,'rw-r--r--',0,'show'),(2,'Collections','collections','database',1,1,'rw-r--r--',1,'show'),(3,'Tags','tags','tag',1,1,'rw-r--r--',2,'show'),(4,'Add','manageentry','plus-circle',1,2,'rw-rw----',0,'manage'),(6,'Tags','managetags','tag',1,2,'rw-rw----',2,'manage'),(7,'Collections','managecolletions','database',1,2,'rw-rw----',3,'manage'),(8,'Users','manageusers','users',1,1,'rw-------',4,'manage'),(9,'Login','auth','',1,1,'rw-r--r--',0,''),(10,'Collection fields','managecollectionfields','',1,2,'rw-rw----',0,''),(11,'Entry','entry','',1,1,'rw-r--r--',0,''),(12,'Search','search','',1,1,'rw-r--r--',0,''),(14,'Tool','tool','',1,2,'rw-rw----',0,''),(15,'Advanced search','advancedsearch','',1,1,'rw-r--r--',0,''),(16,'Profile','profile','user',1,2,'rw-rw----',6,'manage'),(17,'Groups','managegroups','users',1,1,'rw-------',5,'manage'),(18,'Bulkedit','bulkedit','',1,2,'rw-rw----',0,''),(19,'System Information','sysinfo','info',1,1,'rw-------',3,'show');
/*!40000 ALTER TABLE `#REPLACEME#_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `#REPLACEME#_sys_fields`
--

DROP TABLE IF EXISTS `#REPLACEME#_sys_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `#REPLACEME#_sys_fields` (
  `id` int NOT NULL AUTO_INCREMENT,
  `identifier` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `displayname` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `searchtype` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createstring` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inputValidation` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `apiinfo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificationuser` int DEFAULT NULL,
  `owner` int NOT NULL,
  `group` int NOT NULL,
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `identifier` (`identifier`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `#REPLACEME#_sys_fields`
--

LOCK TABLES `#REPLACEME#_sys_fields` WRITE;
/*!40000 ALTER TABLE `#REPLACEME#_sys_fields` DISABLE KEYS */;
INSERT INTO `#REPLACEME#_sys_fields` VALUES (1,'title','Title','text','entryText','`title` varchar(128) NOT NULL, ADD FULLTEXT (`title`)','',NULL,'string 128','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(2,'description','Description','text3','entryText','`description` varchar(255) NULL DEFAULT NULL, ADD FULLTEXT (`description`)','',NULL,'string 255','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(3,'content','Main content','textarea','entryText','`content` text NULL DEFAULT NULL, ADD FULLTEXT (`content`)','',NULL,'mysql text','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(5,'tag','Tag','lookupmultiple','tag',NULL,'',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(6,'category','Category','lookupmultiple','tag',NULL,'',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(7,'publisher','Publisher','lookupmultiple','tag',NULL,'allowSpace',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(8,'developer','Developer','lookupmultiple','tag',NULL,'allowSpace',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(9,'platform','Platform','selection','entrySingleText','`platform` varchar(32) NULL DEFAULT NULL','','Nintendo,Nintendo Switch,PC,Playstation,Playstation 2,Playstation 3,Playstation 4,Playstation 5,Xbox,Xbox 360,Xbox One,Xbox One S,Xbox One X,Xbox Series S,Xbox Series X','One of Nintendo,Nintendo Switch,PC,Playstation,Playstation 2,Playstation 3,Playstation 4,Playstation 5,Xbox,Xbox 360,Xbox One,Xbox One S,Xbox One X,Xbox Series S,Xbox Series X','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(10,'storage','Storage','lookupmultiple','tag',NULL,'',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(13,'rating','Rating','selection','entrySingleText','`rating` varchar(16) NULL DEFAULT NULL','','0/10,2/10,3/10,4/10,5/10,6/10,7/10,8/10,9/10,10/10','One of 0/10,2/10,3/10,4/10,5/10,6/10,7/10,8/10,9/10,10/10','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(14,'year','Year','year','entrySingleNum','`year` int(10) NULL, ADD INDEX (`year`)','',NULL,'int 10','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(15,'coverimage','Cover image','upload',NULL,NULL,'',NULL,'One file in $_FILES[uploads] of post','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(16,'attachment','Attachments','upload_multiple',NULL,NULL,'',NULL,'Multiple in $_FILES[uploads] of post','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(17,'os','Operating system and version','selection','entrySingleNum','`os` varchar(32) NULL DEFAULT NULL','','DOS,Windows 1,Windows 2,Windows 3,Windows 95,Windows 99,Windows XP,Windows 2000,Windows ME,Windows Vista,Windows 8,Windows 10','One of DOS,Windows 1,Windows 2,Windows 3,Windows 95,Windows 99,Windows XP,Windows 2000,Windows ME,Windows Vista,Windows 8,Windows 10','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(18,'actors','Actors','lookupmultiple','tag',NULL,'allowSpace',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',NULL,1,1,'rw-r--r--'),(19,'countries','Countries','lookupmultiple','tag',NULL,'allowSpace',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',NULL,1,1,'rw-r--r--'),(20,'directors','Directors','lookupmultiple','tag',NULL,'allowSpace',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',NULL,1,1,'rw-r--r--'),(21,'genres','Genres','lookupmultiple','tag',NULL,'',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',NULL,1,1,'rw-r--r--'),(22,'languages','Languages','lookupmultiple','tag',NULL,'',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',NULL,1,1,'rw-r--r--'),(23,'runtime','Runtime (min)','number','entrySingleNum','`runtime` int(10) NULL, ADD INDEX (`runtime`)','',NULL,'int 10','2021-08-08 10:52:44','2021-08-08 10:52:44',NULL,1,1,'rw-r--r--'),(24,'imdbrating','IMDB Rating','text','entrySingleText','`imdbrating` varchar(128) NULL DEFAULT NULL','',NULL,'string 128','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(25,'viewcount','View counter','number','entrySingleNum','`viewcount` int(10) NULL, ADD INDEX (`viewcount`)','',NULL,'int 10','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(26,'writers','Writers','lookupmultiple','tag',NULL,'allowSpace',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',NULL,1,1,'rw-r--r--'),(27,'localizedTitle','localized Title','text','entryText','`localizedTitle` varchar(128) NULL DEFAULT NULL, ADD FULLTEXT (`localizedTitle`)','',NULL,'string 128','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(28,'gameEngine','Game Engine','text','entryText','`gameEngine` varchar(128) NOT NULL, ADD FULLTEXT (`gameEngine`)','',NULL,'string 128','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(29,'view','View','selection','entrySingleNum','`view` varchar(32) NULL DEFAULT NULL','','First person,Third person,Top-down','First person,Third person,Top-down','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(30,'sysReq','System Requirements','text3','entryText','`sysReq` varchar(255) NULL DEFAULT NULL, ADD FULLTEXT (`sysReq`)','',NULL,'string 255','2021-08-08 10:52:44','2021-08-08 10:52:44',0,1,1,'rw-r--r--'),(31,'artist','Artist','text','entrySingleText','`artist` varchar(128) NULL DEFAULT NULL','',NULL,'string 128','2021-08-08 10:52:44','2021-08-08 10:52:44',NULL,1,1,'rw-r--r--'),(32,'artists','Artists','lookupmultiple','tag',NULL,'allowSpace',NULL,'string 64','2021-08-08 10:52:44','2021-08-08 10:52:44',NULL,1,1,'rw-r--r--');
/*!40000 ALTER TABLE `#REPLACEME#_sys_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `#REPLACEME#_tool`
--

DROP TABLE IF EXISTS `#REPLACEME#_tool`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `#REPLACEME#_tool` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` int NOT NULL,
  `group` int NOT NULL,
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `action` (`action`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `#REPLACEME#_tool`
--

LOCK TABLES `#REPLACEME#_tool` WRITE;
/*!40000 ALTER TABLE `#REPLACEME#_tool` DISABLE KEYS */;
INSERT INTO `#REPLACEME#_tool` VALUES (1,'IMDB web','Web parser','imdbweb','_self',1,1,'rw-r--r--'),(2,'Game infos','Weblinks','gameinfo','_self',1,1,'rw-r--r--'),(3,'Musicbrainz','Album infos','musicbrainz','_self',1,1,'rw-r--r--');
/*!40000 ALTER TABLE `#REPLACEME#_tool` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `#REPLACEME#_tool2collection`
--

DROP TABLE IF EXISTS `#REPLACEME#_tool2collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `#REPLACEME#_tool2collection` (
  `fk_tool_id` int NOT NULL,
  `fk_collection_id` int NOT NULL,
  UNIQUE KEY `fk_collection_id` (`fk_collection_id`,`fk_tool_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `#REPLACEME#_tool2collection`
--

LOCK TABLES `#REPLACEME#_tool2collection` WRITE;
/*!40000 ALTER TABLE `#REPLACEME#_tool2collection` DISABLE KEYS */;
/*!40000 ALTER TABLE `#REPLACEME#_tool2collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `#REPLACEME#_user`
--

DROP TABLE IF EXISTS `#REPLACEME#_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `#REPLACEME#_user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `login` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `active` int NOT NULL DEFAULT '1',
  `apiToken` char(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apiTokenValidDate` timestamp NULL DEFAULT NULL,
  `baseGroupId` int NOT NULL DEFAULT '0',
  `protected` tinyint(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificationuser` int DEFAULT NULL,
  `owner` int NOT NULL,
  `group` int NOT NULL,
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `#REPLACEME#_user`
--

LOCK TABLES `#REPLACEME#_user` WRITE;
/*!40000 ALTER TABLE `#REPLACEME#_user` DISABLE KEYS */;
INSERT INTO `#REPLACEME#_user` VALUES (1,'admin','$2y$10$BdLVGaeiQc12smmNKf4rk.2Dj6ockECsSlpx1eO7RWN3RbX2gYrai','Administrator',1,NULL,NULL,1,1,'2019-09-01 17:22:02','2021-08-08 10:52:45',0,1,1,'rwxr-----'),(2,'anonymoose','','Anonymoose',1,NULL,NULL,3,1,'2020-05-03 17:22:02','2021-08-08 10:52:45',0,2,3,'rwxr--r--');
/*!40000 ALTER TABLE `#REPLACEME#_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `#REPLACEME#_user2group`
--

DROP TABLE IF EXISTS `#REPLACEME#_user2group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `#REPLACEME#_user2group` (
  `fk_user_id` int NOT NULL DEFAULT '0',
  `fk_group_id` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`fk_user_id`,`fk_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `#REPLACEME#_user2group`
--

LOCK TABLES `#REPLACEME#_user2group` WRITE;
/*!40000 ALTER TABLE `#REPLACEME#_user2group` DISABLE KEYS */;
INSERT INTO `#REPLACEME#_user2group` VALUES (1,1),(2,3);
/*!40000 ALTER TABLE `#REPLACEME#_user2group` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `#REPLACEME#_userSession`
--

DROP TABLE IF EXISTS `#REPLACEME#_userSession`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `#REPLACEME#_userSession` (
  `fk_user_id` int NOT NULL,
  `loginTime` datetime NOT NULL,
  `area` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `salt` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`area`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `#REPLACEME#_userSession`
--

LOCK TABLES `#REPLACEME#_userSession` WRITE;
/*!40000 ALTER TABLE `#REPLACEME#_userSession` DISABLE KEYS */;
/*!40000 ALTER TABLE `#REPLACEME#_userSession` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-08-08 12:53:01
