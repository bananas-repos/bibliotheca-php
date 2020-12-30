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
 * Class ManageCollectionFields to manage fields from a existing collection
 */
class ManageCollectionFields {

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
     * The collection we are working with
     *
     * @var integer
     */
    private $_collectionId;

    /**
     * Which db cols should not be removed
     *
     * @var array
     */
    private $_protectedDBCols = array(
        'id','created','modified','modificationuser','owner','group','rights'
    );

    /**
     * Store existing fileds info for runtime
     *
     * @var array
     */
    private $_cacheExistingSysFields = array();

    /**
     * ManageCollections constructor
     *
     * @param $databaseConnectionObject
     * @param $userObj
     */
    public function __construct($databaseConnectionObject, $userObj) {
        $this->_DB = $databaseConnectionObject;
        $this->_User = $userObj;
    }

    /**
     * The id from the collection we are working with
     *
     * @param $id integer
     */
    public function setCollection($id) {
        if(!empty($id)) {
            $this->_collectionId = $id;
        }
    }

    /**
     * Get available fields based on user
     *
     * @return array
     * @todo No rights implemented yet. Maybe not needed. Management done by hand directly on DB
     */
    public function getAvailableFields() {
        $ret = array();

        $queryStr = "SELECT `id`, `identifier`, `displayname`, `type`,
                        `createstring`, `value`
                    FROM `".DB_PREFIX."_sys_fields`
                    ORDER BY `displayname`";
        $query = $this->_DB->query($queryStr);
        if($query !== false && $query->num_rows > 0) {
            while(($result = $query->fetch_assoc()) != false) {
                $ret[$result['id']] = $result;
            }
        }

        return $ret;
    }

    /**
     * Simple comma seperated number string
     *
     * @param $string string
     * @return bool
     */
    public function validateFieldSortString($string) {
        $ret = false;

        $_t = str_replace(",","",$string);
        if(Summoner::validate($_t, 'digit')) {
            $ret = true;
        }

        return $ret;
    }

    /**
     * $fieldsSortString have to valided already
     *
     * @todo remove non existing ones from table
     *
     * @param $fieldsSortString string
     * @return bool
     */
    public function updateFields($fieldsSortString) {
        $ret = false;
        $ids = array();

        $fieldsSortString = trim($fieldsSortString, ", ");
        if(strstr($fieldsSortString, ",")) {
            $ids = explode(",", $fieldsSortString);
        }
        else {
            $ids[] = $fieldsSortString;
        }

        if(!empty($ids)) {
            $this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

            $queryStr1 = "DELETE FROM `".DB_PREFIX."_collection_fields_".$this->_collectionId."`
                        WHERE `fk_field_id` NOT IN (".implode(",",$ids).")";
            $q1 = $this->_DB->query($queryStr1);
            if($q1 !== false) {
                // https://dev.mysql.com/doc/refman/8.0/en/insert-on-duplicate.html
                $queryStr = "INSERT INTO `".DB_PREFIX."_collection_fields_".$this->_collectionId."` (`fk_field_id`,`sort`) VALUES ";
                foreach ($ids as $k => $v) {
                    $queryStr .= "($v,$k),";
                }
                $queryStr = trim($queryStr, ",");
                $queryStr .= " AS newEntry(fid,s) ON DUPLICATE KEY UPDATE `sort`=s";

                $q2 = $this->_DB->query($queryStr);
                if($q2 !== false) {
                    $_newColumns = $this->_getSQLForCollectionColumns($ids);
                    $alterQuery = false;
                    if(!empty($_newColumns)) {
                        $alterString = "ALTER TABLE `".DB_PREFIX."_collection_entry_".$this->_collectionId."`";
                        foreach($_newColumns as $k=>$v) {
                            $alterString .= " ADD ".$v['createstring'].",";
                        }
                        $alterString = trim($alterString, ",");
                        $alterQuery = $this->_DB->query($alterString);
                    }
                    if(!empty($_newColumns) && $alterQuery == false) {
                        $this->_DB->rollback();
                        error_log('ERROR Failed to update entry table: '.var_export($alterString, true));
                    }
                    else {
                        $this->_DB->commit();
                        $ret = true;
                    }
                }
                else {
                    $this->_DB->rollback();
                    error_log('ERROR Failed to update collection fields: '.var_export($queryStr1, true));
                    error_log('ERROR Failed to update collection fields: '.var_export($queryStr, true));
                }
            }
        }

        return $ret;
    }

    /**
     * Get the fields for currently loaded collection.
     *
     * @param bool $refresh True to reload from DB
     * @return array
     */
    public function getExistingFields($refresh=false) {
        if($refresh === false && !empty($this->_cacheExistingSysFields)) {
            return $this->_cacheExistingSysFields;
        }

        $queryStr = "SELECT `cf`.`fk_field_id` AS id, `sf`.`type`, `sf`.`displayname`, `sf`.`identifier`
                        FROM `".DB_PREFIX."_collection_fields_".$this->_collectionId."` AS cf
                        LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
                        ORDER BY `cf`.`sort`";
        $query = $this->_DB->query($queryStr);
        if($query !== false && $query->num_rows > 0) {
            while(($result = $query->fetch_assoc()) != false) {
                $this->_cacheExistingSysFields[$result['id']] = $result;
            }
        }

        return $this->_cacheExistingSysFields;
    }

    /**
     * Get the column names from current collection entry table
     *
     * @return array
     */
    private function _getExistingCollectionColumns() {
        $ret = array();

        $queryStr = "SHOW COLUMNS FROM `".DB_PREFIX."_collection_entry_".$this->_collectionId."`";
        $query = $this->_DB->query($queryStr);
        if($query !== false && $query->num_rows > 0) {
            while(($result = $query->fetch_assoc()) != false) {
                if(!in_array($result['Field'], $this->_protectedDBCols, true)) {
                    $ret[$result['Field']] = $result['Field'];
                }
            }
        }

        return $ret;
    }

    /**
     * Get the required SQL information from given field ids
     * to create columns in entry table.
     *
     * @param $columnIds array sort=>fk_field_id
     * @return array
     */
    private function _getSQLForCollectionColumns($columnIds) {
        $_fields = array();
        // enrich with information
        $_sysFields = $this->getAvailableFields();
        $_existingDBColumns = $this->_getExistingCollectionColumns();
        if(!empty($columnIds)) {
            foreach($columnIds as $sort=>$fieldId) {
                if(isset($_sysFields[$fieldId])) {
                    $_fd = $_sysFields[$fieldId];
                    if(isset($_existingDBColumns[$_fd['identifier']])) continue;
                    if(empty($_fd['createstring'])) continue;
                    $_fields[$fieldId] = $_fd;
                }
            }
        }
        return $_fields;
    }
}