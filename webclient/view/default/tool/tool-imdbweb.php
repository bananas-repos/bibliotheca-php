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

/**
 * this is the special file for a tool.
 * Requirements and more information come from the main tool.php file
 */

require_once 'lib/imdbwebparser.class.php';
if(file_exists(PATH_ABSOLUTE.'/config/config-imdbweb.php')) {
	require_once 'config/config-imdbweb.php';
}

$IMDB = new IMDB(array(
	'sSearchFor' => TOOL_IMDBWEB_SEARCH,
	'showFields' => TOOL_IMDBWEB_FIELDS,
	'storage' => PATH_SYSTEMOUT,
	'browserAgent' => TOOL_IMDBWEB_BROWSER_AGENT,
	'browserLang' => TOOL_IMDBWEB_BROWSER_ACCEPT_LANG,
	'browserAccept' => TOOL_IMDBWEB_BROWSER_ACCEPT,
	'debug' => false
));


$TemplateData['movieData'] = array();
$TemplateData['saveToSelection'] = '';
$TemplateData['showMatchingForm'] = false;

// prepare fields to save into selection
// create one time and then reuse it
$collectionFields = $ManangeCollectionsFields->getExistingFields(false, true);
if(!empty($collectionFields)) {
	foreach ($collectionFields as $k=>$v) {
		$TemplateData['saveToSelection'] .= "<option value='".$k."' sel_".$v['identifier'].">".$v['displayname']."</option>\n";
	}
}

if(isset($_POST['submitFormSearch'])) {
	$fdata = $_POST['fdata'];
	if (!empty($fdata)) {
		$search = trim($fdata['search']);
		$search = Summoner::validate($search) ? $search : false;

		if(!empty($search)) {
			try {
				$IMDB->search($search);
			}
			catch (Exception $e) {
				if(DEBUG) error_log("[DEBUG] imdb search catch: ".$e->getMessage());
			}

			if ($IMDB->isReady) {
				$TemplateData['movieData'] = $IMDB->getAll();
				$TemplateData['movieImdbId'] = "tt".$IMDB->iId; // this is the IMDB id you can search for
				$TemplateData['showMatchingForm'] = true;
			} else {
				$TemplateData['message']['content'] = "Nothing found.";
				$TemplateData['message']['status'] = "error";
			}
		}
		else {
			$TemplateData['message']['content'] = "Invalid search term";
			$TemplateData['message']['status'] = "error";
		}
	}
}

if(isset($_POST['submitFormSave'])) {
	$fdata = $_POST['fdata'];
	if (!empty($fdata)) {
		$_imdbId = $fdata['imdbId'];
		$_imdbId = Summoner::validate($_imdbId,'nospace') ? $_imdbId : false;

		if(!empty($_imdbId)) {
			try {
				$IMDB->search($_imdbId);
			}
			catch (Exception $e) {
				if(DEBUG) error_log("[DEBUG] imdb search catch: ".$e->getMessage());
			}

			if ($IMDB->isReady) {
				$TemplateData['movieImdbId'] = $_imdbId;
				$_movieData = $IMDB->getAll();

				// build data array based on submit
				// see creation log for structure
				$_data = array();
				foreach($fdata['into'] as $k=>$v) {
					if(!empty($v)) {
						$_t = $IMDB->$k();

						// multiple selections format for field type lookup_multiple
						if(strstr($_t, $IMDB->sSeparator)) {
							$_t = str_replace($IMDB->sSeparator,",", $_t);
						}

						if(isset($collectionFields[$v])) {
							$_data[$v] = $collectionFields[$v];
							$_data[$v]['valueToSave'] = $_t;
						}
					}
				}

				$_r = $Tools->getDefaultCreationInfo();
				if(!empty($TemplateData['editEntry'])) {
					// update existing one
					$do = $Manageentry->create($_data,
						$_r['id'],
						$_r['group'],
						$_r['rights'],
						$TemplateData['editEntry']['id']
					);
					$TemplateData['message']['content'] = "Date saved successfully";
				}
				else {
					// create into loaded collection
					$do = $Manageentry->create($_data,
						$_r['id'],
						$_r['group'],
						$_r['rights']
					);
					$TemplateData['message']['content'] = "Date saved successfully: 
						<a href='index.php?p=manageentry&collection=".$collection['id']."&id=".$do."'>Here</a>";
				}

				if(!empty($do)) {
					$TemplateData['message']['status'] = "success";
				}
				else {
					$TemplateData['message']['content'] = "Data could not be saved. See logs for more.";
					$TemplateData['message']['status'] = "error";
				}
			} else {
				$TemplateData['message']['content'] = "Nothing found.";
				$TemplateData['message']['status'] = "error";
			}
		}
		else {
			$TemplateData['message']['content'] = "IMDB search result information lost.";
			$TemplateData['message']['status'] = "error";
		}
	}
}


/**
 * Helper function. Takes the prebuild options for the target selection field and search for a matching key.
 * Since the optionString is prebuild, avoiding looping over and over again, the selection needs to be done
 * by search and replace.
 * Checks if TOOL_IMDBWEB_FIELDS_TO is defined and a matching key=>value pair is available
 *
 * @param string $optionString
 * @param string $imdbKey
 * @return string
 */
function toolMethod_GetTargetSelection(string $optionString, string $imdbKey): string {
	if(defined('TOOL_IMDBWEB_FIELDS_TO') & !empty($imdbKey)) {
		if(isset(TOOL_IMDBWEB_FIELDS_TO[$imdbKey])) {
			$_k = "sel_".TOOL_IMDBWEB_FIELDS_TO[$imdbKey];
			$optionString = str_replace($_k,'selected="selected"',$optionString);
		}
	}

	return $optionString;
}
