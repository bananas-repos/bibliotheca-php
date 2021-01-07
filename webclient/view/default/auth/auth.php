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

// passwords used here: password_hash("somePassword", PASSWORD_DEFAULT);

if(isset($_GET['m']) && !empty($_GET['m'])) {
	if($_GET['m'] == "logout") {
		$Doomguy->logOut();
		$TemplateData['refresh'] = 'index.php';
	}
}
elseif(isset($_POST['submitForm'])) {
	$fdata = $_POST['fdata'];
	if(!empty($fdata)) {
		$_username = trim($fdata['login']);
		$_password = trim($fdata['password']);

		if(!empty($_username) && !empty($_password)) {
			if(Summoner::validate($_username,'text') === true && Summoner::validate($_password,'text') === true) {
				$do = $Doomguy->authenticate($_username, $_password);
				if($do === true) {
					$TemplateData['refresh'] = 'index.php';
				}
				else {
					$TemplateData['message']['content'] = "Invalid username or password.";
					$TemplateData['message']['status'] = "error";
				}
			}
			else {
				$TemplateData['message']['content'] = "Please provide valid e-Mail and password.";
				$TemplateData['message']['status'] = "error";
			}
		}
	}
}
