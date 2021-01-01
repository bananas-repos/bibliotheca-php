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
	 * @param int $id The collection ID to load
	 * @param string $right The rights mode. read, write or delete
	 * @return array
	 */
	public function load($id,$right="read") {
		$this->_collectionData = array();

		if(!empty($id) && Summoner::validate($id, 'digit')) {

			$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`, `c`.`created`,
					`c`.`owner`, `c`.`group`, `c`.`rights`, `c`.`defaultSearchField`,
					`u`.`name` AS username, `g`.`name` AS groupname
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString($right, "c")."
					AND `c`.`id` = '".$this->_DB->real_escape_string($id)."'";
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows > 0) {
					$this->_collectionData = $query->fetch_assoc();
					$this->_id = $this->_collectionData['id'];
				}
			} catch (Exception $e) {
				if(DEBUG) error_log("[DEBUG] ".__METHOD__."  mysql query: ".$queryStr);
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
	public function getCollections() {
		$ret = array();

		$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString("read", "c")."
					ORDER BY `c`.`name`";
		$query = $this->_DB->query($queryStr);

		if($query !== false && $query->num_rows > 0) {
			while(($result = $query->fetch_assoc()) != false) {
				$ret[$result['id']] = $result;
			}
		}

		return $ret;
	}

	/**
	 * Fields for the loaded collection.
	 *
	 * @return array
	 */
	public function getCollectionFields() {
		$ret = array();

		$queryStr = "SELECT `cf`.`fk_field_id` AS id, `sf`.`type`, `sf`.`displayname`, `sf`.`identifier`,
						`sf`.`searchtype`
						FROM `".DB_PREFIX."_collection_fields_".$this->_id."` AS cf
						LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
						ORDER BY `cf`.`sort`";
		$query = $this->_DB->query($queryStr);
		try {
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$ret[$result['identifier']] = $result;
				}
			}
		} catch (Exception $e) {
			if(DEBUG) error_log("[DEBUG] ".__METHOD__."  mysql query: ".$queryStr);
			error_log("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
		}

		return $ret;
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
}
