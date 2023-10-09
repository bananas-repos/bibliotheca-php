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
require_once 'lib/manageentry.class.php';
$ManageEntry = new Manageentry($DB,$Doomguy);

$TemplateData['pageTitle'] = 'Manage entry - ';
$TemplateData['editFields'] = array();
$TemplateData['editData'] = array();
$TemplateData['loadedCollection'] = '';
$TemplateData['storagePath'] = '';
$TemplateData['existingCollections'] = array();

$TemplateData['_editFieldViewDefault'] = Summoner::themefile('manageentry/field-unknown.html', UI_THEME);

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

if(!empty($_collection)) {
	$TemplateData['loadedCollection'] = $Trite->load($_collection, "write");

	if(!empty($TemplateData['loadedCollection'])) {
		$ManageEntry->setCollection($Trite->param('id'));

		$TemplateData['editFields'] = $ManageEntry->getEditFields();
		$TemplateData['availableTools'] = $Trite->getAvailableTools();

		$TemplateData['pageTitle'] = 'Add - '.$Trite->param('name');

		if(!empty($_id)) {
			$TemplateData['storagePath'] = PATH_WEB_STORAGE . '/' . $_collection . '/' . $_id;

			// prefill template data. Used also later to check if on edit mode
			$TemplateData['editData'] = $ManageEntry->getEditData($_id);
			// special case. Title field should be always available.
			if(!isset($TemplateData['editData']['title'])) {
				$TemplateData['message']['content'] = "Entry has no value in title field.";
				$TemplateData['message']['status'] = "error";
			}
			else {
				$TemplateData['pageTitle'] = 'Edit - '.$TemplateData['editData']['title'].' - '.$Trite->param('name');
			}
		}

		if(isset($_POST['submitForm'])) {
			$fdata = $_POST['fdata'];
			$fupload = array('name' => ''); // match $_FILES
			if(!empty($_FILES) && isset($_FILES['fdata'])) {
				$fupload = $_FILES['fdata'];
			}
			$_fieldsToSave = array();
			if (!empty($fdata)) {
				// default
				$_owner = $Doomguy->param('id');
				$_group = $Trite->param('group');
				$_rights = $Trite->param('rights');

				if(!empty($fdata['rights'])) {
					$_rightsString = Summoner::prepareRightsString($fdata['rights']);
					if(!empty($_rightsString)) {
						$_rights = $_rightsString;
					}
				}

				foreach ($TemplateData['editFields'] as $fieldId=>$fieldData) {
					if(isset($fdata[$fieldData['identifier']])) {
						$_value = trim($fdata[$fieldData['identifier']]);
						$fieldData['valueToSave'] = trim($fdata[$fieldData['identifier']]);
						$_fieldsToSave[$fieldData['identifier']] = $fieldData;
					} elseif(isset($fupload['name'][$fieldData['identifier']])) { // special case upload
						if(isset($fdata[$fieldData['identifier']."_delete"])) {
							$fieldData['deleteData'] = $fdata[$fieldData['identifier']."_delete"];
						}

						// $_FILES data is combined if multiple
						$fieldData['uploadData'] = $fupload;

						$_fieldsToSave[$fieldData['identifier']] = $fieldData;
					}
				}

				// special case. Title field should be always available.
				if(!empty($TemplateData['editData']['title'])) { // EDIT
					if(isset($fdata['doDelete'])) {
						$do = $ManageEntry->delete($_id);
						if ($do === true) {
							$TemplateData['refresh'] = 'index.php?p=collections&collection='.$_collection;
						} else {
							$TemplateData['message']['content'] = "Entry could not be removed.";
							$TemplateData['message']['status'] = "error";
						}
					} elseif (!empty($_fieldsToSave) && isset($_fieldsToSave['title'])) {
						$do = $ManageEntry->create($_fieldsToSave, $_owner, $_group, $_rights, $_id);
						if ($do !== 0) {
							$TemplateData['refresh'] = 'index.php?p=entry&collection='.$_collection.'&id='.$_id;
						} else {
							$TemplateData['message']['content'] = "Entry could not be updated.";
							$TemplateData['message']['status'] = "error";
						}
					}
				}
				else { // ADD
					// special case. Title field should be always available.
					if (!empty($_fieldsToSave) && !empty($_fieldsToSave['title']['valueToSave'])) {
						$do = $ManageEntry->create($_fieldsToSave, $_owner, $_group, $_rights);
						if (!empty($do)) {
							$TemplateData['message']['content'] = "<a href='index.php?p=entry&collection=".$_collection."&id=".$do."'>View your new entry</a> | <a href='index.php?p=manageentry&collection=".$_collection."&id=".$do."'>Edit your new entry</a>";
							$TemplateData['message']['status'] = "success";
						} else {
							// use editData to display given data
							$TemplateData['editData'] = $fdata;
							$TemplateData['message']['content'] = "Entry could not be added.";
							$TemplateData['message']['status'] = "error";
						}
					} else {
						// use editData to display given data
						$TemplateData['editData'] = $fdata;
						$TemplateData['message']['content'] = "Provide at least 'Title'.";
						$TemplateData['message']['status'] = "error";
					}
				}
			}
		}
	}
	else {
		$TemplateData['message']['content'] = "Collection could not be loaded.";
		$TemplateData['message']['status'] = "error";
		$TemplateData['existingCollections'] = $Trite->getCollections("write");
	}
}
else {
	$TemplateData['pageTitle'] .= 'collection overview';
	$TemplateData['existingCollections'] = $Trite->getCollections("write");
}
