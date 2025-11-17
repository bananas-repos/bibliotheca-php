<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2023 Johannes Keßler
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

mb_http_output('UTF-8');
mb_internal_encoding('UTF-8');
ini_set('error_reporting',-1); // E_ALL & E_STRICT

# check request
$_urlToParse = filter_var($_SERVER['QUERY_STRING'],FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
if(!empty($_urlToParse)) {
	# see http://de2.php.net/manual/en/regexp.reference.unicode.php
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

# static helper class
require_once 'lib/summoner.class.php';
# general includes
require_once 'lib/doomguy.class.php';
require_once 'lib/spectre.class.php';
require_once 'lib/mancubus.class.php';
require_once 'lib/manageentry.class.php';
require_once 'lib/trite.class.php';

## DB connection
$DB = new mysqli(DB_HOST, DB_USERNAME,DB_PASSWORD, DB_NAME);
$driver = new mysqli_driver();
$driver->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
if ($DB->connect_errno) exit('Can not connect to MySQL Server');
$DB->set_charset("utf8mb4");
$DB->query("SET collation_connection = 'utf8mb4_unicode_ci'");

# user Object
$Doomguy = new Doomguy($DB);
# API object
$Spectre = new Spectre($DB, $Doomguy);

$_requestMode = "default";
if(isset($_GET['p']) && !empty($_GET['p'])) {
	$_requestMode = trim($_GET['p']);
	$_requestMode = Summoner::validate($_requestMode,'nospace') ? $_requestMode : "default";

	if(!$Spectre->allowedRequests($_requestMode)) $_requestMode = "default";
}
$_authKey = '';
if(isset($_GET['authKey']) && !empty($_GET['authKey'])) {
	$_authKey = trim($_GET['authKey']);
	$_authKey = Summoner::validate($_authKey,'nospace') ? $_authKey : '';
}

$_apiResult = array(
	'message' => 'Nothing to see here.',
	'status' => 200,
	'data' => array()
);
switch ($_requestMode) {
	case 'list':
		# get the latest 10 entris for given collection
		$_msg = 'Missing parameter with value: collection';
		$_status = 404;
		$_data = array();

		$_collection = false;
		if(isset($_GET['collection']) && !empty($_GET['collection'])) {
			$_collection = trim($_GET['collection']);
			$_collection = Summoner::validate($_collection,'digit') ? $_collection : false;
		}

		if(!empty($_collection)) {
			$_msg = 'Invalid collection.';
			$Mancubus = new Mancubus($DB,$Doomguy);
			$Trite = new Trite($DB,$Doomguy);
			$collectionInfo = $Trite->load($_collection);

			$Mancubus->setCollection($Trite->param('id'));
			$Mancubus->setQueryOptions(array('limit' => 10));

			$entries = $Mancubus->getEntries();
			if(!empty($entries)) {
				$_msg = 'Latest entries for collection: '.$collectionInfo['name'];
				$_status = 200;
				$_data = $entries;
			}
		}

		$_apiResult = array(
			'message' => $_msg,
			'status' => $_status,
			'data' => $_data
		);
	break;

	case 'add':
		# add a single new entry to given collection
		# authenticated by api token
		$_msg = 'Missing parameter with value: collection';
		$_status = 400;
		$_data = array();

		$Doomguy->authByApiToken($_authKey);
		if(!$Doomguy->isSignedIn()) {
			$_apiResult = array(
				'message' => "Missing API token.",
				'status' => 401,
				'data' => $_data
			);
			break;
		}

		$_collection = false;
		if(isset($_GET['collection']) && !empty($_GET['collection'])) {
			$_collection = trim($_GET['collection']);
			$_collection = Summoner::validate($_collection,'digit') ? $_collection : false;
		}

		if(!empty($_collection)) {
			$_msg = 'Invalid POST data.';
			$_data = $_REQUEST;

			$Mancubus = new Mancubus($DB,$Doomguy);
			$ManangeEntry = new Manageentry($DB,$Doomguy);

			$ManangeEntry->setCollection($_collection);
			$editFields = $ManangeEntry->getEditFields();

			if(!empty($_POST) && !empty($editFields)) {
				$fdata = $_POST;
				if(!empty($_FILES)) {
					$fupload = $Spectre->prepareFilesArray($_FILES);
				}

				$_owner = $Doomguy->param('id');
				$_group = $Doomguy->param('baseGroupId');
				$_rights = 'rwxrwxr--';

				foreach ($editFields as $fieldId=>$fieldData) {
					if(isset($fupload['name'][$fieldData['identifier']])) {
						$fieldData['uploadData'] = $fupload;
						$_fieldsToSave[$fieldData['identifier']] = $fieldData;
					}
					elseif(isset($fdata[$fieldData['identifier']])) {
						$_value = trim($fdata[$fieldData['identifier']]);
						if(!empty($_value)) {
							$fieldData['valueToSave'] = trim($fdata[$fieldData['identifier']]);

							$_fieldsToSave[$fieldData['identifier']] = $fieldData;
						}
					}
				}

				// special case. Title field should be always available.
				if(!empty($_fieldsToSave) && isset($_fieldsToSave['title'])) {
					$do = $ManangeEntry->create($_fieldsToSave, $_owner, $_group, $_rights);
					if(!empty($do)) {
						$_msg = 'Added entry: '.$_fieldsToSave['title']['valueToSave'];
						$_status = 200;
						$_data = array($_fieldsToSave, $_owner, $_group, $_rights);
					}
				}
			}
		}

		$_apiResult = array(
			'message' => $_msg,
			'status' => $_status,
			'data' => $_data
		);
	break;

	case 'addInfo':
		# return information about the given collection to create an ad call.
		$_msg = 'Missing parameter with value: collection';
		$_status = 404;
		$_data = array();

		$_collection = false;
		if(isset($_GET['collection']) && !empty($_GET['collection'])) {
			$_collection = trim($_GET['collection']);
			$_collection = Summoner::validate($_collection,'digit') ? $_collection : false;
		}

		if(!empty($_collection)) {
			$_msg = 'Invalid collection.';
			$Mancubus = new Mancubus($DB,$Doomguy);
			$Trite = new Trite($DB,$Doomguy);
			$collectionInfo = $Trite->load($_collection);

			$Mancubus->setCollection($Trite->param('id'));

			// just get one entry fpr given collection and then build the
			// json information about adding structure
			$entryStructure = $Mancubus->getEntryStructure();
			$structure = $Spectre->buildAddStructure($entryStructure['fields']);

			if(!empty($structure)) {
				$_msg = 'API POST and FILES data information for collection: '.$collectionInfo['name'];
				$_status = 200;
				$_data = $structure;
			}
		}

		$_apiResult = array(
			'message' => $_msg,
			'status' => $_status,
			'data' => $_data
		);
	break;

	case 'default':
	default:
		// do nothing
}

# header information
http_response_code($_apiResult['status']);
header('Content-type: application/json; charset=UTF-8');
echo json_encode($_apiResult);
