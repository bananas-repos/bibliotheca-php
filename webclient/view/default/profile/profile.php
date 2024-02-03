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

$TemplateData['editData'] = $Doomguy->getAllUserData();
$TemplateData['pageTitle'] = 'Profile';

if(!empty($TemplateData['editData'])) {
	if(isset($_POST['submitForm'])) {
		$fdata = $_POST['fdata'];
		if(!empty($fdata)) {
			$_username = trim($fdata['username']);
			$_password = trim($fdata['password']);
			$refreshApi = false;
			if(isset($fdata['refreshApiToken'])) {
				$refreshApi = true;
			}
			$do = $Possessed->updateUser($Doomguy->param('id'), $_username, $Doomguy->param('login'),
											$_password, $Doomguy->param('baseGroupId'), array(), true, $refreshApi);
			if ($do === true) {
				$TemplateData['refresh'] = 'index.php?p=profile';
			} else {
				$TemplateData['message']['content'] = "Your profile could not be updated.";
				$TemplateData['message']['status'] = "error";
			}
		}
	}
}
else {
	$TemplateData['message']['content'] = "Something went wrong. See logs for more details.";
	$TemplateData['message']['status'] = "error";
}
