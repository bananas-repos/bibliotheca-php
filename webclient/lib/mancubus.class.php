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

/**
 * Class Mancubus everything to show an entry
 */
class Mancubus {
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
     * Currently loaded collection to work with
     *
     * @var number
     */
    private $_collectionId;

    /**
     * Options for db queries
     *  'limit' => int,
     *  'offset' => int,
     *  'orderby' => string,
     *  'sortDirection' => ASC|DESC
     *
     * @var array
     */
    private $_queryOptions;

    /**
     * Mancubus constructor.
     *
     * @param $databaseConnectionObject
     * @param $userObj
     */
    public function __construct($databaseConnectionObject, $userObj) {
        $this->_DB = $databaseConnectionObject;
        $this->_User = $userObj;

        $this->_setDefaults();
    }

    /**
     * Set the to work with collection id
     *
     * @param $collectionId Number
     */
    public function setCollection($collectionId) {
        if(!empty($collectionId)) {
            $this->_collectionId = $collectionId;
        }
    }

    /**
     * Set the following options which can be used in DB queries
     * array(
     *  'limit' => RESULTS_PER_PAGE,
     *  'offset' => (RESULTS_PER_PAGE * ($_curPage-1)),
     *  'orderby' => $_sort,
     *  'sortDirection' => $_sortDirection
     * );
     *
     * @param array $options
     */
    public function setQueryOptions($options) {

        if(!isset($options['limit'])) $options['limit'] = 5;
        if(!isset($options['offset'])) $options['offset'] = false;
        if(!isset($options['sort'])) $options['sort'] = false;
        if(!isset($options['sortDirection'])) $options['sortDirection'] = false;

        $this->_queryOptions = $options;
    }
    
    /**
     * Get all available collections for display based on current user
     *
     * @param int $selections Number of selections
     * @param int $entries Number of entries
     * @param string $search Search string to search for
     * @return array
     */
    public function getLatest($selections, $entries, $search='') {
        $ret = array();

        $queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`, `c`.`created`,
                    `c`.`owner`, `c`.`group`, `c`.`rights`, 
                    `u`.`name` AS username, `g`.`name` AS groupname
                    FROM `".DB_PREFIX."_collection` AS c
                    LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
                    LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
                    WHERE ".$this->_User->getSQLRightsString("read", "c")."
                    ORDER BY `c`.`name`
                    LIMIT $selections";
        $query = $this->_DB->query($queryStr);

        if($query !== false && $query->num_rows > 0) {
            while(($result = $query->fetch_assoc()) != false) {
                $_mObj = new Mancubus($this->_DB,$this->_User);
                $_mObj->setCollection($result['id']);
                $_mObj->setQueryOptions(array('limit' => $entries));

                if(!empty($search)) {
                    require_once 'lib/trite.class.php';
                    $_colObj = new Trite($this->_DB,$this->_User);
                    $_colObj->load($result['id']);

                    $result['entries'] = $_mObj->getEntries($_colObj->param('defaultSearchField'),$search,true);
                }
                else {
                    $result['entries'] = $_mObj->getEntries();
                }
                $ret[$result['id']] = $result;
                unset($_mObj);
            }
        }

        return $ret;
    }

    /**
     * Get entries for loaded collection limited by search in
     * given colName and colValue
     *
     * @param string $colName Table col to search
     * @param string $colValue Value to search in col
     * @param bool $fulltext If col has a fulltext index use it.
     * @return array
     */
    public function getEntries($colName='', $colValue='',$fulltext=false) {
        $ret = array();

        if(!empty($this->_collectionId)) {
            // split since part of it is used later
            $querySelect = "SELECT *";
            $queryFrom = " FROM `".DB_PREFIX."_collection_entry_".$this->_DB->real_escape_string($this->_collectionId)."` AS t";
            $queryWhere = " WHERE ".$this->_User->getSQLRightsString("read", "t")."";

            if(!empty($colName) && !empty($colValue)) {
                if($fulltext === true) {
                    $queryWhere .= " AND MATCH (`t`.`".$this->_DB->real_escape_string($colName)."`) 
                        AGAINST ('".$this->_DB->real_escape_string($colValue)."' IN BOOLEAN MODE)";
                }
                else {
                    $queryWhere .= " AND `t`.`" . $this->_DB->real_escape_string($colName) . "` = '" . $this->_DB->real_escape_string($colValue) . "'";
                }
            }

            $queryOrder = " ORDER BY";
            if(!empty($this->_queryOptions['sort'])) {
                $queryOrder .= ' t.'.$this->_queryOptions['sort'];
            }
            else {
                $queryOrder .= " t.created";
            }
            if(!empty($this->_queryOptions['sortDirection'])) {
                $queryOrder .= ' '.$this->_queryOptions['sortDirection'];
            }
            else {
                $queryOrder .= " DESC";
            }

            $queryLimit = '';
            if(!empty($this->_queryOptions['limit'])) {
                $queryLimit .= " LIMIT ".$this->_queryOptions['limit'];
                # offset can be 0
                if($this->_queryOptions['offset'] !== false) {
                    $queryLimit .= " OFFSET ".$this->_queryOptions['offset'];
                }
            }

            if(DEBUG) error_log("[DEBUG] ".__METHOD__."  data: ".$querySelect.$queryFrom.$queryWhere.$queryOrder.$queryLimit);

            $query = $this->_DB->query($querySelect.$queryFrom.$queryWhere.$queryOrder.$queryLimit);

            if($query !== false && $query->num_rows > 0) {
                $_entryFields = $this->_getEntryFields();

                while(($result = $query->fetch_assoc()) != false) {
                    $result = $this->_mergeEntryWithFields($result, $_entryFields);

                    $ret['results'][$result['id']] = $result;
                }

                $query = $this->_DB->query("SELECT COUNT(t.id) AS amount ".$queryFrom.$queryWhere);
                $result = $query->fetch_assoc();
                $ret['amount'] = $result['amount'];
            }
        }

        return $ret;
    }

    /**
     * Retrive all the data needed to display the entry for given entryId
     * @param $entryId
     * @return array|mixed
     */
    public function getEntry($entryId) {
        $ret = array();

        if(!empty($this->_collectionId) && !empty($entryId)) {
            $queryStr = "SELECT * 
                        FROM `".DB_PREFIX."_collection_entry_".$this->_DB->real_escape_string($this->_collectionId)."` 
                        WHERE ".$this->_User->getSQLRightsString("read")."
                        AND `id` = '".$this->_DB->real_escape_string($entryId)."'";
            $query = $this->_DB->query($queryStr);

            if($query !== false && $query->num_rows > 0) {
                $_entryFields = $this->_getEntryFields();

                if(($result = $query->fetch_assoc()) != false) {
                    $ret = $this->_mergeEntryWithFields($result, $_entryFields);
                }
            }
        }

        return $ret;
    }

    /**
     * Get entries for loaded collection by looking for the given value in given field
     *
     * @param Number $fieldId ID of the field to search in
     * @param String $fieldValue Value of the field
     * @return array
     */
    public function getEntriesByFieldValue($fieldId, $fieldValue) {
        $ret = array();

        $fieldData = array();
        $queryStr = "SELECT `identifier`, `type` FROM `".DB_PREFIX."_sys_fields`
                        WHERE `id` = '".$this->_DB->real_escape_string($fieldId)."'";
        $query = $this->_DB->query($queryStr);
        if($query !== false && $query->num_rows > 0) {
            if(($result = $query->fetch_assoc()) != false) {
                $fieldData = $result;
            }
        }

        if(empty($fieldData)) return $ret;

        if($fieldData['type'] !== "lookupmultiple") {
            return $this->getEntries($fieldData['identifier'], $fieldValue);
        }

        $querySelect = "SELECT `fk_entry`";
        $queryFrom = " FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."` AS t";
        $queryWhere = " WHERE t.fk_field = '".$this->_DB->real_escape_string($fieldId)."'
                    AND t.value = '".$this->_DB->real_escape_string($fieldValue)."'";

        $queryOrder = " ORDER BY";
        if(!empty($this->_queryOptions['sort'])) {
            $queryOrder .= ' t.'.$this->_queryOptions['sort'];
        }
        else {
            $queryOrder .= " t.value";
        }
        if(!empty($this->_queryOptions['sortDirection'])) {
            $queryOrder .= ' '.$this->_queryOptions['sortDirection'];
        }
        else {
            $queryOrder .= " DESC";
        }

        $queryLimit = '';
        if(!empty($this->_queryOptions['limit'])) {
            $queryLimit .= " LIMIT ".$this->_queryOptions['limit'];
            # offset can be 0
            if($this->_queryOptions['offset'] !== false) {
                $queryLimit .= " OFFSET ".$this->_queryOptions['offset'];
            }
        }

        $query = $this->_DB->query($querySelect.$queryFrom.$queryWhere.$queryOrder.$queryLimit);

        if($query !== false && $query->num_rows > 0) {
            while(($result = $query->fetch_assoc()) != false) {
                $_r = $this->getEntry($result['fk_entry']);
                $ret['results'][$_r['id']] = $_r;
            }

            $query = $this->_DB->query("SELECT COUNT(t.value) AS amount ".$queryFrom.$queryWhere);
            $result = $query->fetch_assoc();
            $ret['amount'] = $result['amount'];
        }

        return $ret;
    }

    /**
     * Get tags for loaded collection. Provide earch term to use match against db search
     *
     * @todo Replace with trite class
     *
     * @param mixed $search Search term
     * @return array
     */
    public function getTags($search=false) {
        $ret = array();

        if(!empty($this->_collectionId)) {
            $queryStr = "SELECT `cf`.`fk_field_id` AS id, `sf`.`type`, `sf`.`displayname`, `sf`.`identifier`
                        FROM `".DB_PREFIX."_collection_fields_".$this->_DB->real_escape_string($this->_collectionId)."` AS cf
                        LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
                        WHERE `sf`.`searchtype` = 'tag' 
                        ORDER BY `sf`.`displayname`";

            $query = $this->_DB->query($queryStr);
            if($query !== false && $query->num_rows > 0) {
                while(($result = $query->fetch_assoc()) != false) {
                    $ret[$result['id']] = $result;
                    $ret[$result['id']]['entries'] = array();

                    $_mn = '_loadTagDistinct_'.$result['type'];
                    if(method_exists($this, $_mn)) {
                        $ret[$result['id']]['entries'] = $this->$_mn($result,$search);
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Return the storage info for loaded collection
     * Used by API
     * @return array|mixed
     */
    public function getEntryStructure() {
        $ret = array();

        $_entryFields = $this->_getEntryFields();
        $ret = $this->_mergeEntryWithFields($ret, $_entryFields);

        return $ret;
    }

    /**
     * Load the fields from the loaded collection
     * @return array
     */
    private function _getEntryFields() {
        $ret = array();

        if(!empty($this->_collectionId)) {
            $queryStr = "SELECT `cf`.`fk_field_id` AS id, `sf`.`type`, `sf`.`displayname`, `sf`.`identifier`,
                                `sf`.`value` AS preValue, `sf`.`apiinfo` 
                        FROM `".DB_PREFIX."_collection_fields_".$this->_DB->real_escape_string($this->_collectionId)."` AS cf
                        LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
                        ORDER BY `cf`.`sort`";
            $query = $this->_DB->query($queryStr);
            if($query !== false && $query->num_rows > 0) {
                while(($result = $query->fetch_assoc()) != false) {
                    $ret[$result['id']] = $result;
                }
            }
        }

        return $ret;
    }

    /**
     * Merge the loaded information from collection_entry with the given
     * configured fields
     * @param array $entryData Loaded entry
     * @param array $entryFields Loaded fields
     * @return mixed
     */
    private function _mergeEntryWithFields($entryData, $entryFields) {
        if(!empty($entryFields)) {
            foreach($entryFields as $f) {
                $_mnValue = '_loadFieldValue_'.$f['type'];

                if(isset($entryData[$f['identifier']])) {
                    $f['value'] = $entryData[$f['identifier']];
                    unset($entryData[$f['identifier']]);
                } elseif(method_exists($this, $_mnValue) && isset($entryData['id'])) {
                    $f['value'] = $this->$_mnValue($entryData['id'], $f);
                }

                $_mnSelectionValue = '_loadFieldSelection_'.$f['type'];
                if(method_exists($this, $_mnSelectionValue) && isset($f['preValue'])) {
                    $f['preValue'] = $this->$_mnSelectionValue($f['preValue']);
                }

                $entryData['fields'][$f['identifier']] = $f;
            }
        }
        return $entryData;
    }

    /**
     * Load the values for given $entryId for $fieldData
     * lookup function for field type lookupmultiple
     * @param Numer $entryId
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
     * Load and prepare the value for a selection field
     *
     * @param $data string
     * @return array
     */
    private function _loadFieldSelection_selection($data) {
        $ret = array();

        if(is_string($data)) {
            if(strstr($data, ',')) {
                $ret = explode(',',$data);
            }
            else {
                $ret[] = $data;
            }
        }

        return $ret;
    }

    /**
     * Load the selection as tag.
     * Search is a 1:1 match
     *
     * @param array $fieldData The sys field data
     * @param mixed $search Searchterm
     * @return array
     */
    private function _loadTagDistinct_selection($fieldData,$search=false) {
        return $this->_loadColAsTagFromEntryTable($fieldData['identifier'],$search);
    }

    /**
     * Load the data for lookupmultiple field. Provide field id and optional searchterm.
     * Uses currently loaded collection
     *
     * @param array $fieldData The field data to use
     * @param mixed $search Searchterm to run a match against DB search
     * @return array
     */
    private function _loadTagDistinct_lookupmultiple($fieldData,$search=false) {
        $ret = array();

        if(!empty($fieldData) && !empty($this->_collectionId)) {
            $queryStr = "SELECT DISTINCT(`value`) 
                        FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
                        WHERE `fk_field` = '".$this->_DB->real_escape_string($fieldData['id'])."'";
            if(!empty($search)) {
                $queryStr .= " AND MATCH (`value`) AGAINST ('" . $this->_DB->real_escape_string($search) . "' IN BOOLEAN MODE)";
            }

            try {
                if(DEBUG) error_log("[DEBUG] ".__METHOD__." mysql query: ".$queryStr);

                $query = $this->_DB->query($queryStr);
                if ($query !== false && $query->num_rows > 0) {
                    while (($result = $query->fetch_assoc()) != false) {
                        $ret[] = $result['value'];
                    }
                }
            }
            catch (Exception $e) {
                error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());

            }
        }

        return $ret;
    }

    /**
     * Load the data from lookupmultiple field. In this case $fieldata is overwritten
     * and year is used.
     *
     * @param array $fieldData
     * @param bool $search
     * @return array
     */
    private function _loadTagDistinct_year($fieldData,$search=false) {
        return $this->_loadColAsTagFromEntryTable("year",$search);
    }

    /**
     * Get the distinct data from a col and optionaml search term
     *
     * @param $colname
     * @param $search
     * @return array
     */
    private function _loadColAsTagFromEntryTable($colname,$search) {
        $ret = array();

        if(!empty($colname) && !empty($this->_collectionId)) {
            if(!empty($search)) {
                $queryStr = "SELECT `".$this->_DB->real_escape_string($colname)."` 
                        FROM `".DB_PREFIX."_collection_entry_".$this->_DB->real_escape_string($this->_collectionId)."`";
                $queryStr .= " WHERE `".$colname."` = '".$this->_DB->real_escape_string($search)."'";
            }
            else {
                $queryStr = "SELECT DISTINCT(`".$this->_DB->real_escape_string($colname)."`) 
                        FROM `".DB_PREFIX."_collection_entry_".$this->_DB->real_escape_string($this->_collectionId)."`";
            }

            $queryStr .= " ORDER BY `".$this->_DB->real_escape_string($colname)."` DESC";

            try {
                if(DEBUG) error_log("[DEBUG] ".__METHOD__." mysql query: ".$queryStr);

                $query = $this->_DB->query($queryStr);
                if($query !== false && $query->num_rows > 0) {
                    while(($result = $query->fetch_assoc()) != false) {
                        if(!empty($result[$colname])) {
                            $ret[] = $result[$colname];
                        }
                    }
                }
            }
            catch (Exception $e) {
                error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
            }
        }


        return $ret;
    }

    /**
     * set some defaults by init of the class
     */
    private function _setDefaults() {
        // default query options
        $options['limit'] = 5;
        $options['offset'] = false;
        $options['sort'] = false;
        $options['sortDirection'] = false;
        $this->setQueryOptions($options);
    }
}