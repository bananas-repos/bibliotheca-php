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

require_once 'lib/mancubus.class.php';
$Mancubus = new Mancubus($DB,$Doomguy);
require_once 'lib/trite.class.php';
$Trite = new Trite($DB,$Doomguy);

$_collection = false;
if(isset($_GET['collection']) && !empty($_GET['collection'])) {
	$_collection = trim($_GET['collection']);
	$_collection = Summoner::validate($_collection,'digit') ? $_collection : false;
}

// field id to search within
$_fid = false;
if(isset($_GET['fid']) && !empty($_GET['fid'])) {
	$_fid = trim($_GET['fid']);
	$_fid = Summoner::validate($_fid,'digit') ? $_fid : false;
}

// field value to look up
$_fv = false;
if(isset($_GET['fv']) && !empty($_GET['fv'])) {
	$_fv = trim($_GET['fv']);
	$_fv = Summoner::validate($_fv,'text') ? $_fv : false;
}

$_search = false;
if(isset($_POST['navSearch'])) {
	$_search = trim($_POST['navSearch']);
	$_search = Summoner::validate($_search,'text') ? $_search :  false;
}

require_once(Summoner::themefile('system/pagination_before.php',UI_THEME));

$TemplateData['loadedCollection'] = array();
$TemplateData['storagePath'] = '';
$TemplateData['entries'] = array();
$TemplateData['collections'] = array();
$TemplateData['search'] = false;
// needed for pagination link building
$TemplateData['pagination']['currentGetParameters']['p'] = 'collections';
$TemplateData['pagination']['currentGetParameters']['collection'] = $_collection;

if(!empty($_collection)) {
	$TemplateData['loadedCollection'] = $Trite->load($_collection);
	if(!empty($TemplateData['loadedCollection'])) {
		$Mancubus->setCollection($Trite->param('id'));
		$Mancubus->setQueryOptions($_queryOptions); // this comes from pagination_before!
		$TemplateData['storagePath'] = PATH_WEB_STORAGE . '/' . $_collection;
		$TemplateData['entryLinkPrefix'] = "index.php?p=entry&collection=".$_collection;
		$TemplateData['searchAction'] = 'index.php?p=collections&collection='.$_collection;

		if (!empty($_fv) && !empty($_fid)) {
			$TemplateData['entries'] = $Mancubus->getEntriesByFieldValue($_fid, $_fv);
			$TemplateData['search'] = $_fv;

			$TemplateData['pagination']['currentGetParameters']['fid'] = $_fid;
			$TemplateData['pagination']['currentGetParameters']['fv'] = $_fv;
		} else {
			$TemplateData['entries'] = $Mancubus->getEntries($Trite->param('defaultSearchField'),$_search,true);
			if (!empty($_search)) {
				$TemplateData['search'] = $_search;
			}
		}
	}
	else {
		$TemplateData['message']['content'] = "Can not load given collection.";
		$TemplateData['message']['status'] = "error";
	}
}
else {
	$TemplateData['collections'] = $Trite->getCollections();
}

require_once(Summoner::themefile('system/pagination_after.php',UI_THEME));
