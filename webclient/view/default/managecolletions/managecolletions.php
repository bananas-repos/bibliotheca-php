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

require_once 'lib/managecollections.class.php';
$ManangeCollections = new ManageCollections($DB,$Doomguy);
require_once 'lib/managecollectionfields.class.php';
$ManangeCollectionFields = new ManageCollectionFields($DB,$Doomguy);

$TemplateData['existingCollections'] = $ManangeCollections->getCollections();
$TemplateData['ownerSelection'] = $ManangeCollections->getUsersForSelection();
$TemplateData['groupSelection'] = $ManangeCollections->getGroupsForSelection();
$TemplateData['toolSelection'] = $ManangeCollections->getToolsForSelection();
// default rights
$TemplateData['editData']['rights'] = Summoner::prepareRightsArray('rwxr--r--');
// tool needs to be preset
$TemplateData['editData']['tool'] = array();
$TemplateData['existingFields'] = array();

$TemplateData['pageTitle'] = 'Manage collection';

// @todo providing the id is enough
$_editMode = false;
if(isset($_GET['m']) && !empty($_GET['m'])) {
	if($_GET['m'] == "edit") {
		$_editMode = true;
	}
}

$_id = false;
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Summoner::validate($_id,'digit') ? $_id : false;
}

if($_editMode === true && !empty($_id)) {
	$TemplateData['editData'] = $ManangeCollections->getEditData($_id);
	$ManangeCollectionFields->setCollection($_id);
	$TemplateData['existingFields'] = $ManangeCollectionFields->getExistingFields();
	if(!isset($TemplateData['editData']['name'])) {
		$TemplateData['refresh'] = 'index.php?p=managecolletions';
	}
}

$_saveData = array();
if(isset($_POST['submitForm'])) {
	$fdata = $_POST['fdata'];
	if (!empty($fdata)) {
		$_saveData['name'] = trim($fdata['name']);
		$_saveData['description'] = trim($fdata['description']);
		$_saveData['owner'] = trim($fdata['owner']);
		$_saveData['group'] = trim($fdata['group']);
		$_saveData['rights'] = Summoner::prepareRightsString($fdata['rights']);
		$_saveData['defaultSearchField'] = trim($fdata['defaultSearchField']);
		$_saveData['id'] = $_id;

		$_saveData['tool'] = array();
		if(isset($fdata['tool'])) {
			$_saveData['tool'] = $fdata['tool'];
		}

		$_saveData['doRightsForEntries'] = false;
		if(isset($fdata['doRightsForEntries'])) {
			$_saveData['doRightsForEntries'] = true;
		}

		if(!empty($TemplateData['editData']['name'])) { // EDIT
			if(isset($fdata['doDelete'])) {
				$do = $ManangeCollections->deleteCollection($_id);
				if ($do === true) {
					$TemplateData['refresh'] = 'index.php?p=managecolletions';
				} else {
					$TemplateData['message']['content'] = "Collection could not be deleted.";
					$TemplateData['message']['status'] = "error";
				}
			}
			else {
				if (Summoner::validate($_saveData['name'], 'nospace') === true
					&& isset($TemplateData['groupSelection'][$_saveData['group']])
					&& isset($TemplateData['ownerSelection'][$_saveData['owner']])
				) {
					$do = $ManangeCollections->updateCollection($_saveData);
					if ($do === true) {
						$TemplateData['refresh'] = 'index.php?p=managecolletions';
					} else {
						$TemplateData['message']['content'] = "Collection could not be updated.";
						$TemplateData['message']['status'] = "error";
					}
				} else {
					$TemplateData['message']['content'] = "Provide name, owner, group and valid rights.";
					$TemplateData['message']['status'] = "error";
				}
			}
		}
		else { // ADD
			if (!empty($_saveData['name']) && !empty($_saveData['owner']) && !empty($_saveData['group']) && !empty($_saveData['rights'])) {
				if (Summoner::validate($_saveData['name'], 'nospace') === true
					&& isset($TemplateData['groupSelection'][$_saveData['group']])
					&& isset($TemplateData['ownerSelection'][$_saveData['owner']])
				) {
					$do = $ManangeCollections->createCollection($_saveData);
					if ($do === true) {
						$TemplateData['refresh'] = 'index.php?p=managecolletions';
					} else {
						$TemplateData['message']['content'] = "Collection could not be created.";
						$TemplateData['message']['status'] = "error";
					}
				} else {
					$TemplateData['message']['content'] = "Provide name, owner, group and valid rights.";
					$TemplateData['message']['status'] = "error";
				}
			}
		}

	}
}
