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

$TemplateData['editData'] = $Doomguy->getAllUserData();

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
											$_password, $Doomguy->param('baseGroupId'), true, $refreshApi);
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
