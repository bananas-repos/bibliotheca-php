-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 01, 2019 at 08:27 PM
-- Server version: 5.7.26-log
-- PHP Version: 7.3.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bibliotheca`
--
CREATE DATABASE IF NOT EXISTS `bibliotheca` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
USE `bibliotheca`;

-- --------------------------------------------------------

--
-- Table structure for table `bib_collection`
--

DROP TABLE IF EXISTS `bib_collection`;
CREATE TABLE `bib_collection` (
  `id` int(10) NOT NULL,
  `name` varchar(64) COLLATE utf8mb4_bin NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `modificationuser` int(11) NOT NULL,
  `owner` int(10) NOT NULL,
  `group` int(10) NOT NULL,
  `rights` char(9) COLLATE utf8mb4_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `bib_collection`
--

INSERT INTO `bib_collection` (`id`, `name`, `description`, `created`, `modified`, `modificationuser`, `owner`, `group`, `rights`) VALUES
(1, 'Movies', 'Movie collection', '2019-09-01 17:26:23', NULL, 0, 2, 2, 'rwxrw-r--'),
(2, 'Games', 'Game collection', '2019-09-01 17:26:56', NULL, 0, 2, 2, 'rwxrw-r--');

-- --------------------------------------------------------

--
-- Table structure for table `bib_collection_entry_1`
--

DROP TABLE IF EXISTS `bib_collection_entry_1`;
CREATE TABLE `bib_collection_entry_1` (
  `id` int(10) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificationuser` int(10) NOT NULL,
  `title` varchar(128) COLLATE utf8mb4_bin NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `content` text COLLATE utf8mb4_bin NOT NULL,
  `releasedate` timestamp NULL DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `image` varchar(128) COLLATE utf8mb4_bin DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `bib_collection_field_1`
--

DROP TABLE IF EXISTS `bib_collection_field_1`;
CREATE TABLE `bib_collection_field_1` (
  `id` int(11) NOT NULL,
  `identifier` varchar(16) COLLATE utf8mb4_bin NOT NULL,
  `displayname` varchar(128) COLLATE utf8mb4_bin NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_bin NOT NULL,
  `position` int(10) DEFAULT NULL,
  `searchable` tinyint(1) DEFAULT NULL,
  `protected` tinyint(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificationuser` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `bib_collection_field_1`
--

INSERT INTO `bib_collection_field_1` (`id`, `identifier`, `displayname`, `type`, `position`, `searchable`, `protected`, `created`, `modified`, `modificationuser`) VALUES
(1, 'title', 'Title', 'varchar', NULL, NULL, 1, '2019-09-01 18:26:33', '2019-09-01 18:53:00', 2),
(2, 'description', 'Description', 'varchar', NULL, NULL, 1, '2019-09-01 18:28:35', '2019-09-01 18:53:04', 2),
(3, 'content', 'Main content', 'text', NULL, NULL, 1, '2019-09-01 18:28:35', '2019-09-01 18:53:07', 2),
(4, 'releasedate', 'Release date', 'date', NULL, NULL, 0, '2019-09-01 19:01:39', '2019-09-01 19:29:13', 2),
(5, 'tag', 'Tag', 'lookupmultiple', NULL, NULL, 0, '2019-09-01 19:11:18', '2019-09-01 19:28:42', 2),
(6, 'category', 'Category', 'lookupmultiple', NULL, NULL, 0, '2019-09-01 19:11:18', '2019-09-01 19:28:48', 2),
(7, 'publisher', 'Publisher', 'lookupmultiple', NULL, NULL, 0, '2019-09-01 19:17:51', '2019-09-01 19:20:31', 2),
(8, 'developer', 'Developer', 'lookupmultiple', NULL, NULL, 0, '2019-09-01 19:17:51', '2019-09-01 19:20:36', 2),
(9, 'platform', 'Platform', 'lookup', NULL, NULL, 0, '2019-09-01 19:18:33', '2019-09-01 19:20:44', 2),
(10, 'storage', 'Storage', 'lookup', NULL, NULL, 0, '2019-09-01 19:18:33', '2019-09-01 19:20:57', 2),
(13, 'rating', 'Rating', 'lookup', NULL, NULL, 0, '2019-09-01 19:25:35', '2019-09-01 19:25:35', 2),
(14, 'year', 'Year', 'year', NULL, NULL, 0, '2019-09-01 19:30:11', '2019-09-01 19:31:11', 2),
(15, 'coverimage', 'Cover image', 'image', NULL, NULL, 0, '2019-09-01 19:48:44', '2019-09-01 19:48:44', 2),
(16, 'attachment', 'Attachments', 'upload', NULL, NULL, 0, '2019-09-01 19:48:44', '2019-09-01 19:48:44', 2),
(17, 'os', 'Operating system and version', 'lookupmultiple', NULL, NULL, 0, '2019-09-01 19:55:13', '2019-09-01 19:55:13', 2);

-- --------------------------------------------------------

--
-- Table structure for table `bib_collection_lookup2entry_1`
--

DROP TABLE IF EXISTS `bib_collection_lookup2entry_1`;
CREATE TABLE `bib_collection_lookup2entry_1` (
  `lookup` int(10) NOT NULL,
  `entry` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `bib_collection_lookup_1`
--

DROP TABLE IF EXISTS `bib_collection_lookup_1`;
CREATE TABLE `bib_collection_lookup_1` (
  `id` int(10) NOT NULL,
  `name` varchar(32) COLLATE utf8mb4_bin NOT NULL,
  `position` int(10) NOT NULL DEFAULT '0',
  `field` int(10) NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificationuser` int(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `bib_collection_lookup_1`
--

INSERT INTO `bib_collection_lookup_1` (`id`, `name`, `position`, `field`, `created`, `modified`, `modificationuser`) VALUES
(1, 'PC', 0, 9, '2019-09-01 19:53:27', '2019-09-01 19:53:27', 2),
(2, 'Nintendo Switch', 0, 9, '2019-09-01 19:54:21', '2019-09-01 19:54:21', 2),
(3, 'Windows 95', 0, 17, '2019-09-01 19:55:44', '2019-09-01 19:55:44', 2),
(4, 'Windows 98', 0, 17, '2019-09-01 19:56:00', '2019-09-01 19:56:00', 2),
(5, 'Windows ME', 0, 17, '2019-09-01 19:56:21', '2019-09-01 19:56:21', 2),
(6, 'Windows 2000', 0, 17, '2019-09-01 19:56:21', '2019-09-01 19:56:21', 2),
(7, 'Windows XP', 0, 17, '2019-09-01 19:56:44', '2019-09-01 19:56:44', 2),
(8, 'Windows Vista', 0, 17, '2019-09-01 19:56:44', '2019-09-01 19:56:44', 2),
(9, '1/10', 0, 13, '2019-09-01 19:58:23', '2019-09-01 19:58:23', 2),
(10, '2/10', 0, 13, '2019-09-01 19:58:23', '2019-09-01 19:58:23', 2),
(11, '3/10', 0, 13, '2019-09-01 19:58:40', '2019-09-01 19:58:40', 2),
(12, '4/10', 0, 13, '2019-09-01 19:58:40', '2019-09-01 19:58:40', 2),
(13, '5/10', 0, 13, '2019-09-01 19:58:56', '2019-09-01 19:58:56', 2),
(14, '6/10', 0, 13, '2019-09-01 19:58:56', '2019-09-01 19:58:56', 2);

-- --------------------------------------------------------

--
-- Table structure for table `bib_group`
--

DROP TABLE IF EXISTS `bib_group`;
CREATE TABLE `bib_group` (
  `id` int(10) NOT NULL,
  `name` varchar(16) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `describtion` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `protected` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `bib_group`
--

INSERT INTO `bib_group` (`id`, `name`, `describtion`, `active`, `protected`) VALUES
(1, 'Administration', 'Die Gruppe die alles darf.', 1, 1),
(2, 'Benutzergruppe', 'Standard Benutzergruppe', 1, 1),
(3, 'Gast', 'Die Gast Gruppe.', 1, 1),
(8, 'Collection', 'Group to access the collection management', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `bib_user`
--

DROP TABLE IF EXISTS `bib_user`;
CREATE TABLE `bib_user` (
  `id` int(10) NOT NULL,
  `login` varchar(255) CHARACTER SET utf8mb4 DEFAULT NULL,
  `password` varchar(40) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `name` varchar(64) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `active` int(10) NOT NULL DEFAULT '1',
  `email` varchar(128) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
  `baseGroupId` int(10) NOT NULL DEFAULT '0',
  `protected` tinyint(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `bib_user`
--

INSERT INTO `bib_user` (`id`, `login`, `password`, `name`, `active`, `email`, `baseGroupId`, `protected`, `created`) VALUES
(1, 'admin', 'a94a8fe5ccb19ba61c4c0873d391e987982fbbd3', 'Banana', 1, 'mail@bananas-playground.net', 1, 1, '2019-09-01 17:22:02'),
(2, 'bibuser', '', 'Mr. Gast', 1, 'test@test.com', 2, 1, '2019-09-01 17:22:02');

-- --------------------------------------------------------

--
-- Table structure for table `bib_user2group`
--

DROP TABLE IF EXISTS `bib_user2group`;
CREATE TABLE `bib_user2group` (
  `fk_user_id` int(10) NOT NULL DEFAULT '0',
  `fk_group_id` int(10) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

--
-- Dumping data for table `bib_user2group`
--

INSERT INTO `bib_user2group` (`fk_user_id`, `fk_group_id`) VALUES
(1, 1),
(2, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bib_collection`
--
ALTER TABLE `bib_collection`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bib_collection_entry_1`
--
ALTER TABLE `bib_collection_entry_1`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bib_collection_field_1`
--
ALTER TABLE `bib_collection_field_1`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `bib_collection_lookup_1`
--
ALTER TABLE `bib_collection_lookup_1`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bib_group`
--
ALTER TABLE `bib_group`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bib_user`
--
ALTER TABLE `bib_user`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bib_user2group`
--
ALTER TABLE `bib_user2group`
  ADD PRIMARY KEY (`fk_user_id`,`fk_group_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bib_collection`
--
ALTER TABLE `bib_collection`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `bib_collection_entry_1`
--
ALTER TABLE `bib_collection_entry_1`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bib_collection_field_1`
--
ALTER TABLE `bib_collection_field_1`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `bib_collection_lookup_1`
--
ALTER TABLE `bib_collection_lookup_1`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `bib_group`
--
ALTER TABLE `bib_group`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `bib_user`
--
ALTER TABLE `bib_user`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
