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

require_once 'lib/trite.class.php';
$Trite = new Trite($DB,$Doomguy);
require_once 'lib/managetags.class.php';
$ManageTags = new ManageTags($DB,$Doomguy);

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

$TemplateData['loadedCollection'] = array();
$TemplateData['collections'] = array();

if(!empty($_collection)) {
	$TemplateData['loadedCollection'] = $Trite->load($_collection);
	if(!empty($TemplateData['loadedCollection'])) {
		$ManageTags->setCollection($_collection);
		if(isset($_POST['submitForm'])) {
			$fdata = $_POST['fdata'];
			$do = array();
			if(!empty($fdata)) {
				foreach ($fdata as $ident=>$data) {
					$do[] = $ManageTags->doWithTag($ident, $data);
				}
			}
			if(!empty($do)) {
				if(empty(implode($do))) {
					$TemplateData['refresh'] = 'index.php?p=managetags&collection='.$_collection;
				}
				else {
					$TemplateData['message']['content'] = implode('<br / >',$do);
					$TemplateData['message']['status'] = "error";
				}
			}
			else {
				$TemplateData['message']['content'] = "Can not execute given options. See logs for more.";
				$TemplateData['message']['status'] = "error";
			}
		}
		else {
			$TemplateData['tags'] = $Trite->getTags();
		}
	}
	else {
		$TemplateData['message']['content'] = "Can not load given collection.";
		$TemplateData['message']['status'] = "error";
	}
}
else {
	$TemplateData['collections'] = $Trite->getCollections();
}
