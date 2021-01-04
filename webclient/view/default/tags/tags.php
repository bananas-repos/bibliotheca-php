<?php
/**
 * Bibliotheca webclient
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

require_once 'lib/mancubus.class.php';
$Mancubus = new Mancubus($DB,$Doomguy);
require_once 'lib/trite.class.php';
$Trite = new Trite($DB,$Doomguy);

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

$TemplateData['loadedCollection'] = array();
$TemplateData['tags'] = array();
$TemplateData['search'] = false;

$_search = false;
if(isset($_POST['navSearch'])) {
	$_search = trim($_POST['navSearch']);
	$_search = Summoner::validate($_search,'text') ? $_search :  false;
}


if(!empty($_collection)) {
	$TemplateData['loadedCollection'] = $Trite->load($_collection);
	if(!empty($TemplateData['loadedCollection'])) {
		$TemplateData['searchAction'] = 'index.php?p=tags&collection='.$Trite->param('id');
		$Mancubus->setCollection($Trite->param('id'));
		//$TemplateData['tags'] = $Mancubus->getTags($_search);
		$TemplateData['tags'] = $Trite->getTags($_search);
		if(!empty($_search)) {
			$TemplateData['search'] = $_search;
		}

		$Trite->getTags();
	}
	else {
		$TemplateData['message']['content'] = "Can not load given collection.";
		$TemplateData['message']['status'] = "error";
	}
}
else {
	$TemplateData['collections'] = $Trite->getCollections();
}
