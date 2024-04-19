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

require_once 'lib/mancubus.class.php';
$Mancubus = new Mancubus($DB,$Doomguy);
require_once 'lib/trite.class.php';
$Trite = new Trite($DB,$Doomguy);

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

$TemplateData['pageTitle'] = 'Tags - ';
$TemplateData['loadedCollection'] = array();
$TemplateData['tags'] = array();
$TemplateData['search'] = array();

$_search = '';
if(isset($_GET['navSearch'])) {
	$_search = trim($_GET['navSearch']);
	$_search = urldecode($_search);
	$_search = Summoner::validate($_search) ? $_search : '';
}


if(!empty($_collection)) {
	$TemplateData['loadedCollection'] = $Trite->load($_collection);
	if(!empty($TemplateData['loadedCollection'])) {
		$TemplateData['navSearchAction'] = array('p'  => 'tags', 'collection'  => $Trite->param('id'));
		$Mancubus->setCollection($Trite->param('id'));
		//$TemplateData['tags'] = $Mancubus->getTags($_search);
		$TemplateData['tags'] = $Trite->getTags($_search);
		if(!empty($_search)) {
			$TemplateData['search'] = $_search;
		}

		$Trite->getTags();

		$TemplateData['pageTitle'] = $Trite->param('name');
	}
	else {
		$TemplateData['message']['content'] = $I18n->t('global.message.couldNotLoadCollection');
		$TemplateData['message']['status'] = "error";
	}
}
else {
	$TemplateData['pageTitle'] .= 'collection overview';
	$TemplateData['collections'] = $Trite->getCollections();
}
