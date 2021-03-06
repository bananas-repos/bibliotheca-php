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

/**
 * Class Trite
 *
 * Everything related for using a collection.
 * For manage collection use Managecollection class
 *
 */
class Trite {
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
	 * Currently loaded collection to work with
	 *
	 * @var string
	 */
	private $_id;

	/**
	 * Current loaded collection data as an array
	 *
	 * @var array
	 */
	private $_collectionData;

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
	 * Cache for already loaded collection fields
	 *
	 * @var array
	 */
	private $_cacheExistingCollectionFields = array();

	/**
	 * Trite constructor.
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
	 * Set the following options which can be used in DB queries
	 * array(
	 *  'limit' => RESULTS_PER_PAGE,
	 *  'offset' => (RESULTS_PER_PAGE * ($_curPage-1)),
	 *  'orderby' => $_sort,
	 *  'sortDirection' => $_sortDirection
	 * );
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
	 * Get information to display for current collection
	 * based on current user and given rights
	 *
	 * @param string $id The collection ID to load
	 * @param string $right The rights mode. read, write or delete
	 * @return array
	 */
	public function load(string $id,$right="read"): array {
		$this->_collectionData = array();

		if(!empty($id) && Summoner::validate($id, 'digit')) {

			$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`, `c`.`created`,
					`c`.`owner`, `c`.`group`, `c`.`rights`, `c`.`defaultSearchField`,
					`c`.`defaultSortField`,`c`.`advancedSearchTableFields`,
					`u`.`name` AS username, `g`.`name` AS groupname
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString($right, "c")."
					AND `c`.`id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows > 0) {
					$this->_collectionData = $query->fetch_assoc();
					$this->_collectionData['advancedSearchTableFields'] = $this->_loadAdvancedSearchTableFields($this->_collectionData['advancedSearchTableFields']);
					$this->_id = $this->_collectionData['id'];
				}
			} catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $this->_collectionData;
	}

	/**
	 * get the value of the specified param from the collection data array
	 *
	 * @param string $param
	 * @return bool|mixed
	 */
	public function param($param) {
		$ret = false;

		$param = trim($param);

		if(!empty($param) && isset($this->_collectionData[$param])) {
			$ret = $this->_collectionData[$param];
		}

		return $ret;
	}

	/**
	 * Get all available collections for display based on current user
	 * and read mode
	 *
	 * @return array
	 */
	public function getCollections($rightsMode="read") {
		$ret = array();

		$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString($rightsMode, "c")."
					ORDER BY `c`.`name`";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);

			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$ret[$result['id']] = $result;
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

	/**
	 * Fields for the loaded collection.
	 *
	 * Works only if collection is already loaded and thus rights are validated
	 *
	 * @return array
	 */
	public function getCollectionFields(): array {
		if(empty($this->_id)) return array();

		if(!empty($this->_cacheExistingCollectionFields)) {
			return $this->_cacheExistingCollectionFields;
		}
		$this->_cacheExistingCollectionFields = array();

		$queryStr = "SELECT `cf`.`fk_field_id` AS id, `sf`.`type`, `sf`.`displayname`, `sf`.`identifier`,
						`sf`.`searchtype`
						FROM `".DB_PREFIX."_collection_fields_".$this->_id."` AS cf
						LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
						ORDER BY `cf`.`sort`";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		$query = $this->_DB->query($queryStr);
		try {
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$this->_cacheExistingCollectionFields[$result['identifier']] = $result;
				}
			}
		} catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
		}

		return $this->_cacheExistingCollectionFields;
	}

	/**
	 * return the simple search fields for loaded collection
	 * Every field witch has a column in the entry table is a simple search field.
	 * Name starts with entry
	 *
	 * @see ManageCollectionFields->getSimpleSearchFields()
	 *
	 * @return array
	 */
	public function getSimpleSearchFields(): array {
		$ret = array();

		$fields = $this->getCollectionFields();
		if(!empty($fields)) {
			foreach($fields as $k=>$v) {
				if(isset($v['searchtype']) && strpos($v['searchtype'],'entry') !== false) {
					$ret[$k] = $v;
				}
			}
		}

		$def['created'] = array('identifier' => 'created', 'displayname' => 'Created', 'type' => 'systemfield');
		$def['modified'] = array('identifier' => 'modified', 'displayname' => 'Modified', 'type' => 'systemfield');
		$ret = $def + $ret;

		return $ret;
	}

	/**
	 * Get the tag fields (searchtype = tag) and their values.
	 * Possible optimization can be done here: Do not load everything at once, but per field
	 * Needs also change in frontend to separate those calls
	 *
	 * Works only if collection is already loaded and thus rights are validated
	 *
	 * @param string $search String value to search value against
	 * @return array
	 */
	public function getTags($search='') {
		$ret = array();

		$queryStr = "SELECT `cf`.`fk_field_id` AS id,
							`sf`.`type`,
							`sf`.`displayname`,
							`sf`.`identifier`,
							`e2l`.`value`
						FROM `".DB_PREFIX."_collection_fields_".$this->_DB->real_escape_string($this->_id)."` AS cf
						LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
						LEFT JOIN `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_id)."` AS e2l ON `e2l`.`fk_field` = `sf`.`id`
						WHERE `sf`.`searchtype` = 'tag'";
		if(!empty($search)) {
			$queryStr .= " AND MATCH (`e2l`.`value`) AGAINST ('".$this->_DB->real_escape_string($search)."' IN BOOLEAN MODE)";
		}
		else {
			$queryStr .= " ORDER BY `sf`.`displayname`, `e2l`.`value`";
		}
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		$query = $this->_DB->query($queryStr);
		try {
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$ret[$result['id']]['id'] = $result['id'];
					$ret[$result['id']]['displayname'] = $result['displayname'];
					$ret[$result['id']]['identifier'] = $result['identifier'];
					$ret[$result['id']]['type'] = $result['type'];
					if(!empty($result['value'])) {
						$ret[$result['id']]['entries'][$result['value']] = $result['value'];
					}
					elseif(!isset($ret[$result['id']]['entries'])) {
						$ret[$result['id']]['entries'] = array();
					}
				}
			}
		} catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

	/**
	 * Load the tools configured for the current loaded collection
	 *
	 * @return array
	 */
	public function getAvailableTools() {
		$ret = array();

		$queryStr = "SELECT `t`.`id`, `t`.`name`, `t`.`description`, `t`.`action`, `t`.`target`
					FROM `".DB_PREFIX."_tool2collection` AS t2c
					LEFT JOIN `".DB_PREFIX."_tool` AS t ON t.id = t2c.fk_tool_id
					WHERE t2c.fk_collection_id = '".$this->_DB->real_escape_string($this->_id)."'";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$ret[$result['id']] = $result;
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return  $ret;
	}

	/**
	 * set some defaults by init of the class
	 *
	 * @return void
	 */
	private function _setDefaults() {
		// default query options
		$options['limit'] = 5;
		$options['offset'] = false;
		$options['sort'] = false;
		$options['sortDirection'] = false;
		$this->setQueryOptions($options);
	}

	/**
	 * Make a key=>value array of a comma seperated string and use the value as key
	 *
	 * @param array $data
	 * @return array
	 */
	private function _loadAdvancedSearchTableFields($data) {
		$ret = array();

		$_t = explode(',',$data);
		foreach($_t as $e) {
			$ret[$e] = $e;
		}

		return $ret;
	}
}
