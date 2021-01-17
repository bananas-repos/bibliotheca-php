<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2021 Johannes Keßler
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
require_once 'lib/possessed.class.php';
$Possessed = new Possessed($DB, $Doomguy);
$TemplateData['existingGroups'] = $Possessed->getGroups();
$TemplateData['editData'] = false;

$TemplateData['pageTitle'] = 'Manage groups';

$_id = false;
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Summoner::validate($_id,'digit') ? $_id : false;
}

if(!empty($_id)) {
	$TemplateData['editData'] = $Possessed->getEditGroupData($_id);
	if(!isset($TemplateData['editData']['name'])) {
		$TemplateData['refresh'] = 'index.php?p=managegroups';
	}
}

if(isset($_POST['submitForm'])) {
	$fdata = $_POST['fdata'];
	if(!empty($fdata)) {
		$_name = trim($fdata['name']);
		$_description = trim($fdata['description']);

		if(!empty($TemplateData['editData'])) {
			if(isset($fdata['doDelete'])) {
				$do = $Possessed->deleteGroup($_id);
				if ($do === true) {
					$TemplateData['refresh'] = 'index.php?p=managegroups';
				}
				else {
					$TemplateData['message']['content'] = "Group could not be deleted.";
					$TemplateData['message']['status'] = "error";
				}
			}
			elseif (Summoner::validate($_name,'nospace') && Summoner::validate($_description)) {
				$do = $Possessed->updateGroup($_id, $_name, $_description);
				if ($do === true) {
					$TemplateData['refresh'] = 'index.php?p=managegroups';
				}
				else {
					$TemplateData['message']['content'] = "Group could not be updated. Either wrong input or duplicate group name";
					$TemplateData['message']['status'] = "error";
				}
			}
			else {
				$TemplateData['message']['content'] = "Provide name and description.";
				$TemplateData['message']['status'] = "error";
			}
		}
		else { // adding mode
			if (Summoner::validate($_name,'nospace') && Summoner::validate($_description)) {
				$do = $Possessed->createGroup($_name, $_description);
				if ($do === true) {
					$TemplateData['refresh'] = 'index.php?p=managegroups';
				}
				else {
					$TemplateData['message']['content'] = "Group could not be created.";
					$TemplateData['message']['status'] = "error";
				}
			}
			else {
				$TemplateData['message']['content'] = "Provide name and description.";
				$TemplateData['message']['status'] = "error";
			}
		}

	}
}
