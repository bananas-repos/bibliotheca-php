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

$_collection = '';
if(isset($_GET['collection']) && !empty($_GET['collection'])) {
	$_collection = trim($_GET['collection']);
	$_collection = Summoner::validate($_collection,'digit') ? $_collection : '';
}

$TemplateData['loadedCollection'] = array();
$TemplateData['pageTitle'] = 'Bulkedit';
$TemplateData['itemsToWorkWith'] = array();

if(!empty($_collection)) {
	$TemplateData['loadedCollection'] = $Trite->load($_collection, "write");
	if(!empty($TemplateData['loadedCollection'])) {
		$ManageEntry->setCollection($Trite->param('id'));

		if(isset($_POST['bulkedit']) && !empty($_POST['bulkedit'])) {
			foreach($_POST['bulkedit'] as $e) {
				$TemplateData['itemsToWorkWith'][] = $ManageEntry->getEditData($e);
			}

			// needs this editData array since manageentry functionality is used
			$TemplateData['editData'] = array();
			$TemplateData['editFields'] = $ManageEntry->getEditFields();

			// @see manageentry for similar process
			if(isset($_POST['submitForm'])) {
				$fdata = $_POST['fdata'];
				$fupload = array('name' => ''); // match $_FILES
				if(!empty($_FILES) && isset($_FILES['fdata'])) {
					$fupload = $_FILES['fdata'];
				}
				$_fieldsToSave = array();
				// default
				$_owner = $Doomguy->param('id');
				$_group = $Trite->param('group');
				$_rights = $Trite->param('rights');

				if (!empty($fdata)) {
					foreach ($TemplateData['editFields'] as $fieldId=>$fieldData) {
						if(isset($fdata['additionalEditOption'][$fieldData['identifier']])) {
							$fieldData['bulkeditMethod'] = $fdata['additionalEditOption'][$fieldData['identifier']];
							if(isset($fdata[$fieldData['identifier']])) {
								$_value = trim($fdata[$fieldData['identifier']]);
								$fieldData['valueToSave'] = trim($fdata[$fieldData['identifier']]);
								$_fieldsToSave[$fieldData['identifier']] = $fieldData;
							} elseif(isset($fupload['name'][$fieldData['identifier']])) {
								// special case upload
								// $_FILES data is combined
								$fieldData['uploadData'] = $fupload;

								$_fieldsToSave[$fieldData['identifier']] = $fieldData;
							}
						}
					}
				}

				// now update the entries with the gathered data to save
				if(!empty($_fieldsToSave)) {
					$_messages = array();
					foreach ($TemplateData['itemsToWorkWith'] as $entry) {
						foreach ($_fieldsToSave as $ident=>$data) {
							switch ($data['bulkeditMethod']) {
								case 'add':
									if(is_array($entry[$ident])) { // lookup multiple
										$data['valueToSave'] = implode(",", $entry[$ident]).",".$data['valueToSave'];
                                    }
									else {
										$data['valueToSave'] = $entry[$ident].' '.$data['valueToSave'] ;
									}
								break;

								case 'replace':
									// leave it as it is
								break;

								case 'empty':
									$data['valueToSave'] = '';
								break;
							}

							$_fieldsToSave[$ident] = $data;
						}

						$do = $ManageEntry->create($_fieldsToSave, $_owner, $_group, $_rights, $entry['id']);
						if ($do !== 0) {
							$_messages[] = "Entry updated: ".$entry['id'];
						} else {
							$_messages[] = "Entry could not be updated. See log for more details: ".$entry['id'];
						}

						unset($data);
					}

					$TemplateData['message']['content'] = implode("<br />",$_messages);
					$TemplateData['message']['status'] = "info";
				}
			}
		}
		else {
			$TemplateData['message']['content'] = "Missing required search items to work with.";
			$TemplateData['message']['status'] = "error";
		}
	}
	else {
		$TemplateData['message']['content'] = "Can not load given collection.";
		$TemplateData['message']['status'] = "error";
	}
}
