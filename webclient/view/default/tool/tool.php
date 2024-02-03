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
 *  along with this program.  If not, see http://www.gnu.org/licenses/gpl-3.0.
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

$_collection = '';
if(isset($_GET['collection']) && !empty($_GET['collection'])) {
	$_collection = trim($_GET['collection']);
	$_collection = Summoner::validate($_collection,'digit') ? $_collection : '';
}

$_id = '';
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Summoner::validate($_id,'digit') ? $_id : '';
}

$_t = '';
if(isset($_GET['t']) && !empty($_GET['t'])) {
	$_t = trim($_GET['t']);
	$_t = Summoner::validate($_t,'nospace') ? $_t : '';
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
