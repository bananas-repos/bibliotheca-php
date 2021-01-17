<?php
/**
 * Bibliotheca
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

require_once 'lib/tentacle.class.php';
$Tools = new Tentacle($DB,$Doomguy);
require_once 'lib/managecollectionfields.class.php';
$ManangeCollectionsFields = new ManageCollectionFields($DB, $Doomguy);
require_once  'lib/manageentry.class.php';
$Manageentry = new Manageentry($DB,$Doomguy);
require_once 'lib/trite.class.php';
$Trite = new Trite($DB,$Doomguy);

$TemplateData['pageTitle'] = 'Tool';
$TemplateData['tool'] = array();
$TemplateData['tool']['viewFile'] = '';
$TemplateData['collection'] = array();
$TemplateData['editEntry'] = array();

$_collection = false;
if(isset($_GET['collection']) && !empty($_GET['collection'])) {
	$_collection = trim($_GET['collection']);
	$_collection = Summoner::validate($_collection,'digit') ? $_collection : false;
}

$_id = false;
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Summoner::validate($_id,'digit') ? $_id : false;
}

$_t = false;
if(isset($_GET['t']) && !empty($_GET['t'])) {
	$_t = trim($_GET['t']);
	$_t = Summoner::validate($_t,'nospace') ? $_t : false;
}

if(!empty($_collection) && !empty($_t)) {
	$collection = $Trite->load($_collection,"write");
	$toolInfo = $Tools->validate($_t);

	if(!empty($collection) && !empty($toolInfo)) {
		$TemplateData['tool'] = $toolInfo;
		$TemplateData['collection'] = $collection;

		$ManangeCollectionsFields->setCollection($Trite->param('id'));
		$Manageentry->setCollection($Trite->param('id'));

		if(!empty($_id)) {
			$TemplateData['editEntry'] = $Manageentry->getEditData($_id);
		}

		$_toolFile = Summoner::themefile('tool/tool-'.$toolInfo['action'].'.php', UI_THEME);
		$_toolViewFile = Summoner::themefile('tool/tool-'.$toolInfo['action'].'.html', UI_THEME);
		if(file_exists($_toolFile) && file_exists($_toolViewFile)) {
			require_once $_toolFile;
			$TemplateData['tool']['viewFile'] = $_toolViewFile;

			$TemplateData['pageTitle'] .= ' - '.$toolInfo['name'];
		}
		else {
			$TemplateData['tool']['viewFile'] = '';
			$TemplateData['message']['content'] = "Required tool files can not be found.";
			$TemplateData['message']['status'] = "error";
		}
	}
	else {
		$TemplateData['message']['content'] = "Collection nor tool could not be loaded.";
		$TemplateData['message']['status'] = "error";
	}
}
