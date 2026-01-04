<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2026 Johannes Keßler
 *
 * This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program. If not, see http://www.gnu.org/licenses/gpl-3.0
 */

require_once './config/config.php';

const BIB_VERSION = '1.8.1 - Harobed Village';

mb_http_output('UTF-8');
mb_internal_encoding('UTF-8');
error_reporting(-1); // E_ALL & E_STRICT

# check request
$_urlToParse = filter_var($_SERVER['QUERY_STRING'],FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
if(!empty($_urlToParse)) {
	if(preg_match('/[\p{C}\p{M}\p{Sc}\p{Sk}\p{So}\p{Zl}\p{Zp}]/u',$_urlToParse) === 1) {
		die('Malformed request. Make sure you know what you are doing.');
	}
}

# set the error reporting
ini_set('log_errors',true);
if(DEBUG === true) {
	ini_set('display_errors',true);
}
else {
	ini_set('display_errors',false);
}

# time settings
date_default_timezone_set(TIMEZONE);

# i18n
require_once 'lib/i18n.class.php';
$I18n = new I18n();

# static helper class
require_once 'lib/summoner.class.php';
# general includes
require_once 'lib/doomguy.class.php';
require_once 'lib/gorenest.class.php';

## main vars
# the template data as an array
# and some defaults
$TemplateData = array();
$TemplateData['pagination'] = array();
$TemplateData['navSearchAction'] = array();
$TemplateData['pageTitle'] = 'Dashboard';
# the view
$View = Summoner::themefile('dashboard/dashboard.html', UI_THEME);
# the script
$ViewScript = Summoner::themefile('dashboard/dashboard.php', UI_THEME);
# the messages
$ViewMessage = Summoner::themefile('system/message.php',UI_THEME);
# the menu
$ViewMenu = Summoner::themefile('system/menu.php',UI_THEME);

## DB connection
$DB = new mysqli(DB_HOST, DB_USERNAME,DB_PASSWORD, DB_NAME);
$driver = new mysqli_driver();
$driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
if ($DB->connect_errno) exit('Can not connect to MySQL Server');
$DB->set_charset("utf8mb4");
$DB->query("SET collation_connection = 'utf8mb4_unicode_ci'");

# user Object
$Doomguy = new Doomguy($DB);
# menu Object
$Gorenest = new GoreNest($DB,$Doomguy);

$_requestMode = "dashboard";
if(isset($_GET['p']) && !empty($_GET['p'])) {
	$_requestMode = trim($_GET['p']);
	$_requestMode = Summoner::validate($_requestMode,'nospace') ? $_requestMode : "dashboard";

	$_validPages = $Gorenest->allowedPageRequests();
	$_validPages["dashboard"] = "dashboard";
	if(!isset($_validPages[$_requestMode])) $_requestMode = "dashboard";

	$ViewScript = Summoner::themefile($_requestMode.'/'.$_requestMode.'.php', UI_THEME);
	$View = Summoner::themefile($_requestMode.'/'.$_requestMode.'.html', UI_THEME);
}

# now include the script
# this sets information into $Data and can overwrite $View
if(!empty($ViewScript)) {
	require_once $ViewScript;
}

if(!empty($TemplateData['refresh'])) {
	header("Location: ".$TemplateData['refresh']);
}

# header information
header('Content-type: text/html; charset=UTF-8');

## now inlcude the main view
require_once Summoner::themefile('main.php', UI_THEME);

$DB->close();
