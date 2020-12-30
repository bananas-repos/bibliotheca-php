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

require_once 'lib/managecollections.class.php';
$ManangeCollections = new ManageCollections($DB,$Doomguy);
require_once 'lib/manageentry.class.php';
$ManangeEntry = new Manageentry($DB,$Doomguy);

$TemplateData['editFields'] = array();
$TemplateData['editData'] = array();
$TemplateData['loadedCollection'] = '';
$TemplateData['storagePath'] = '';

$TemplateData['existingCollections'] = $ManangeCollections->getCollections();
$TemplateData['_editFieldViewDefault'] = Summoner::themefile('manageentry/field-unknown.html', UI_THEME);

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

if(!empty($_collection)) {
	$setCollection = $ManangeCollections->getCollection($_collection, "write");

	if(!empty($setCollection)) {
		$ManangeEntry->setCollection($_collection);
		$TemplateData['loadedCollection'] = $setCollection;

		$TemplateData['editFields'] = $ManangeEntry->getEditFields();
		$TemplateData['availableTools'] = $ManangeCollections->getAvailableTools($_collection);

		if(!empty($_id)) {
			$TemplateData['storagePath'] = PATH_WEB_STORAGE . '/' . $_collection . '/' . $_id;

			// prefill template data. Used also later to check if on edit mode
			$TemplateData['editData'] = $ManangeEntry->getEditData($_id);
			// special case. Title field should be always available.
			if(!isset($TemplateData['editData']['title'])) {
				$TemplateData['message']['content'] = "Entry has no value in title field.";
				$TemplateData['message']['status'] = "error";
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
				// @todo there is no setting for individual rights available yet
				$_owner = $Doomguy->param('id');
				$_group = $Doomguy->param('baseGroupId');
				$_rights = 'rwxrwxr--';

				foreach ($TemplateData['editFields'] as $fieldId=>$fieldData) {
					if(isset($fdata[$fieldData['identifier']])) {
						$_value = trim($fdata[$fieldData['identifier']]);
						$fieldData['valueToSave'] = trim($fdata[$fieldData['identifier']]);
						$_fieldsToSave[$fieldData['identifier']] = $fieldData;
					} elseif(isset($fupload['name'][$fieldData['identifier']])) {
						if(isset($fdata[$fieldData['identifier']."_delete"])) {
							$fieldData['deleteData'] = $fdata[$fieldData['identifier']."_delete"];
						}
						// special case upload
						// $_FILES data is combinend
						$fieldData['uploadData'] = $fupload;

						$_fieldsToSave[$fieldData['identifier']] = $fieldData;
					}
				}

				// special case. Title field should be always available.
				if(!empty($TemplateData['editData']['title'])) { // EDIT
					if(isset($fdata['doDelete'])) {
						$do = $ManangeEntry->delete($_id);
						if ($do === true) {
							$TemplateData['refresh'] = 'index.php?p=collections&collection='.$_collection;
						} else {
							$TemplateData['message']['content'] = "Entry could not be removed.";
							$TemplateData['message']['status'] = "error";
						}
					} elseif (!empty($_fieldsToSave) && isset($_fieldsToSave['title'])) {
						$do = $ManangeEntry->create($_fieldsToSave, $_owner, $_group, $_rights, $_id);
						if ($do !== 0) {
							$TemplateData['refresh'] = 'index.php?p=manageentry&collection='.$_collection.'&id='.$_id;
						} else {
							$TemplateData['message']['content'] = "Entry could not be updated.";
							$TemplateData['message']['status'] = "error";
						}
					}
				}
				else { // ADD
					// special case. Title field should be always available.
					if (!empty($_fieldsToSave) && isset($_fieldsToSave['title'])) {
						$do = $ManangeEntry->create($_fieldsToSave, $_owner, $_group, $_rights);
						if (!empty($do)) {
							$TemplateData['message']['content'] = "New entry: <a href='index.php?p=manageentry&collection=".$_collection."&id=".$do."'>".$do."</a>";
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
	}
}
