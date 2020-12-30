<?php
/**
 * Bibliotheca webclient
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
require_once 'lib/possessed.class.php';
$Possessed = new Possessed($DB);
$TemplateData['existingGroups'] = $Possessed->getGroups();
$TemplateData['existingUsers'] = $Possessed->getUsers();
$TemplateData['editData'] = false;

$_editMode = false;
if(isset($_GET['m']) && !empty($_GET['m'])) {
    if($_GET['m'] == "edit") {
        $_editMode = true;
    }
}

$_id = false;
if(isset($_GET['id']) && !empty($_GET['id'])) {
    $_id = trim($_GET['id']);
    $_id = Summoner::validate($_id,'digit') ? $_id : false;
}

if($_editMode === true && !empty($_id)) {
    $TemplateData['editData'] = $Possessed->getEditData($_id);
    if(!isset($TemplateData['editData']['name'])) {
        $TemplateData['refresh'] = 'index.php?p=manageusers';
    }
}


if(isset($_POST['submitForm'])) {
    $fdata = $_POST['fdata'];
    if(!empty($fdata)) {

        $_login = trim($fdata['login']);
        $_group = trim($fdata['group']);
        $_username = trim($fdata['username']);
        $_password = trim($fdata['password']);
        $_active = false;
        if (isset($fdata['active'])) {
            $_active = true;
        }

        if(!empty($TemplateData['editData'])) {
            if(isset($fdata['doDelete'])) {
                $do = $Possessed->deleteUser($_id);
                if ($do === true) {
                    $TemplateData['refresh'] = 'index.php?p=manageusers';
                } else {
                    $TemplateData['message']['content'] = "User could not be deleted.";
                    $TemplateData['message']['status'] = "error";
                }
            }
            elseif (!empty($_username) && !empty($_group) && !empty($_login)) {
                if (Summoner::validate($_username, 'text') === true
                    && Summoner::validate($_login, 'nospace') === true
                    && isset($TemplateData['existingGroups'][$_group])
                ) {
                    $refreshApi = false;
                    if(isset($fdata['refreshApiToken'])) {
                        $refreshApi = true;
                    }
                    $do = $Possessed->updateUser($_id, $_username, $_login, $_password, $_group, $_active, $refreshApi);
                    if ($do === true) {
                        $TemplateData['refresh'] = 'index.php?p=manageusers';
                    } else {
                        $TemplateData['message']['content'] = "User could not be updated.";
                        $TemplateData['message']['status'] = "error";
                    }
                } else {
                    $TemplateData['message']['content'] = "Provide username, login and a valid user group.";
                    $TemplateData['message']['status'] = "error";
                }
            }
        }
        else { // adding mode
            if (!empty($_username) && !empty($_password) && !empty($_group) && !empty($_login)) {
                if (Summoner::validate($_username, 'text') === true
                    && Summoner::validate($_password, 'text') === true
                    && Summoner::validate($_login, 'nospace') === true
                    && isset($TemplateData['existingGroups'][$_group])
                ) {
                    $do = $Possessed->createUser($_username, $_login, $_password, $_group, $_active);
                    if ($do === true) {
                        $TemplateData['refresh'] = 'index.php?p=manageusers';
                    } else {
                        $TemplateData['message']['content'] = "User could not be created.";
                        $TemplateData['message']['status'] = "error";
                    }
                } else {
                    $TemplateData['message']['content'] = "Provide username, login, password and a valid user group.";
                    $TemplateData['message']['status'] = "error";
                }
            }
        }
    }
}