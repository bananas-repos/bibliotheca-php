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

/**
 * manage the fields from a existing collection
 */

require_once 'lib/managecollections.class.php';
$ManangeCollections = new ManageCollections($DB,$Doomguy);
require_once 'lib/managecollectionfields.class.php';
$ManangeCollectionFields = new ManageCollectionFields($DB,$Doomguy);
$TemplateData['availableFields'] = $ManangeCollectionFields->getAvailableFields();
$TemplateData['existingFields'] = array();

$_id = false;
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Summoner::validate($_id,'digit') ? $_id : false;
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
				$TemplateData['message']['content'] = "Fields could not be updated.";
				$TemplateData['message']['status'] = "error";
			}
		}
		else {
			$TemplateData['message']['content'] = "Please provide valid fields.";
			$TemplateData['message']['status'] = "error";
		}
	}
}
