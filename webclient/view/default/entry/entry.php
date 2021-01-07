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

require_once 'lib/mancubus.class.php';
$Mancubus = new Mancubus($DB,$Doomguy);
require_once 'lib/manageentry.class.php';
$ManageEntry = new Manageentry($DB,$Doomguy);
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

$TemplateData['fieldViewDefault'] = Summoner::themefile('entry/field-unknown.html', UI_THEME);
$TemplateData['entry'] = array();
$TemplateData['loadedCollection'] = array();
$TemplateData['storagePath'] = '';
$TemplateData['showEdit'] = false;

if(!empty($_collection) && !empty($_id)) {
	$TemplateData['loadedCollection'] = $Trite->load($_collection);
	if(!empty($TemplateData['loadedCollection'])) {
		$Mancubus->setCollection($Trite->param('id'));
		$TemplateData['entry'] = $Mancubus->getEntry($_id);
		$TemplateData['storagePath'] = PATH_WEB_STORAGE . '/' . $Trite->param('id') . '/' . $_id;
		$TemplateData['searchAction'] = 'index.php?p=collections&collection=' . $Trite->param('id');

		$ManageEntry->setCollection($Trite->param('id'));
		$TemplateData['showEdit'] = $ManageEntry->canEditEntry($_id);
	}
	else {
		$TemplateData['message']['content'] = 'Can not load given collection.';
		$TemplateData['message']['status'] = 'error';
	}
}
else {
	$TemplateData['message']['status'] = 'error';
	$TemplateData['message']['content'] = 'Missing required query parameters.';
}
