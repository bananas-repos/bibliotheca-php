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

require_once 'lib/trite.class.php';
$Trite = new Trite($DB,$Doomguy);
require_once 'lib/managetags.class.php';
$ManageTags = new ManageTags($DB,$Doomguy);

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

$TemplateData['loadedCollection'] = array();
$TemplateData['collections'] = array();
$TemplateData['pageTitle'] = 'Manage tags';

if(!empty($_collection)) {
	$TemplateData['loadedCollection'] = $Trite->load($_collection, "write");
	if(!empty($TemplateData['loadedCollection'])) {
		$ManageTags->setCollection($_collection);
		if(isset($_POST['submitForm'])) {
			$fdata = $_POST['fdata'];
			$do = array();
			if(!empty($fdata)) {
				foreach ($fdata as $ident=>$data) {
					$do[] = $ManageTags->doWithTag($ident, $data);
				}
			}
			if(!empty($do)) {
				if(empty(implode($do))) {
					$TemplateData['refresh'] = 'index.php?p=managetags&collection='.$_collection;
				}
				else {
					$TemplateData['message']['content'] = implode('<br / >',$do);
					$TemplateData['message']['status'] = "error";
				}
			}
			else {
				$TemplateData['message']['content'] = $I18n->t('managetags.message.executionError');
				$TemplateData['message']['status'] = "error";
			}
		}
		else {
			$TemplateData['tags'] = $Trite->getTags();
			if(empty($TemplateData['tags'])) {
				$TemplateData['message']['content'] = $I18n->t('managetags.message.notTagsAvailable');
				$TemplateData['message']['status'] = "warning";
			}
		}
	}
	else {
		$TemplateData['message']['content'] = $I18n->t('global.message.couldNotLoadCollection');
		$TemplateData['message']['status'] = "error";
	}
}
else {
	$TemplateData['collections'] = $Trite->getCollections("write");
}
