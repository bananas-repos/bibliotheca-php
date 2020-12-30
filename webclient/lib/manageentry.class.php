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

class Manageentry {
    /**
     * The database object
     *
     * @var object
     */
    private $_DB;

    /**
     * The user object to query with
     *
     * @var object
     */
    private $_User;

    /**
     * Currently loaded collection to manage entries from
     *
     * @var string Number
     */
    private $_collectionId;

    /**
     * Placeholder in query strings for inserted DB id
     *
     * @var string
     */
    private $_replaceEntryString = 'REPLACE_ENTERY';

    /**
     * ManageCollections constructor.
     *
     * @param $databaseConnectionObject
     * @param $userObj
     */
    public function __construct($databaseConnectionObject, $userObj) {
        $this->_DB = $databaseConnectionObject;
        $this->_User = $userObj;
    }

    /**
     * Set the collection to manage entries from
     *
     * @param $collectionId
     */
    public function setCollection($collectionId) {
        if(!empty($collectionId)) {
            $this->_collectionId = $collectionId;
        }
    }

    /**
     * Load the fields for the loaded collection
     * Also load additional data based on fieldtype and _loadField_ method
     *
     * @return array
     */
    public function getEditFields() {
        $ret = array();

        if(!empty($this->_collectionId)) {
            $queryStr = "SELECT `cf`.`fk_field_id` AS id, `sf`.`type`, `sf`.`displayname`, `sf`.`identifier`,
                            `sf`.`value`
                        FROM `".DB_PREFIX."_collection_fields_".$this->_DB->real_escape_string($this->_collectionId)."` AS cf
                        LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
                        ORDER BY `cf`.`sort`";
            $query = $this->_DB->query($queryStr);
            if($query !== false && $query->num_rows > 0) {
                while(($result = $query->fetch_assoc()) != false) {
                    $_mn = '_loadField_'.$result['type'];
                    if(method_exists($this, $_mn)) {
                        $result = $this->$_mn($result);
                    }
                    $ret[$result['id']] = $result;
                }
            }
        }

        return $ret;
    }

    /**
     * Load required data for edit. Uses some functions from Mancubus but has
     * different data layout. Checks write edit too
     *
     * @param $entryId
     * @return array
     */
    public function getEditData($entryId) {
        $ret = array();

        if(!empty($this->_collectionId) && !empty($entryId)) {
            $queryStr = "SELECT * 
                        FROM `".DB_PREFIX."_collection_entry_".$this->_DB->real_escape_string($this->_collectionId)."` 
                        WHERE ".$this->_User->getSQLRightsString("write")."
                        AND `id` = '".$this->_DB->real_escape_string($entryId)."'";
            $query = $this->_DB->query($queryStr);

            if($query !== false && $query->num_rows > 0) {
                $_entryFields = $this->getEditFields();

                if(($result = $query->fetch_assoc()) != false) {
                    $ret = $this->_mergeEntryWithFields($result, $_entryFields);
                    $ret['_canDelete'] = $this->_canDelete($entryId);
                }

            }
        }

        return $ret;
    }

    /**
     * Create an entry with given data
     *
     * @param array $data
     * @param number $owner
     * @param number $group
     * @param string $rights
     * @param mixed $update Either false for no update or the ID to update
     * @return mixed
     */
    public function create($data, $owner, $group, $rights, $update=false) {
        $ret = false;

        if(DEBUG) error_log("[DEBUG] ".__METHOD__." data: ".var_export($data,true));

        //@todo there is no setting for individual rights available yet
        if(!empty($data) && !empty($owner) && !empty($group) && !empty($rights)) {

            // create the queryData array
            // init is the entry in the table. Needed for after stuff
            // after returns query and upload which then calls the extra methods
            $queryData['init'] = array();
            $queryData['after'] = array();
            foreach ($data as $i=>$d) {
                $_mn = '_saveField_'.$d['type'];
                if(method_exists($this, $_mn)) {
                    $queryData = $this->$_mn($d, $queryData);
                }
                else {
                    if(DEBUG)error_log("[DEBUG] ".__METHOD__." Missing query function for: ".var_export($d, true));
                }
            }

            if(DEBUG) error_log("[DEBUG] ".__METHOD__." queryData: ".var_export($queryData,true));

            if(!empty($queryData['init'])) {
                $this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

                try {
                    $queryStr = "INSERT INTO `".DB_PREFIX."_collection_entry_".$this->_collectionId."`";
                    if($update !== false && is_numeric($update)) {
                        $queryStr = "UPDATE `".DB_PREFIX."_collection_entry_".$this->_collectionId."`";
                    }
                    $queryStr .= " SET
                                `modificationuser` = '".$this->_DB->real_escape_string($owner)."',
                                `owner` = '".$this->_DB->real_escape_string($owner)."',
                                `group` = '".$this->_DB->real_escape_string($group)."',
                                `rights`= '".$this->_DB->real_escape_string($rights)."',";
                    $queryStr .= implode(", ",$queryData['init']);
                    if($update !== false && is_numeric($update)) {
                        $queryStr .= " WHERE `id` = '".$this->_DB->real_escape_string($update)."'";
                    }

                    if(DEBUG) error_log("[DEBUG] ".__METHOD__." init queryStr: ".var_export($queryStr,true));

                    $this->_DB->query($queryStr);

                    if($update !== false && is_numeric($update)) {
                        $newId = $update;
                    }
                    else {
                        $newId = $this->_DB->insert_id;
                    }

                    if(!empty($newId)) {
                        if(!empty($queryData['after']) && isset($queryData['after']['query'])) {
                            foreach ($queryData['after']['query'] as $q) {
                                $this->_runAfter_query($q, $newId);
                            }
                        }

                        if(!empty($queryData['after']) && isset($queryData['after']['upload'])) {
                            foreach ($queryData['after']['upload'] as $q) {
                                $this->_runAfter_upload($q, $newId);
                            }
                        }

                        $this->_DB->commit();
                        $ret = $newId;
                    }
                    else {
                        $this->_DB->rollback();
                    }
                } catch (Exception $e) {
                    if(DEBUG) error_log("[DEBUG] ".__METHOD__."  mysql catch: ".$e->getMessage());
                    $this->_DB->rollback();
                }
            }
            else {
                if(DEBUG) error_log("[DEBUG] ".__METHOD__." empty init in: ".var_export($queryData,true));
            }
        }

        return $ret;
    }

    /**
     * Delete given entryId from currently loaded collection
     * Checks userrights too.
     *
     * @param $entryId
     * @return bool
     */
    public function delete($entryId) {
        $ret = false;

        if(!empty($entryId) && !empty($this->_collectionId)) {

            if ($this->_canDelete($entryId)) {

                $this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

                try {
                    // remove assets
                    $_path = PATH_STORAGE.'/'.$this->_collectionId.'/'.$entryId;
                    if(is_dir($_path) && is_readable($_path)) {
                        if(DEBUG) error_log("[DEBUG] ".__METHOD__."  remove assets :".$_path);
                        $rmDir = Summoner::recursive_remove_directory($_path);
                        if($rmDir === false) {
                            throw new Exception("Failed to delete path: ".$_path);
                        }
                    }

                    // delete data from lookup fields
                    $queryStr = "DELETE 
                        FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
                        WHERE `fk_entry` = '".$this->_DB->real_escape_string($entryId)."'";
                    if(DEBUG) error_log("[DEBUG] ".__METHOD__." remove lookup queryStr: ".var_export($queryStr,true));
                    $this->_DB->query($queryStr);

                    // delete entry
                    $queryStr = "DELETE
                        FROM `".DB_PREFIX."_collection_entry_".$this->_collectionId."`
                        WHERE `id` = '".$this->_DB->real_escape_string($entryId)."'
                            AND " . $this->_User->getSQLRightsString("delete") . "";
                    $this->_DB->query($queryStr);

                    $this->_DB->commit();
                    $ret = true;
                } catch (Exception $e) {
                    if(DEBUG) error_log("[DEBUG] ".__METHOD__."  mysql catch: ".$e->getMessage());
                    $this->_DB->rollback();
                }
            }
        }

        return $ret;
    }

    /**
     * Validates that current use can write the given Entry
     *
     * @param $entryId
     * @return bool
     */
    public function canEditEntry($entryId) {
        $ret = false;

        if(!empty($entryId) && !empty($this->_collectionId)) {

            $queryStr = "SELECT `id`
                        FROM `".DB_PREFIX."_collection_entry_".$this->_collectionId."`
                        WHERE `id` = '".$this->_DB->real_escape_string($entryId)."'
                            AND " . $this->_User->getSQLRightsString("write") . "";
            $query = $this->_DB->query($queryStr);
            if ($query !== false && $query->num_rows > 0) {
                if (($result = $query->fetch_assoc()) != false) {
                    $ret = true;
                }
            }
        }

        return $ret;
    }

    /**
     * Check if given entryid can be deleted from current collection
     * and user
     *
     * @param $entryId
     * @return bool
     */
    private function _canDelete($entryId) {
        $ret = false;

        if(!empty($entryId) && !empty($this->_collectionId)) {

            $queryStr = "SELECT `id`
                        FROM `".DB_PREFIX."_collection_entry_".$this->_collectionId."`
                        WHERE `id` = '".$this->_DB->real_escape_string($entryId)."'
                            AND " . $this->_User->getSQLRightsString("delete") . "";
            $query = $this->_DB->query($queryStr);
            if ($query !== false && $query->num_rows > 0) {
                if (($result = $query->fetch_assoc()) != false) {
                    $ret = true;
                }
            }
        }

        return $ret;
    }

    /**
     * Merge the loaded entryData with the to look up entryFields data
     * In this case only the fields which have a _loadFieldValue_ method
     * are loaded. More is not needed here.
     *
     * @param $entryData array
     * @param $entryFields array
     * @return array
     */
    private function _mergeEntryWithFields($entryData, $entryFields) {
        if(!empty($entryFields)) {
            foreach($entryFields as $f) {
                $_mnValue = '_loadFieldValue_'.$f['type'];

                if(!isset($entryData[$f['identifier']]) && method_exists($this, $_mnValue) && isset($entryData['id']) ) {
                    $entryData[$f['identifier']] = $this->$_mnValue($entryData['id'], $f);
                }
            }
        }
        return $entryData;
    }

    /**
     * Load the values for given $entryId for $fieldData
     * lookup function for field type lookupmultiple
     *
     * @see Mancubus
     * @param Number $entryId
     * @param array $fieldData
     * @return array
     */
    private function _loadFieldValue_lookupmultiple($entryId, $fieldData) {
        $ret = array();

        if(!empty($entryId) && !empty($fieldData) && !empty($this->_collectionId)) {
            $queryStr = "SELECT `value` 
                        FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
                        WHERE `fk_field` = '".$this->_DB->real_escape_string($fieldData['id'])."'
                            AND `fk_entry` = '".$this->_DB->real_escape_string($entryId)."'";
            $query = $this->_DB->query($queryStr);
            if($query !== false && $query->num_rows > 0) {
                while(($result = $query->fetch_assoc()) != false) {
                    $ret[] = $result['value'];
                }
            }
        }

        return $ret;
    }

    /**
     * Get the single upload file from storage location
     * lookup function for field type upload
     *
     * @see Mancubus
     * @param $entryId
     * @param $fieldData
     * @return string
     */
    private function _loadFieldValue_upload($entryId, $fieldData) {
        $ret = "";
        if(!empty($entryId) && !empty($fieldData) && !empty($this->_collectionId)) {

            $uploadedFile = glob(PATH_STORAGE.'/'.$this->_collectionId.'/'.$entryId.'/'.$fieldData['identifier'].'-*');
            if(!empty($uploadedFile)) {
                foreach ($uploadedFile as $f) {
                    $ret = basename($f);
                    break;
                }
            }
        }
        return $ret;
    }

    /**
     * Get the multiple upload files from storage location
     * lookup function for field type upload_multiple
     *
     * @see Mancubus
     * @param $entryId
     * @param $fieldData
     * @return array
     */
    private function _loadFieldValue_upload_multiple($entryId, $fieldData) {
        $ret = array();
        if(!empty($entryId) && !empty($fieldData) && !empty($this->_collectionId)) {

            $uploadedFile = glob(PATH_STORAGE.'/'.$this->_collectionId.'/'.$entryId.'/'.$fieldData['identifier'].'-*');
            if(!empty($uploadedFile)) {
                foreach ($uploadedFile as $f) {
                    $ret[] = basename($f);
                }
            }
        }
        return $ret;
    }

    /**
     * Provide the options for a selection field by processing the $data['value']
     * since the values are stored in the entry DB as a list
     *
     * @param $data array
     * @return array
     */
    private function _loadField_selection($data) {
        if(!empty($data) && isset($data['value']) && !empty($data['value'])) {
            if(strstr($data['value'], ",")) {
                $data['options'] = explode(",", $data['value']);
            }
        }
        return $data;
    }

    /**
     * Load suggestions based on the existing data for this field
     *
     * @param $data array Field data
     * @return array
     */
    private function _loadField_lookupmultiple($data) {
        if(!empty($data) && isset($data['id']) && !empty($data['id'])) {
            $queryStr = "SELECT DISTINCT(`value`) 
                        FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
                        WHERE `fk_field` = '".$this->_DB->real_escape_string($data['id'])."'";
            $query = $this->_DB->query($queryStr);
            if ($query !== false && $query->num_rows > 0) {
                while (($result = $query->fetch_assoc()) != false) {
                    $data['suggestion'][] = $result['value'];
                }
            }
        }
        return $data;
    }

    /**
     * Create part of the insert statement for field type text
     *
     * @param $data array Field data
     * @param $queryData array Query data array
     * @return array
     */
    private function _saveField_text($data, $queryData) {
        $queryData['init'][] = "`".$data['identifier']."` = '".$this->_DB->real_escape_string($data['valueToSave'])."'";
        return $queryData;
    }

    /**
     * Create part of the insert statement for field type text3
     *
     * @param $data array Field data
     * @param $queryData array Query data array
     * @return array
     */
    private function _saveField_text3($data, $queryData) {
        return $this->_saveField_text($data, $queryData);
    }

    /**
     * Create part of the insert statement for field type textarea
     *
     * @param $data array Field data
     * @param $queryData array Query data array
     * @return array
     */
    private function _saveField_textarea($data, $queryData) {
        return $this->_saveField_text($data, $queryData);
    }

    /**
     * Create part of the insert statement for field type selection
     *
     * @param $data array Field data
     * @param $queryData array Query data array
     * @return array
     */
    private function _saveField_selection($data, $queryData) {
        return $this->_saveField_text($data, $queryData);
    }
    /**
     * Create part of the insert statement for field type year
     *
     * @param $data array Field data
     * @param $queryData array Query data array
     * @return array
     */
    private function _saveField_year($data, $queryData) {
        return $this->_saveField_text($data, $queryData);
    }

    /**
     * Create part of the insert statement for field type lookupmultiple
     *
     * @param $data array Field data
     * @param $queryData array Query data array
     * @return array
     */
    private function _saveField_lookupmultiple($data, $queryData) {
        $_d = trim($data['valueToSave']);
        $_d = trim($_d, ",");

        // first clean since the new data is everything
        $queryData['after']['query'][] = "DELETE FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_collectionId."`
                                        WHERE `fk_field` = '".$this->_DB->real_escape_string($data['id'])."'
                                            AND `fk_entry` = '".$this->_replaceEntryString."'";
        if(!empty($_d)) {
            $_process = array($_d);
            if (strstr($data['valueToSave'], ",")) {
                $_process = explode(",", $data['valueToSave']);
            }
            foreach ($_process as $p) {
                $queryData['after']['query'][] = "INSERT IGNORE INTO `".DB_PREFIX."_collection_entry2lookup_".$this->_collectionId."`
                                SET `fk_field` = '".$this->_DB->real_escape_string($data['id'])."',
                                    `fk_entry` = '".$this->_replaceEntryString."',
                                    `value` = '".$this->_DB->real_escape_string($p)."'";
            }
        }

        return $queryData;
    }

    /**
     * Single upload field
     *
     * @param $data array The data from _FILES
     * @param $queryData array
     * @return array
     */
    private function _saveField_upload($data, $queryData) {
        $_up = $data['uploadData'];

        // delete the single upload
        // this way the after query method is triggered without any upload
        if(isset($data['deleteData'])) {
            $queryData['after']['upload'][] = array(
                'identifier' => $data['identifier'],
                'multiple' => false,
                'deleteData' => $data['deleteData']
            );
        }

        if($_up['error'][$data['identifier']] === 0) {
            $_ext = pathinfo($_up['name'][$data['identifier']],PATHINFO_EXTENSION);
            $newFilename = sha1($_up['name'][$data['identifier']]).".".$_ext;

            $queryData['after']['upload'][] = array(
                'identifier' => $data['identifier'],
                'name' => $newFilename,
                'tmp_name' => $_up['tmp_name'][$data['identifier']],
                'multiple' => false
            );
        }
        return $queryData;
    }

    /**
     * Multiple upload field
     *
     * @param $data array The data from _FILES
     * @param $queryData array
     * @return array
     */
    private function _saveField_upload_multiple($data, $queryData) {
        $_up = $data['uploadData'];

        if(isset($data['deleteData'])) {
            $queryData['after']['upload'][] = array(
                'identifier' => $data['identifier'],
                'multiple' => true,
                'deleteData' => $data['deleteData']
            );
        }

        foreach ($_up['error'][$data['identifier']] as $k=>$v) {
            if($v === 0) {
                $_ext = pathinfo($_up['name'][$data['identifier']][$k],PATHINFO_EXTENSION);
                $newFilename = sha1($_up['name'][$data['identifier']][$k]).".".$_ext;

                $queryData['after']['upload'][] = array(
                    'identifier' => $data['identifier'],
                    'name' => $newFilename,
                    'tmp_name' => $_up['tmp_name'][$data['identifier']][$k],
                    'multiple' => true
                );
            }
        }

        return $queryData;
    }

    /**
     * runs the query and throws query execption if false
     *
     * @param $queryString
     * @param $insertId
     */
    private function _runAfter_query($queryString, $insertId) {
        if(!empty($queryString) && !empty($insertId)) {
            // replace only once to avoid replacing actual data
            $queryStr = Summoner::replaceOnce($queryString,$this->_replaceEntryString, $insertId);
            $this->_DB->query($queryStr);
            if(DEBUG) error_log("[DEBUG] ".__METHOD__." queryStr: ".var_export($queryStr,true));
        }
    }

    /**
     * Move uploaded into right directory
     * If single upload (multiple=false) then remove all the files for this type field first. Works the same
     * if you want to remove the upload via edit
     *
     * Also removes the defined uploads from multiple upload field
     *
     * @param $uploadData
     * @param $insertId
     * @throws Exception
     */
    private function _runAfter_upload($uploadData, $insertId) {
        if(!empty($uploadData) && !empty($insertId)) {
            if(DEBUG) error_log("[DEBUG] ".__METHOD__." uploadata: ".var_export($uploadData,true));
            $_path = PATH_STORAGE.'/'.$this->_collectionId.'/'.$insertId;
            if(!is_dir($_path)) {
                if(!mkdir($_path, 0777, true)) {
                    throw new Exception("Failed to create storage path: ".$_path);
                }
            }

            if($uploadData['multiple'] === false) {
                // single upload. Delete existing first.
                // also triggered if the single needs to be deleted
                $_existingFiles = glob($_path.'/'.$uploadData['identifier'].'-*');
                if(DEBUG) error_log("[DEBUG] ".__METHOD__." remove single existing: ".var_export($_existingFiles,true));
                if(!empty($_existingFiles)) {
                    foreach ($_existingFiles as $f) {
                        unlink($f);
                    }
                    clearstatcache();
                }
            }

            if($uploadData['multiple'] === true && isset($uploadData['deleteData'])) {
                if(DEBUG) error_log("[DEBUG] ".__METHOD__." remove multiple existing: ".var_export($uploadData['deleteData'],true));
                foreach ($uploadData['deleteData'] as $k=>$v) {
                    $_file = $_path.'/'.$v;
                    if(file_exists($_file)) {
                        unlink($_file);
                    }
                    clearstatcache();
                }
            }

            if(isset($uploadData['tmp_name']) && isset($uploadData['name'])) {
                if(!move_uploaded_file($uploadData['tmp_name'],$_path.'/'.$uploadData['identifier'].'-'.$uploadData['name'])) {
                    throw new Exception("Can not move file to: ".$_path.'/'.$uploadData['identifier'].'-'.$uploadData['name']);
                }
            }
        }
    }
}