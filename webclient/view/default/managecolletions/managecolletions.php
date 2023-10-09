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
$TemplateData['simpleSearchFields'] = array();

$TemplateData['pageTitle'] = 'Manage collection';

// @todo providing the id is enough
$_editMode = false;
if(isset($_GET['m']) && !empty($_GET['m'])) {
	if($_GET['m'] == "edit") {
		$_editMode = true;
	}
}

$_id ='';
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Summoner::validate($_id,'digit') ? $_id : '';
}

if($_editMode === true && !empty($_id)) {
	$TemplateData['editData'] = $ManangeCollections->getEditData($_id);
	$ManangeCollectionFields->setCollection($_id);
	$TemplateData['existingFields'] = $ManangeCollectionFields->getExistingFields();
	$TemplateData['simpleSearchFields'] = $ManangeCollectionFields->getSimpleSearchFields();
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
		$_saveData['defaultSearchField'] = $fdata['defaultSearchField'];
		$_saveData['defaultSortField'] = $fdata['defaultSortField'];
		$_saveData['defaultSortOrder'] = $fdata['defaultSortOrder'];
		$_saveData['id'] = $_id;

		$_saveData['tool'] = array();
		if(isset($fdata['tool'])) {
			$_saveData['tool'] = $fdata['tool'];
		}

		$_saveData['doRightsForEntries'] = false;
		if(isset($fdata['doRightsForEntries'])) {
			$_saveData['doRightsForEntries'] = true;
		}

		$_saveData['advancedSearchTableFields'] = '';
		if(isset($fdata['advancedSearchTableFields'])) {
			$_saveData['advancedSearchTableFields'] = implode(',',$fdata['advancedSearchTableFields']);
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
