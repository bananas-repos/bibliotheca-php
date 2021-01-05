<?php
/**
 * Bibliotheca webclient
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

/**
 * Class ManageCollectionFields to manage fields from a existing collection
 */
class ManageCollectionFields {

	/**
	 * The database object
	 *
	 * @var mysqli
	 */
	private $_DB;

	/**
	 * The user object to query with
	 *
	 * @var Doomguy
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
	 * Store existing fields info for runtime
	 *
	 * @var array
	 */
	private $_cacheExistingSysFields = array();

	/**
	 * Store available fields info for runtime
	 *
	 * @var array
	 */
	private $_cacheAvailableFields = array();

	/**
	 * ManageCollections constructor
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct($databaseConnectionObject, $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * The id from the collection we are working with
	 *
	 * @param integer $id
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
	public function getAvailableFields($refresh=false) {

		if($refresh === false && !empty($this->_cacheAvailableFields)) {
			return $this->_cacheAvailableFields;
		}

		$queryStr = "SELECT `id`, `identifier`, `displayname`, `type`,
						`createstring`, `value`
					FROM `".DB_PREFIX."_sys_fields`
					ORDER BY `displayname`";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$this->_cacheAvailableFields[$result['id']] = $result;
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $this->_cacheAvailableFields;
	}

	/**
	 * Simple comma separated number string
	 *
	 * @param string $string
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
	 * Deletes relations and data from the collection!
	 *
	 * $fieldsSortString have to be validated already
	 *
	 * @param string $fieldsSortString
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

			$_newColumns = $this->_getSQLForCollectionColumns($ids);
			$_existingFields = $this->getExistingFields();

			// use the createsting info to determine if the field needs to be remove
			// from entry table or lookup table
			$_fieldsToCheckForDelete = $_existingFields;
			$queriesDeleteEntryTable = array();
			foreach($ids as $_id) {
				if(isset($_fieldsToCheckForDelete[$_id])) {
					unset($_fieldsToCheckForDelete[$_id]);
				}
			}
			if(!empty($_fieldsToCheckForDelete)) {
				foreach($_fieldsToCheckForDelete as $k=>$v)  {
					if(!empty($v['createstring'])) {
						$queriesDeleteEntryTable[] = "ALTER TABLE `".DB_PREFIX."_collection_entry_".$this->_collectionId."`
														DROP `".$v['identifier']."`";
					}
				}
			}

			$queryStrDeleteFields = "DELETE FROM `".DB_PREFIX."_collection_fields_".$this->_collectionId."`
						WHERE `fk_field_id` NOT IN (".implode(",",$ids).")";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStrDeleteFields,true));

			$queryStrDeletee2l = "DELETE FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_collectionId."`
						WHERE `fk_field` NOT IN (".implode(",",$ids).")";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStrDeletee2l,true));

			$queryStrInsertFields = "INSERT INTO `".DB_PREFIX."_collection_fields_".$this->_collectionId."` (`fk_field_id`,`sort`) VALUES ";
			foreach ($ids as $k => $v) {
				$queryStrInsertFields .= "($v,$k),";
			}
			$queryStrInsertFields = trim($queryStrInsertFields, ",");
			$queryStrInsertFields .= " ON DUPLICATE KEY UPDATE `sort` = VALUES(`sort`)";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStrInsertFields,true));

			if(!empty($_newColumns)) {
				$queryStrAlterEntry = "ALTER TABLE `".DB_PREFIX."_collection_entry_".$this->_collectionId."`";
				foreach($_newColumns as $k=>$v) {
					$queryStrAlterEntry .= " ADD ".$v['createstring'].",";
				}
				$queryStrAlterEntry = trim($queryStrAlterEntry, ",");
				if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStrAlterEntry,true));
			}

			try {
				$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

				if($this->_DB->query($queryStrDeleteFields) !== false && $this->_DB->query($queryStrDeletee2l) !== false) {

					$_check = true;
					if(!empty($queriesDeleteEntryTable)) {
						foreach($queriesDeleteEntryTable as $q) {
							if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($q,true));
							if($this->_DB->query($q) == false) {
								$_check = false;
								break;
							}
						}
					}

					if($this->_DB->query($queryStrInsertFields) !== false && $_check === true) {
						$alterQuery = false;
						if(!empty($_newColumns)) {
							$alterQuery = $this->_DB->query($queryStrAlterEntry);
						}
						if(!empty($_newColumns) && $alterQuery === false) {
							throw new Exception("Failed to insert alter the table.");
						}
					}
					else {
						throw new Exception("Failed to insert the new fields.");
					}
				}
				else {
					throw new Exception("Failed to delete old fields.");
				}
				$this->_DB->commit();
				$ret = true;
			}
			catch (Exception $e) {
				$this->_DB->rollback();
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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

		$queryStr = "SELECT `cf`.`fk_field_id` AS id, `sf`.`type`, `sf`.`displayname`, `sf`.`identifier`,
							`sf`.`createstring`
						FROM `".DB_PREFIX."_collection_fields_".$this->_collectionId."` AS cf
						LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
						ORDER BY `cf`.`sort`";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$this->_cacheExistingSysFields[$result['id']] = $result;
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					if(!in_array($result['Field'], $this->_protectedDBCols, true)) {
						$ret[$result['Field']] = $result['Field'];
					}
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

	/**
	 * Get the required SQL information from given field ids
	 * to create columns in entry table.
	 *
	 * @param array $columnIds sort=>fk_field_id
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
