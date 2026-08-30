<?php

/**
 * Bibliotheca
 *
 * Copyright 2018-2026 Johannes Keßler
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

/**
 * this is the special file for a tool.
 * Requirements and more information come from the main tool.php file
 */

require_once 'lib/metacritic.class.php';
if(file_exists(PATH_ABSOLUTE.'/config/config-metacritic.php')) {
    require_once 'config/config-metacritic.php';
}
$METACRITIC = new Metacritic(array(
    'storage' => TOOL_METACRITIC_CACHE_STORAGE,
    'browserAgent' => TOOL_METACRITIC_BROWSER_AGENT,
    'browserLang' => TOOL_METACRITIC_BROWSER_ACCEPT_LANG,
    'browserAccept' => TOOL_METACRITIC_BROWSER_ACCEPT,
    'attributes' => TOOL_METACRITIC_ATTRIBUTES_MOVIE,
    'debug' => DEBUG,
    'type' => 'movie'
));

$TemplateData['movieData'] = array();
$TemplateData['saveToSelection'] = '';
$TemplateData['showMatchingForm'] = false;

// prepare fields to save into selection
// create one time and then reuse it
$collectionFields = $ManangeCollectionsFields->getExistingFields(false, true);
if(!empty($collectionFields)) {
    foreach ($collectionFields as $k=>$v) {
        $TemplateData['saveToSelection'] .= "<option value='".$k."' sel_".$v['identifier'].">".$I18n->t($v['displayname'])."</option>\n";
    }
}

if(isset($_POST['submitFormSearch'])) {
    $fdata = $_POST['fdata'];
    if (!empty($fdata)) {
        $search = trim($fdata['search']);
        $search = Summoner::validate($search, "nospaceP") ? $search : false;

        if (!empty($search)) {
            $mData = $METACRITIC->fetch($search);

            if (!empty($mData)) {
                $TemplateData['movieData'] = $mData;
                $TemplateData['urlpart'] = $search;
                $TemplateData['showMatchingForm'] = true;
            } else {
                $TemplateData['message']['content'] = $I18n->t('global.message.nothingFound');
                $TemplateData['message']['status'] = "error";
            }
        }
        else {
            $TemplateData['message']['content'] = $I18n->t('global.message.invalidSearchTerm');
            $TemplateData['message']['status'] = "error";
        }
    }
}

if(isset($_POST['submitFormSave'])) {
    $fdata = $_POST['fdata'];
    if (!empty($fdata)) {
        $_urlpart = $fdata['urlpart'];
        $_urlpart = Summoner::validate($_urlpart,'nospaceP') ? $_urlpart : false;

        if(!empty($_urlpart)) {
            // why search again?
            // Cache is used, so not really a new search and the data stays the same without a roundtrip
            // thriugh the requsts
            $mData = $METACRITIC->fetch($_urlpart);

            if (!empty($mData)) {
                $TemplateData['urlpart'] = $_urlpart;

                // build data array based on submit
                // see creation log for structure
                $_data = array();
                foreach($fdata['into'] as $k=>$v) {
                    if(!empty($v)) {
                        $_t = $mData[$k];

                        // multiple selections format for field type lookup_multiple
                        if(is_array($_t)) {
                            $_t = implode(",", $_t);
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
                    $TemplateData['message']['content'] = $I18n->t('global.message.dataSaved');
                }
                else {
                    // create into loaded collection
                    $do = $Manageentry->create($_data,
                        $_r['id'],
                        $_r['group'],
                        $_r['rights']
                    );
                    $TemplateData['message']['content'] = $I18n->t('global.message.dataSaved')." <a href='index.php?p=manageentry&collection=".$collection['id']."&id=".$do."'>".$I18n->t('global.view')."</a>";
                }

                if(!empty($do)) {
                    $TemplateData['message']['status'] = "success";
                }
                else {
                    $TemplateData['message']['content'] = $I18n->t('global.message.couldNotBeSaved');
                    $TemplateData['message']['status'] = "error";
                }
            } else {
                $TemplateData['message']['content'] = $I18n->t('global.message.nothingFound');
                $TemplateData['message']['status'] = "error";
            }
        }
        else {
            $TemplateData['message']['content'] = $I18n->t('tool.imdb.search.missingid');
            $TemplateData['message']['status'] = "error";
        }
    }
}


/**
 * Helper function. Takes the prebuild options for the target selection field and search for a matching key.
 * Since the optionString is prebuild, avoiding looping over and over again, the selection needs to be done
 * by search and replace.
 * Checks if TOOL_METACRITIC_FIELDS_TO_MOVIE is defined and a matching key=>value pair is available
 *
 * @param string $optionString
 * @param string $key
 * @return string
 */
function toolMethod_GetTargetSelection(string $optionString, string $key): string {
    if(defined('TOOL_METACRITIC_FIELDS_TO_MOVIE') & !empty($key)) {
        if(isset(TOOL_METACRITIC_FIELDS_TO_MOVIE[$key])) {
            $_k = "sel_".TOOL_METACRITIC_FIELDS_TO_MOVIE[$key];
            $optionString = str_replace($_k,'selected="selected"',$optionString);
        }
    }

    return $optionString;
}

/**
 * Make the given mixed data displayable as a string
 *
 * @param mixed $value
 * @return string
 */
function toolMethod_DisplayValues(mixed $value): string {
    $ret = $value;
    if(is_array($value)) {
        $ret = implode(", ", $value);
    }
    return $ret;
}
