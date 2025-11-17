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
 *  along with this program. If not, see http://www.gnu.org/licenses/gpl-3.0
 */

require_once 'lib/possessed.class.php';
$Possessed = new Possessed($DB, $Doomguy);
$TemplateData['existingGroups'] = $Possessed->getGroups();
$TemplateData['editData'] = array();

$TemplateData['pageTitle'] = 'Manage groups';

$_id = '';
if(isset($_GET['id']) && !empty($_GET['id'])) {
	$_id = trim($_GET['id']);
	$_id = Summoner::validate($_id,'digit') ? $_id : '';
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
					$TemplateData['message']['content'] = $I18n->t('managegroups.message.couldNotBeDelete');
					$TemplateData['message']['status'] = "error";
				}
			}
			elseif (Summoner::validate($_name,'nospace') && Summoner::validate($_description)) {
				$do = $Possessed->updateGroup($_id, $_name, $_description);
				if ($do === true) {
					$TemplateData['refresh'] = 'index.php?p=managegroups';
				}
				else {
					$TemplateData['message']['content'] = $I18n->t('managegroups.message.couldNotBeUpdated');
					$TemplateData['message']['status'] = "error";
				}
			}
			else {
				$TemplateData['message']['content'] = $I18n->t('managegroups.message.missingName');
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
					$TemplateData['message']['content'] = $I18n->t('managegroups.message.couldNotBeCreated');
					$TemplateData['message']['status'] = "error";
				}
			}
			else {
				$TemplateData['message']['content'] = $I18n->t('managegroups.message.missingName');
				$TemplateData['message']['status'] = "error";
			}
		}

	}
}
