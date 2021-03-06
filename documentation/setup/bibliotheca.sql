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
  `defaultSortField` varchar(16) COLLATE utf8mb4_unicode_ci NOT NULL,
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

INSERT INTO `#REPLACEME#_group` (`id`, `name`, `description`, `protected`, `created`, `modificationuser`, `owner`, `group`, `rights`) VALUES
(1, 'Administration', 'Admin group', 1, '2020-05-31 21:25:29', 0, 1, 1, 'rwx------'),
(2, 'Users', 'Default user group', 1, '2020-05-31 21:25:29', 0, 1, 1, 'rwxr--r--'),
(3, 'Anonymous', 'Anonymous users', 1, '2020-05-31 21:25:29', 0, 1, 1, 'rwxr--r--');

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_menu`
--

DROP TABLE IF EXISTS `#REPLACEME#_menu`;
CREATE TABLE `#REPLACEME#_menu` (
  `id` int UNSIGNED NOT NULL,
  `text` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
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

INSERT INTO `#REPLACEME#_menu` (`id`, `text`, `action`, `icon`, `owner`, `group`, `rights`, `position`, `category`) VALUES
(1, 'Dashboard', '', 'home', 1, 1, 'rw-r--r--', 0, 'show'),
(2, 'Collections', 'collections', 'database', 1, 1, 'rw-r--r--', 1, 'show'),
(3, 'Tags', 'tags', 'tag', 1, 1, 'rw-r--r--', 2, 'show'),
(4, 'Add', 'manageentry', 'plus-circle', 1, 2, 'rw-rw----', 0, 'manage'),
(6, 'Tags', 'managetags', 'tag', 1, 2, 'rw-rw----', 2, 'manage'),
(7, 'Collections', 'managecolletions', 'database', 1, 2, 'rw-rw----', 3, 'manage'),
(8, 'Users', 'manageusers', 'users', 1, 1, 'rw-------', 4, 'manage'),
(9, 'Login', 'auth', '', 1, 1, 'rw-r--r--', 0, ''),
(10, 'Collection fields', 'managecollectionfields', '', 1, 2, 'rw-rw----', 0, ''),
(11, 'Entry', 'entry', '', 1, 1, 'rw-r--r--', 0, ''),
(12, 'Search', 'search', '', 1, 1, 'rw-r--r--', 0, ''),
(14, 'Tool', 'tool', '', 1, 2, 'rw-rw----', 0, ''),
(15, 'Advanced search', 'advancedsearch', '', 1, 1, 'rw-r--r--', 0, ''),
(16, 'Profile', 'profile', 'user', 1, 2, 'rw-rw----', 6, 'manage'),
(17, 'Groups', 'managegroups', 'users', 1, 1, 'rw-------', 5, 'manage'),
(18, 'Bulkedit', 'bulkedit', '', 1, 2, 'rw-rw----', 0, '');

-- --------------------------------------------------------

--
-- Table structure for table `#REPLACEME#_sys_fields`
--

DROP TABLE IF EXISTS `#REPLACEME#_sys_fields`;
CREATE TABLE `#REPLACEME#_sys_fields` (
  `id` int NOT NULL,
  `identifier` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `displayname` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `searchtype` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `createstring` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inputValidation` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
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

INSERT INTO `#REPLACEME#_sys_fields` (`id`, `identifier`, `displayname`, `type`, `searchtype`, `createstring`, `inputValidation`, `value`, `apiinfo`, `created`, `modificationuser`, `owner`, `group`, `rights`) VALUES
(1, 'title', 'Title', 'text', 'entryText', '`title` varchar(128) NOT NULL, ADD FULLTEXT (`title`)', '', NULL, 'string 128', NOW(), 0, 1, 1, 'rw-r--r--'),
(2, 'description', 'Description', 'text3', 'entryText', '`description` varchar(255) NULL DEFAULT NULL, ADD FULLTEXT (`description`)', '', NULL, 'string 255', NOW(), 0, 1, 1, 'rw-r--r--'),
(3, 'content', 'Main content', 'textarea', 'entryText', '`content` text NULL DEFAULT NULL, ADD FULLTEXT (`content`)', '', NULL, 'mysql text', NOW(), 0, 1, 1, 'rw-r--r--'),
(5, 'tag', 'Tag', 'lookupmultiple', 'tag', NULL, '', NULL, 'string 64', NOW(), 0, 1, 1, 'rw-r--r--'),
(6, 'category', 'Category', 'lookupmultiple', 'tag', NULL, '', NULL, 'string 64', NOW(), 0, 1, 1, 'rw-r--r--'),
(7, 'publisher', 'Publisher', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', NOW(), 0, 1, 1, 'rw-r--r--'),
(8, 'developer', 'Developer', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', NOW(), 0, 1, 1, 'rw-r--r--'),
(9, 'platform', 'Platform', 'selection', 'entrySingleText', '`platform` varchar(32) NULL DEFAULT NULL', '', 'PC,Xbox,Playstation,Nintendo,Nintendo Switch', 'One of PC,Xbox,Playstation,Nintendo,Nintendo Switch', NOW(), 0, 1, 1, 'rw-r--r--'),
(10, 'storage', 'Storage', 'lookupmultiple', 'tag', NULL, '', NULL, 'string 64', NOW(), 0, 1, 1, 'rw-r--r--'),
(13, 'rating', 'Rating', 'selection', 'entrySingleText', '`rating` varchar(16) NULL DEFAULT NULL', '', '0/10,2/10,3/10,4/10,5/10,6/10,7/10,8/10,9/10,10/10', 'One of 0/10,2/10,3/10,4/10,5/10,6/10,7/10,8/10,9/10,10/10', NOW(), 0, 1, 1, 'rw-r--r--'),
(14, 'year', 'Year', 'number', 'entrySingleNum', '`year` int(10) NULL, ADD INDEX (`year`)', '', NULL, 'int 10', NOW(), 0, 1, 1, 'rw-r--r--'),
(15, 'coverimage', 'Cover image', 'upload', NULL, NULL, '', NULL, 'One file in $_FILES[uploads] of post', NOW(), 0, 1, 1, 'rw-r--r--'),
(16, 'attachment', 'Attachments', 'upload_multiple', NULL, NULL, '', NULL, 'Multiple in $_FILES[uploads] of post', NOW(), 0, 1, 1, 'rw-r--r--'),
(17, 'os', 'Operating system and version', 'selection', 'entrySingleNum', '`os` varchar(32) NULL DEFAULT NULL', '', 'Windows 10,Windows 95, Windows 99,Windows XP, Windows ME, Windows 2000,Windows Vista,Windows 8', 'One of Windows 10,Windows 95, Windows 99,Windows XP, Windows ME, Windows 2000,Windows Vista,Windows 8', NOW(), 0, 1, 1, 'rw-r--r--'),
(18, 'actors', 'Actors', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', NOW(), NULL, 1, 1, 'rw-r--r--'),
(19, 'countries', 'Countries', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', NOW(), NULL, 1, 1, 'rw-r--r--'),
(20, 'directors', 'Directors', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', NOW(), NULL, 1, 1, 'rw-r--r--'),
(21, 'genres', 'Genres', 'lookupmultiple', 'tag', NULL, '', NULL, 'string 64', NOW(), NULL, 1, 1, 'rw-r--r--'),
(22, 'languages', 'Languages', 'lookupmultiple', 'tag', NULL, '', NULL, 'string 64', NOW(), NULL, 1, 1, 'rw-r--r--'),
(23, 'runtime', 'Runtime (min)', 'number', 'entrySingleNum', '`runtime` int(10) NULL, ADD INDEX (`runtime`)', '', NULL, 'int 10', NOW(), NULL, 1, 1, 'rw-r--r--'),
(24, 'imdbrating', 'IMDB Rating', 'text', 'entrySingleText', '`imdbrating` varchar(128) NULL DEFAULT NULL', '', NULL, 'string 128', NOW(), 0, 1, 1, 'rw-r--r--'),
(25, 'viewcount', 'View counter', 'number', 'entrySingleNum', '`viewcount` int(10) NULL, ADD INDEX (`viewcount`)', '', NULL, 'int 10', NOW(), 0, 1, 1, 'rw-r--r--'),
(26, 'writers', 'Writers', 'lookupmultiple', 'tag', NULL, 'allowSpace', NULL, 'string 64', NOW(), NULL, 1, 1, 'rw-r--r--'),
(27, 'localizedTitle', 'localized Title', 'text', 'entryText', '`localizedTitle` varchar(128) NOT NULL, ADD FULLTEXT (`localizedTitle`)', '', NULL, 'string 128', NOW(), '0', '1', '1', 'rw-r--r--');

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
(2, 'Game infos', 'Weblinks', 'gameinfo', '_self', 1, 1, 'rw-r--r--');

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

INSERT INTO `#REPLACEME#_user` (`id`, `login`, `password`, `name`, `active`, `apiToken`, `apiTokenValidDate`, `baseGroupId`, `protected`, `created`, `modificationuser`, `owner`, `group`, `rights`) VALUES
(1, 'admin', '$2y$10$BdLVGaeiQc12smmNKf4rk.2Dj6ockECsSlpx1eO7RWN3RbX2gYrai', 'Administrator', 1, NULL, NULL, 1, 1, '2019-09-01 17:22:02', 0, 1, 1, 'rwxr-----'),
(2, 'anonymoose', '', 'Anonymoose', 1, NULL, NULL, 3, 1, '2020-05-03 17:22:02', 0, 2, 3, 'rwxr--r--');

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
-- Dumping data for table `#REPLACEME#_user2group`
--

INSERT INTO `#REPLACEME#_user2group` (`fk_user_id`, `fk_group_id`) VALUES
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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `#REPLACEME#_sys_fields`
--
ALTER TABLE `#REPLACEME#_sys_fields`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `#REPLACEME#_tool`
--
ALTER TABLE `#REPLACEME#_tool`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `#REPLACEME#_user`
--
ALTER TABLE `#REPLACEME#_user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
