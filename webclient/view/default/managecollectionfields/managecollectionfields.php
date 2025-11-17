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

/**
 * manage the fields from a existing collection
 */

require_once 'lib/managecollections.class.php';
$ManangeCollections = new ManageCollections($DB,$Doomguy);
require_once 'lib/managecollectionfields.class.php';
$ManangeCollectionFields = new ManageCollectionFields($DB,$Doomguy);
$TemplateData['availableFields'] = $ManangeCollectionFields->getAvailableFields();
$TemplateData['existingFields'] = array();

$TemplateData['pageTitle'] = 'Manage collection fields';

$_id = '';
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Summoner::validate($_id,'digit') ? $_id : '';
}

if(!empty($_id)) {
	$TemplateData['editData'] = $ManangeCollections->getEditData($_id);
	$ManangeCollectionFields->setCollection($_id);
	$TemplateData['existingFields'] = $ManangeCollectionFields->getExistingFields();
	// reduce the selection for only the new ones
	if(!empty($TemplateData['existingFields'])) {
		foreach ($TemplateData['existingFields'] as $k=>$v) {
			unset($TemplateData['availableFields'][$k]);
		}
	}

	// if loading failed redirect to overview
	if(!isset($TemplateData['editData']['name'])) {
		$TemplateData['refresh'] = 'index.php?p=managecolletions';
	}
}

if(isset($_POST['submitForm'])) {
	$fdata = $_POST['fdata'];
	if (!empty($fdata)) {
		$_fieldSortString = trim($fdata['fieldSortString']);
		if($ManangeCollectionFields->validateFieldSortString($_fieldSortString)) {
			$do = $ManangeCollectionFields->updateFields($_fieldSortString);
			if ($do === true) {
				$TemplateData['refresh'] = 'index.php?p=managecollectionfields&id='.$_id;
			} else {
				$TemplateData['message']['content'] = $I18n->t('managefields.message.notUpdate');
				$TemplateData['message']['status'] = "error";
			}
		}
		else {
			$TemplateData['message']['content'] = $I18n->t('managefields.message.provideValidFields');
			$TemplateData['message']['status'] = "error";
		}
	}
}
