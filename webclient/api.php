<?php
/**
 * Bibliotheca webclient
 *
 * Copyright 2018-2020 Johannes Keßler
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
define('DEBUG',false);

require_once './config/path.php';
require_once './config/system.php';
require_once './config/database.php';

# static helper class
require_once 'lib/summoner.class.php';
# general includes
require_once 'lib/doomguy.class.php';
require_once 'lib/spectre.class.php';
require_once 'lib/mancubus.class.php';
require_once 'lib/manageentry.class.php';
require_once 'lib/trite.class.php';

## main vars
# database object
$DB = false;
$Spectre = false;

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
$_authKey = false;
if(isset($_GET['authKey']) && !empty($_GET['authKey'])) {
	$_authKey = trim($_GET['authKey']);
	$_authKey = Summoner::validate($_authKey,'nospace') ? $_authKey : false;
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
						$_data = array();
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
