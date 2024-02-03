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

// passwords used here: password_hash("somePassword", PASSWORD_DEFAULT);

$TemplateData['pageTitle'] = 'Auth';

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
