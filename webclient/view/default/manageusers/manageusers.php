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
$TemplateData['existingUsers'] = $Possessed->getUsers();
$TemplateData['editData'] = false;
$TemplateData['editData']['groups'] = array();
$TemplateData['pageTitle'] = 'Manage users';

$_id = false;
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Summoner::validate($_id,'digit') ? $_id : false;
}

if(!empty($_id)) {
	$TemplateData['editData'] = $Possessed->getEditData($_id);
	if(!isset($TemplateData['editData']['name'])) {
		$TemplateData['refresh'] = 'index.php?p=manageusers';
	}
}


if(isset($_POST['submitForm'])) {
	$fdata = $_POST['fdata'];
	if(!empty($fdata)) {

		$_login = trim($fdata['login']);
		$_group = trim($fdata['group']);
		$_username = trim($fdata['username']);
		$_password = trim($fdata['password']);
		$_active = false;
		if (isset($fdata['active'])) {
			$_active = true;
		}

		$_groups = array();
		if(isset($fdata['groups'])) {
			$_groups = $fdata['groups'];
		}

		if(!empty($TemplateData['editData'])) {
			if(isset($fdata['doDelete'])) {
				$do = $Possessed->deleteUser($_id);
				if ($do === true) {
					$TemplateData['refresh'] = 'index.php?p=manageusers';
				}
				else {
					$TemplateData['message']['content'] = "User could not be deleted.";
					$TemplateData['message']['status'] = "error";
				}
			}
			elseif (!empty($_username) && !empty($_group) && !empty($_login)) {
				if (Summoner::validate($_username) === true
					&& Summoner::validate($_login, 'nospace') === true
					&& isset($TemplateData['existingGroups'][$_group])
				) {
					$refreshApi = false;
					if(isset($fdata['refreshApiToken'])) {
						$refreshApi = true;
					}
					$do = $Possessed->updateUser($_id, $_username, $_login, $_password, $_group, $_groups, $_active, $refreshApi);
					if ($do === true) {
						$TemplateData['refresh'] = 'index.php?p=manageusers';
					}
					else {
						$TemplateData['message']['content'] = "User could not be updated. Either wrong input or duplicate user name";
						$TemplateData['message']['status'] = "error";
					}
				}
				else {
					$TemplateData['message']['content'] = "Provide username, login and a valid user group.";
					$TemplateData['message']['status'] = "error";
				}
			}
		}
		else { // adding mode
			if (!empty($_username) && !empty($_password) && !empty($_group) && !empty($_login)) {
				if (Summoner::validate($_username, 'text') === true
					&& Summoner::validate($_password, 'text') === true
					&& Summoner::validate($_login, 'nospace') === true
					&& isset($TemplateData['existingGroups'][$_group])
				) {
					$do = $Possessed->createUser($_username, $_login, $_password, $_group, $_groups, $_active);
					if ($do === true) {
						$TemplateData['refresh'] = 'index.php?p=manageusers';
					}
					else {
						$TemplateData['message']['content'] = "User could not be created.";
						$TemplateData['message']['status'] = "error";
					}
				}
				else {
					$TemplateData['message']['content'] = "Provide username, login, password and a valid user group.";
					$TemplateData['message']['status'] = "error";
				}
			}
		}
	}
}
