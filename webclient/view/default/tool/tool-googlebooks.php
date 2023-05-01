<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2023 Johannes Keßler
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

require_once 'lib/googlebookparser.class.php';
if(file_exists(PATH_ABSOLUTE.'/config/config-googlebooks.php')) {
	require_once 'config/config-googlebooks.php';
}

$GOOGLEBOOKS = new GoogleBooks(array(
	'browserAgent' => TOOL_GOOGLEBOOKS_BROWSER_AGENT,
	'browserLang' => TOOL_GOOGLEBOOKS_BROWSER_ACCEPT_LANG,
	'browserAccept' => TOOL_GOOGLEBOOKS_BROWSER_ACCEPT,
	'debug' => true
));

$TemplateData['bookData'] = array();
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
			$booksearch = $GOOGLEBOOKS->searchForISBN($search);

			if(!empty($booksearch)) {
				$TemplateData['bookData'] = $booksearch;
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

		// build data array based on submit
		// see creation log for structure
		$_data = array();
		foreach($fdata['into'] as $k=>$v) {
			if(!empty($v) && isset($fdata['from'][$k])) {
				if(isset($collectionFields[$v])) {

					$_data[$v] = $collectionFields[$v];
					$_data[$v]['valueToSave'] = $fdata['from'][$k];

					// special case for image
					if($k == "cover") {

						$fieldData = array();

						$_f = $GOOGLEBOOKS->downloadCover($fdata['from'][$k]);
						if($_f && is_file($_f)) {
							$_e = UPLOAD_ERR_OK;
							// build _FILES based on regular add form
							$fieldData['name'][$_data[$v]['identifier']] = 'cover.jpg';
							$fieldData['type'][$_data[$v]['identifier']] = mime_content_type($_f);
							$fieldData['size'][$_data[$v]['identifier']] = filesize($_f);
							$fieldData['tmp_name'][$_data[$v]['identifier']] = $_f;
							$fieldData['error'][$_data[$v]['identifier']] = UPLOAD_ERR_OK;
							$fieldData['rebuildUpload'][$_data[$v]['identifier']] = true;
						}

						$_data[$v]['uploadData'] = $fieldData;
					}
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

		// remove tmp file
		if(isset($_f) && is_file($_f) && file_exists($_f)) {
			unlink($_f);
		}
	}
}


/**
 * Helper function. Takes the prebuild options for the target selection field and search for a matching key.
 * Since the optionString is prebuild, avoiding looping over and over again, the selection needs to be done
 * by search and replace.
 * Checks if TOOL_GOOGLEBOOKS_FIELDS_TO is defined and a matching key=>value pair is available
 *
 * @param string $optionString
 * @param string $googleKey
 * @return string
 */
function toolMethod_GetTargetSelection(string $optionString, string $googleKey): string {
	if(defined('TOOL_GOOGLEBOOKS_FIELDS_TO') & !empty($googleKey)) {
		if(isset(TOOL_GOOGLEBOOKS_FIELDS_TO[$googleKey])) {
			$_k = "sel_".TOOL_GOOGLEBOOKS_FIELDS_TO[$googleKey];
			$optionString = str_replace($_k,'selected="selected"',$optionString);
		}
	}

	return $optionString;
}
