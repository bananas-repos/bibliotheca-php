SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bibliotheca`
--

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_collection`
--

DROP TABLE IF EXISTS `#REPLACEME#_collection`;
CREATE TABLE `#REPLACEME#_collection` (
  `id` int NOT NULL,
  `name` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `defaultSearchField` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `defaultSortField` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `defaultSortOrder` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `advancedSearchTableFields` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificationuser` int DEFAULT NULL,
  `owner` int NOT NULL,
  `group` int NOT NULL,
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_group`
--

DROP TABLE IF EXISTS `#REPLACEME#_group`;
CREATE TABLE `#REPLACEME#_group` (
  `id` int NOT NULL,
  `name` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `protected` tinyint(1) NOT NULL DEFAULT '0',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `modificationuser` int DEFAULT NULL,
  `owner` int NOT NULL,
  `group` int NOT NULL,
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#REPLACEME#_group`
--

INSERT INTO `#REPLACEME#_group` (`id`, `name`, `description`, `protected`, `created`, `modified`, `modificationuser`, `owner`, `group`, `rights`) VALUES
(1, 'Administration', 'Admin group', 1, '2020-05-31 21:25:29', '2021-01-02 19:56:10', 0, 1, 1, 'rwx------'),
(2, 'Users', 'Default user group', 1, '2020-05-31 21:25:29', '2021-01-02 19:56:10', 0, 1, 1, 'rwxr--r--'),
(3, 'Anonymous', 'Anonymous users', 1, '2020-05-31 21:25:29', '2021-01-02 19:56:10', 0, 1, 1, 'rwxr--r--');

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_menu`
--

DROP TABLE IF EXISTS `#REPLACEME#_menu`;
CREATE TABLE `#REPLACEME#_menu` (
  `id` int UNSIGNED NOT NULL,
  `text` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contextaction` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` int NOT NULL DEFAULT '0',
  `group` int NOT NULL DEFAULT '0',
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int NOT NULL DEFAULT '0',
  `category` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#REPLACEME#_menu`
--

INSERT INTO `bib_menu` (`id`, `text`, `action`, `contextaction`, `icon`, `owner`, `group`, `rights`, `position`, `category`) VALUES
(1, 'menu.show.dashboard', 'dashboard', '', 'home', 1, 1, 'rw-r--r--', 0, 'show'),
(2, 'menu.show.collections', 'collections', '', 'database', 1, 1, 'rw-r--r--', 1, 'show'),
(3, 'menu.show.tags', 'tags', '', 'tag', 1, 1, 'rw-r--r--', 2, 'show'),
(4, 'menu.manage.add', 'manageentry', 'collection', 'plus-circle', 1, 2, 'rw-rw----', 0, 'manage'),
(6, 'menu.manage.tags', 'managetags', 'collection', 'tag', 1, 2, 'rw-rw----', 2, 'manage'),
(7, 'menu.manage.collections', 'managecolletions', '', 'database', 1, 2, 'rw-rw----', 3, 'manage'),
(8, 'menu.manage.users', 'manageusers', '', 'users', 1, 1, 'rw-------', 4, 'manage'),
(9, 'Login', 'auth', '', '', 1, 1, 'rw-r--r--', 0, ''),
(10, 'Collection fields', 'managecollectionfields', '', '', 1, 2, 'rw-rw----', 0, ''),
(11, 'Entry', 'entry', '', '', 1, 1, 'rw-r--r--', 0, ''),
(12, 'Search', 'search', '', '', 1, 1, 'rw-r--r--', 0, ''),
(14, 'Tool', 'tool', '', '', 1, 2, 'rw-rw----', 0, ''),
(15, 'Advanced search', 'advancedsearch', '', '', 1, 1, 'rw-r--r--', 0, ''),
(16, 'menu.manage.profile', 'profile', '', 'user', 1, 2, 'rw-rw----', 6, 'manage'),
(17, 'menu.manage.groups', 'managegroups', '', 'users', 1, 1, 'rw-------', 5, 'manage'),
(18, 'Bulkedit', 'bulkedit', '', '', 1, 2, 'rw-rw----', 0, ''),
(19, 'menu.show.sysinfo', 'sysinfo', '', 'info', 1, 1, 'rw-------', 3, 'show');

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_sys_fields`
--

DROP TABLE IF EXISTS `#REPLACEME#_sys_fields`;
CREATE TABLE `#REPLACEME#_sys_fields` (
  `id` int NOT NULL,
  `identifier` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `displayname` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#REPLACEME#_sys_fields`
--

INSERT INTO `#REPLACEME#_sys_fields` (`id`, `identifier`, `displayname`, `type`, `searchtype`, `createstring`, `inputValidation`, `value`, `apiinfo`, `created`, `modified`, `modificationuser`, `owner`, `group`, `rights`) VALUES
(1, 'title', 'sysfield.title', 'text', 'entryText', '`title` varchar(128) NOT NULL, ADD FULLTEXT (`title`)', '', NULL, 'string 128', '2019-09-01 18:26:33', '2024-04-20 07:44:10', 0, 1, 1, 'rw-r--r--'),
(2, 'description', 'sysfield.description', 'text3', 'entryText', '`description` varchar(255) NULL DEFAULT NULL, ADD FULLTEXT (`description`)', '', NULL, 'string 255', '2019-09-01 18:28:35', '2024-04-20 07:45:18', 0, 1, 1, 'rw-r--r--'),
(3, 'content', 'sysfield.content', 'textarea', 'entryText', '`content` text NULL DEFAULT NULL, ADD FULLTEXT (`content`)', '', NULL, 'mysql text', '2019-09-01 18:28:35', '2024-04-20 07:46:05', 0, 1, 1, 'rw-r--r--'),
(5, 'tag', 'sysfield.tag', 'lookupmultiple', 'tag', NULL, '', NULL, 'string 64', '2019-09-01 19:11:18', '2024-04-20 07:46:35', 0, 1, 1, 'rw-r--r--'),
(6, 'category', 'sysfield.category', 'lookupmultiple', 'tag', NULL, '', NULL, 'string 64', '2019-09-01 19:11:18', '2024-04-20 07:47:26', 0, 1, 1, 'rw-r--r--'),
(7, 'publisher', 'sysfield.publisher', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', '2019-09-01 19:17:51', '2024-04-20 07:47:45', 0, 1, 1, 'rw-r--r--'),
(8, 'developer', 'sysfield.developer', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', '2019-09-01 19:17:51', '2024-04-20 07:48:07', 0, 1, 1, 'rw-r--r--'),
(9, 'platform', 'sysfield.platform', 'selection', 'entrySingleText', '`platform` varchar(32) NULL DEFAULT NULL', '', 'Nintendo,Nintendo Switch,PC,Playstation,Playstation 2,Playstation 3,Playstation 4,Playstation 5,Xbox,Xbox 360,Xbox One,Xbox One S,Xbox One X,Xbox Series S,Xbox Series X', 'One of Nintendo,Nintendo Switch,PC,Playstation,Playstation 2,Playstation 3,Playstation 4,Playstation 5,Xbox,Xbox 360,Xbox One,Xbox One S,Xbox One X,Xbox Series S,Xbox Series X', '2019-09-01 19:18:33', '2024-04-20 07:48:32', 0, 1, 1, 'rw-r--r--'),
(10, 'storage', 'sysfield.storage', 'lookupmultiple', 'tag', NULL, '', NULL, 'string 64', '2019-09-01 19:18:33', '2024-04-20 07:48:56', 0, 1, 1, 'rw-r--r--'),
(13, 'rating', 'sysfield.rating', 'selection', 'entrySingleText', '`rating` varchar(16) NULL DEFAULT NULL', '', '0/10,2/10,3/10,4/10,5/10,6/10,7/10,8/10,9/10,10/10', 'One of 0/10,2/10,3/10,4/10,5/10,6/10,7/10,8/10,9/10,10/10', '2019-09-01 19:25:35', '2024-04-20 07:49:22', 0, 1, 1, 'rw-r--r--'),
(14, 'year', 'sysfield.year', 'year', 'entrySingleNum', '`year` int(10) NULL, ADD INDEX (`year`)', '', NULL, 'int 10', '2019-09-01 19:30:11', '2024-04-20 07:49:37', 0, 1, 1, 'rw-r--r--'),
(15, 'coverimage', 'sysfield.coverimage', 'upload', NULL, NULL, '', NULL, 'One file in $_FILES[uploads] of post', '2019-09-01 19:48:44', '2024-04-20 07:49:59', 0, 1, 1, 'rw-r--r--'),
(16, 'attachment', 'sysfield.attachment', 'upload_multiple', NULL, NULL, '', NULL, 'Multiple in $_FILES[uploads] of post', '2019-09-01 19:48:44', '2024-04-20 07:50:35', 0, 1, 1, 'rw-r--r--'),
(17, 'os', 'sysfield.os', 'selection', 'entrySingleNum', '`os` varchar(32) NULL DEFAULT NULL', '', 'DOS,Windows 1,Windows 2,Windows 3,Windows 95,Windows 98,Windows XP,Windows 2000,Windows ME,Windows Vista,Windows 7,Windows 8,Windows 10,Windows 11', 'One of DOS,Windows 1,Windows 2,Windows 3,Windows 95,Windows 98,Windows XP,Windows 2000,Windows ME,Windows Vista,Windows 7,Windows 8,Windows 10,Windows 11', '2019-09-01 19:55:13', '2024-04-20 07:51:07', 0, 1, 1, 'rw-r--r--'),
(18, 'actors', 'sysfield.actors', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', '2020-07-26 07:12:48', '2024-04-20 07:51:30', NULL, 1, 1, 'rw-r--r--'),
(19, 'countries', 'sysfield.countries', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', '2020-07-26 07:16:08', '2024-04-20 07:52:01', NULL, 1, 1, 'rw-r--r--'),
(20, 'directors', 'sysfield.directors', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', '2020-07-26 07:17:59', '2024-04-20 07:52:23', NULL, 1, 1, 'rw-r--r--'),
(21, 'genres', 'sysfield.genres', 'lookupmultiple', 'tag', NULL, '', NULL, 'string 64', '2020-07-26 07:18:55', '2024-04-20 07:52:44', NULL, 1, 1, 'rw-r--r--'),
(22, 'languages', 'sysfield.languages', 'lookupmultiple', 'tag', NULL, '', NULL, 'string 64', '2020-07-26 07:20:45', '2024-04-20 07:53:17', NULL, 1, 1, 'rw-r--r--'),
(23, 'runtime', 'sysfield.runtime', 'number', 'entrySingleNum', '`runtime` int(10) NULL, ADD INDEX (`runtime`)', '', NULL, 'int 10', '2020-07-26 07:22:24', '2024-04-20 07:53:40', NULL, 1, 1, 'rw-r--r--'),
(24, 'imdbrating', 'sysfield.imdbrating', 'text', 'entrySingleText', '`imdbrating` varchar(128) NULL DEFAULT NULL', '', NULL, 'string 128', '2020-12-27 10:00:33', '2024-04-20 07:54:05', 0, 1, 1, 'rw-r--r--'),
(25, 'viewcount', 'sysfield.viewcount', 'number', 'entrySingleNum', '`viewcount` int(10) NULL, ADD INDEX (`viewcount`)', '', NULL, 'int 10', '2020-12-27 10:41:10', '2024-04-20 07:54:29', 0, 1, 1, 'rw-r--r--'),
(26, 'writers', 'sysfield.writers', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', '2021-01-05 09:47:20', '2024-04-20 07:54:53', NULL, 1, 1, 'rw-r--r--'),
(27, 'localizedTitle', 'sysfield.localizedTitle', 'text', 'entryText', '`localizedTitle` varchar(128) NULL DEFAULT NULL, ADD FULLTEXT (`localizedTitle`)', '', NULL, 'string 128', '2021-04-25 19:33:31', '2024-04-20 07:55:22', 0, 1, 1, 'rw-r--r--'),
(28, 'gameEngine', 'sysfield.gameEngine', 'text', 'entryText', '`gameEngine` varchar(128) NOT NULL, ADD FULLTEXT (`gameEngine`)', '', NULL, 'string 128', '2021-04-25 21:21:37', '2024-04-20 07:55:44', 0, 1, 1, 'rw-r--r--'),
(29, 'view', 'sysfield.view', 'selection', 'entrySingleNum', '`view` varchar(32) NULL DEFAULT NULL', '', 'First person,Third person,Top-down', 'First person,Third person,Top-down', '2021-04-25 21:21:45', '2024-04-20 07:56:12', 0, 1, 1, 'rw-r--r--'),
(30, 'sysReq', 'sysfield.sysReq', 'text3', 'entryText', '`sysReq` varchar(255) NULL DEFAULT NULL, ADD FULLTEXT (`sysReq`)', '', NULL, 'string 255', '2021-04-25 21:21:54', '2024-04-20 07:56:36', 0, 1, 1, 'rw-r--r--'),
(31, 'artist', 'sysfield.artist', 'text', 'entrySingleText', '`artist` varchar(128) NULL DEFAULT NULL', '', NULL, 'string 128', '2021-07-09 08:30:11', '2024-04-20 07:56:57', NULL, 1, 1, 'rw-r--r--'),
(32, 'artists', 'sysfield.artists', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', '2021-07-18 11:42:16', '2024-04-20 07:57:49', NULL, 1, 1, 'rw-r--r--'),
(34, 'isbn', 'sysfield.isbn', 'text', 'entrySingleText', '`isbn` varchar(32) NULL, ADD INDEX (`isbn`)', '', NULL, 'varchar 32', '2022-10-08 10:47:01', '2024-04-20 07:57:26', NULL, 1, 1, 'rw-r--r--'),
(35, 'combSearch', 'Combined Search', 'hidden', 'entryText', '`combSearch` text NULL DEFAULT NULL, ADD FULLTEXT (`combSearch`)', '', NULL, 'mysql text - Content will be auto generated from other entry fields', '2024-01-28 09:56:32', '2024-01-28 11:21:51', NULL, 1, 1, 'rw-r--r--');

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_tool`
--

DROP TABLE IF EXISTS `#REPLACEME#_tool`;
CREATE TABLE `#REPLACEME#_tool` (
  `id` int NOT NULL,
  `name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` int NOT NULL,
  `group` int NOT NULL,
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#REPLACEME#_tool`
--

INSERT INTO `#REPLACEME#_tool` (`id`, `name`, `description`, `action`, `target`, `owner`, `group`, `rights`) VALUES
(1, 'IMDB web', 'Web parser', 'imdbweb', '_self', 1, 1, 'rw-r--r--'),
(2, 'Game infos', 'Game infos', 'gameinfo', '_self', 1, 1, 'rw-r--r--'),
(3, 'Musicbrainz', 'Album infos', 'musicbrainz', '_self', 1, 1, 'rw-r--r--'),
(4, 'Google Books', 'Book infos from Google', 'googlebooks', '_self', 1, 1, 'rw-r--r--');

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_tool2collection`
--

DROP TABLE IF EXISTS `#REPLACEME#_tool2collection`;
CREATE TABLE `#REPLACEME#_tool2collection` (
  `fk_tool_id` int NOT NULL,
  `fk_collection_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_user`
--

DROP TABLE IF EXISTS `#REPLACEME#_user`;
CREATE TABLE `#REPLACEME#_user` (
  `id` int NOT NULL,
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
  `rights` char(9) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `#REPLACEME#_user`
--

INSERT INTO `#REPLACEME#_user` (`id`, `login`, `password`, `name`, `active`, `apiToken`, `apiTokenValidDate`, `baseGroupId`, `protected`, `created`, `modified`, `modificationuser`, `owner`, `group`, `rights`) VALUES
(1, 'admin', '$2y$10$BdLVGaeiQc12smmNKf4rk.2Dj6ockECsSlpx1eO7RWN3RbX2gYrai', 'Administrator', 1, NULL, NULL, 1, 1, '2019-09-01 17:22:02', '2021-01-02 19:56:10', 0, 1, 1, 'rwxr-----'),
(2, 'anonymoose', '', 'Anonymoose', 1, NULL, NULL, 3, 1, '2020-05-03 17:22:02', '2021-01-02 19:56:10', 0, 2, 3, 'rwxr--r--');

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_user2group`
--

DROP TABLE IF EXISTS `#REPLACEME#_user2group`;
CREATE TABLE `#REPLACEME#_user2group` (
  `fk_user_id` int NOT NULL DEFAULT '0',
  `fk_group_id` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bib_user2group`
--

INSERT INTO `bib_user2group` (`fk_user_id`, `fk_group_id`) VALUES
(1, 1),
(2, 3);

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_userSession`
--

DROP TABLE IF EXISTS `#REPLACEME#_userSession`;
CREATE TABLE `#REPLACEME#_userSession` (
  `fk_user_id` int NOT NULL,
  `loginTime` datetime NOT NULL,
  `area` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `salt` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `#REPLACEME#_collection`
--
ALTER TABLE `#REPLACEME#_collection`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `#REPLACEME#_group`
--
ALTER TABLE `#REPLACEME#_group`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `#REPLACEME#_menu`
--
ALTER TABLE `#REPLACEME#_menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `#REPLACEME#_sys_fields`
--
ALTER TABLE `#REPLACEME#_sys_fields`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD UNIQUE KEY `identifier` (`identifier`);

--
-- Indexes for table `#REPLACEME#_tool`
--
ALTER TABLE `#REPLACEME#_tool`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `action` (`action`);

--
-- Indexes for table `#REPLACEME#_tool2collection`
--
ALTER TABLE `#REPLACEME#_tool2collection`
  ADD UNIQUE KEY `fk_collection_id` (`fk_collection_id`,`fk_tool_id`);

--
-- Indexes for table `#REPLACEME#_user`
--
ALTER TABLE `#REPLACEME#_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`);

--
-- Indexes for table `#REPLACEME#_user2group`
--
ALTER TABLE `#REPLACEME#_user2group`
  ADD PRIMARY KEY (`fk_user_id`,`fk_group_id`);

--
-- Indexes for table `#REPLACEME#_userSession`
--
ALTER TABLE `#REPLACEME#_userSession`
  ADD PRIMARY KEY (`area`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `#REPLACEME#_collection`
--
ALTER TABLE `#REPLACEME#_collection`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `#REPLACEME#_group`
--
ALTER TABLE `#REPLACEME#_group`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `#REPLACEME#_menu`
--
ALTER TABLE `#REPLACEME#_menu`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `#REPLACEME#_sys_fields`
--
ALTER TABLE `#REPLACEME#_sys_fields`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `#REPLACEME#_tool`
--
ALTER TABLE `#REPLACEME#_tool`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `#REPLACEME#_user`
--
ALTER TABLE `#REPLACEME#_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
