<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2023 Johannes Keßler
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
 *  along with this program.  If not, see http://www.gnu.org/licenses/gpl-3.0.
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
	private mysqli $_DB;

	/**
	 * The user object to query with
	 *
	 * @var Doomguy
	 */
	private Doomguy $_User;

	/**
	 * Currently loaded collection to work with
	 *
	 * @var string
	 */
	private string $_id;

	/**
	 * Current loaded collection data as an array
	 *
	 * @var array
	 */
	private array $_collectionData;

	/**
	 * Options for db queries
	 *  'limit' => int,
	 *  'offset' => int,
	 *  'orderby' => string,
	 *  'sortDirection' => ASC|DESC
	 *
	 * @var array
	 */
	private array $_queryOptions;

	/**
	 * Cache for already loaded collection fields
	 *
	 * @var array
	 */
	private array $_cacheExistingCollectionFields = array();

	/**
	 * Trite constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct(mysqli $databaseConnectionObject, Doomguy $userObj) {
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
	 *
	 * @param array $options
	 */
	public function setQueryOptions(array $options): void {

		if(!isset($options['limit'])) $options['limit'] = 5;
		if(!isset($options['offset'])) $options['offset'] = false;
		if(!isset($options['sort'])) $options['sort'] = false;
		if(!isset($options['sortDirection'])) $options['sortDirection'] = false;

		$this->_queryOptions = $options;
	}

	/**
	 * Get information to display for given collection
	 * based on current user and given rights
	 *
	 * @param string $id The collection ID to load
	 * @param string $right The rights mode. read, write or delete
	 * @return array
	 */
	public function load(string $id, string $right="read"): array {
		$this->_collectionData = array();

		if(!empty($id) && Summoner::validate($id, 'digit')) {

			$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`, `c`.`created`,
					`c`.`owner`, `c`.`group`, `c`.`rights`, `c`.`defaultSearchField`,
					`c`.`defaultSortField`,`c`.`advancedSearchTableFields`,
					`c`.`defaultSortOrder`,
					`u`.`name` AS username, `g`.`name` AS groupname
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString($right, "c")."
					AND `c`.`id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows > 0) {
					$this->_collectionData = $query->fetch_assoc();
					$this->_collectionData['advancedSearchTableFields'] = $this->_loadAdvancedSearchTableFields($this->_collectionData['advancedSearchTableFields']);
					$this->_id = $this->_collectionData['id'];
				}
			} catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $this->_collectionData;
	}

	/**
	 * get the value of the specified param from the collection data array
	 *
	 * @param string $param
	 * @return string
	 */
	public function param(string $param): string {
		$ret = '';

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
	 * @param string $rightsMode
	 * @return array
	 */
	public function getCollections(string $rightsMode="read"): array {
		$ret = array();

		$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString($rightsMode, "c")."
					ORDER BY `c`.`name`";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$query = $this->_DB->query($queryStr);

			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$ret[$result['id']] = $result;
				}
			}
		}
		catch (Exception $e) {
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		$query = $this->_DB->query($queryStr);
		try {
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$this->_cacheExistingCollectionFields[$result['identifier']] = $result;
				}
			}
		} catch (Exception $e) {
            Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
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

        // add systemfields
		$def['created'] = array('identifier' => 'created', 'displayname' => 'Created', 'type' => 'systemfield');
		$def['modified'] = array('identifier' => 'modified', 'displayname' => 'Modified', 'type' => 'systemfield');
        $def['search'] = array('identifier' => 'search', 'displayname' => 'Combined Search', 'type' => 'systemfield');
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
	public function getTags(string $search=''): array {
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
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
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
            Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

	/**
	 * Load the tools configured for the current loaded collection
	 *
	 * @return array
	 */
	public function getAvailableTools(): array {
		$ret = array();

		$queryStr = "SELECT `t`.`id`, `t`.`name`, `t`.`description`, `t`.`action`, `t`.`target`
					FROM `".DB_PREFIX."_tool2collection` AS t2c
					LEFT JOIN `".DB_PREFIX."_tool` AS t ON t.id = t2c.fk_tool_id
					WHERE t2c.fk_collection_id = '".$this->_DB->real_escape_string($this->_id)."'";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$query = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$ret[$result['id']] = $result;
				}
			}
		}
		catch (Exception $e) {
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return  $ret;
	}

	/**
	 * Some statistics about the current collection.
	 * Entries, tags, storage
	 * Adds a stats array to _collectionData
	 *
	 * @return array
	 */
	public function getStats(): array {
		if(empty($this->_id)) return array();

		$this->_collectionData['stats'] = array();

		$queryStr = "SELECT COUNT(*) AS entries FROM `".DB_PREFIX."_collection_entry_".$this->_id."`";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$query = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				$result = $query->fetch_assoc();
				$this->_collectionData['stats']['entriesCount'] = $result['entries'];
			}
		}
		catch (Exception $e) {
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		$tags = $this->getTags();
		$tagsCount = 0;
		foreach ($tags as $k=>$v) {
			$tagsCount += count($v['entries']);
		}
		$this->_collectionData['stats']['tagsCount'] = $tagsCount;

		$tableSize = 0; // in MB
		$queryStr = "SELECT (DATA_LENGTH + INDEX_LENGTH) AS `size`
						FROM information_schema.TABLES
						WHERE TABLE_SCHEMA = 'bibliotheca'
  						AND TABLE_NAME LIKE 'bib_collection_%_".$this->_id."'
						ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$query = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$tableSize += $result['size'];
				}
			}
		}
		catch (Exception $e) {
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}
		$this->_collectionData['stats']['tableSize'] = Summoner::bytesToHuman($tableSize);

		$this->_collectionData['stats']['storageSize'] = Summoner::bytesToHuman(Summoner::folderSize(PATH_STORAGE.'/'.$this->_id));


		return $this->_collectionData;
	}

	/**
	 * set some defaults by init of the class
	 *
	 * @return void
	 */
	private function _setDefaults(): void {
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
	 * @param string $data
	 * @return array
	 */
	private function _loadAdvancedSearchTableFields(string $data): array {
		$ret = array();

		if(!strstr($data, ',')) {
			$ret[$data] = $data;
		}
		else {
			$_t = explode(',',$data);
			foreach($_t as $e) {
				$ret[$e] = $e;
			}
		}

		return $ret;
	}
}
