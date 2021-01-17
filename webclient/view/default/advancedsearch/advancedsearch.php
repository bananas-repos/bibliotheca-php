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
require_once 'lib/mancubus.class.php';
$Mancubus = new Mancubus($DB,$Doomguy);

$_collection = false;
if(isset($_GET['collection']) && !empty($_GET['collection'])) {
	$_collection = trim($_GET['collection']);
	$_collection = Summoner::validate($_collection,'digit') ? $_collection : false;
}

$TemplateData['pageTitle'] = 'Advanced search';
$TemplateData['loadedCollection'] = array();
$TemplateData['collections'] = array();
$TemplateData['collectionFields'] = array();
$TemplateData['search'] = false;

if(!empty($_collection)) {
	$TemplateData['loadedCollection'] = $Trite->load($_collection);
	if(!empty($TemplateData['loadedCollection'])) {
		$TemplateData['collectionFields'] = $Trite->getCollectionFields();
		$Mancubus->setCollection($Trite->param('id'));
		$Mancubus->setQueryOptions(array('limit' => 60));

		$TemplateData['storagePath'] = PATH_WEB_STORAGE . '/' . $Trite->param('id');
		$TemplateData['entryLinkPrefix'] = "index.php?p=entry&collection=".$Trite->param('id');

		if(isset($_POST['submitForm'])) {
			$fdata = $_POST['fdata'];
			if (!empty($fdata)) {
				$_search = trim($fdata['search']);

				if (!empty($_search) && Summoner::validate($_search)) {
					if (strstr($_search, ':')) { // field search
						$_matches = array();
						if(preg_match_all("/(\p{L}+:)(?(?!\p{L}+:).)*/u",$_search, $_matches) !== false && !empty($_matches[0])) {
							// $matches[0] has the identifier: and text
							// $matches[1] has only the identifier:
							// $matches[0][0] belongs to $matches[1][0] and so on

							$_sData = array();
							$_ms = count($_matches[0]);
							for($i=0;$i<$_ms;$i++) {
								$_cn = trim(str_replace(':','',$_matches[1][$i]));
								if(isset($TemplateData['collectionFields'][$_cn])) {
									$_sData[$i]['colName'] = $_cn;
									$_sData[$i]['colValue'] = trim(str_replace($_matches[1][$i],'',$_matches[0][$i]));
									$_sData[$i]['fieldData'] = $TemplateData['collectionFields'][$_cn];
								}
							}

							$TemplateData['entries'] = $Mancubus->getEntries($_sData);
							$TemplateData['search'] = $_search;
						}
						else {
							$TemplateData['message']['content'] = "Wrong input format.";
							$TemplateData['message']['status'] = "error";
						}
					} else { // ordinary search within default field
						$TemplateData['entries'] = $Mancubus->getEntries(
							array(
								0 => array(
									'colName' => $Trite->param('defaultSearchField'),
									'colValue' => $_search,
									'fieldData' => $TemplateData['collectionFields'][$Trite->param('defaultSearchField')]
								)
							)
						);
						$TemplateData['search'] = $_search;
					}
				}
			}
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
