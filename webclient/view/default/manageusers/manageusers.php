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

require_once 'lib/possessed.class.php';
$Possessed = new Possessed($DB, $Doomguy);
$TemplateData['existingGroups'] = $Possessed->getGroups();
$TemplateData['existingUsers'] = $Possessed->getUsers();
$TemplateData['editData'] = array();
$TemplateData['editData']['groups'] = array();
$TemplateData['pageTitle'] = 'Manage users';

$_id = '';
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Summoner::validate($_id,'digit') ? $_id : '';
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
					$TemplateData['message']['content'] = "User could not be deleted. Make sure the user is not used anymore.";
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
