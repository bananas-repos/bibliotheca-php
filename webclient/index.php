<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2021 Johannes Keßler
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once './config/config.php';

mb_http_output('UTF-8');
mb_internal_encoding('UTF-8');
ini_set('error_reporting',-1); // E_ALL & E_STRICT

# check request
$_urlToParse = filter_var($_SERVER['QUERY_STRING'],FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
if(!empty($_urlToParse)) {
	# see http://de2.php.net/manual/en/regexp.reference.unicode.php
	if(preg_match('/[\p{C}\p{M}\p{Sc}\p{Sk}\p{So}\p{Zl}\p{Zp}]/u',$_urlToParse) === 1) {
		die('Malformed request. Make sure you know what you are doing.');
	}
}

# set the error reporting
ini_set('log_errors',true);
ini_set('error_log',PATH_SYSTEMOUT.'/error.log');
if(DEBUG === true) {
	ini_set('display_errors',true);
}
else {
	ini_set('display_errors',false);
}

# time settings
date_default_timezone_set(TIMEZONE);

# static helper class
require_once 'lib/summoner.class.php';
# general includes
require_once 'lib/doomguy.class.php';
require_once 'lib/gorenest.class.php';


## main vars
# database object
$DB = false;
# the template data as an array
# and some defaults
$TemplateData = array();
$TemplateData['pagination'] = array();
$TemplateData['searchAction'] = 'index.php';
$TemplateData['pageTitle'] = 'Dashboard';
# the view
$View = Summoner::themefile('dashboard/dashboard.html', UI_THEME);
# the script
$ViewScript = Summoner::themefile('dashboard/dashboard.php', UI_THEME);
# the messages
$ViewMessage = Summoner::themefile('system/message.php',UI_THEME);
# the menu
$ViewMenu = Summoner::themefile('system/menu.php',UI_THEME);
# the pagination
$ViewPagination = Summoner::themefile('system/pagination.html',UI_THEME);

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
$Gorenest->loadMenu();

$_requestMode = false;
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
