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
 * Class ManageCollections to manage collections
 */
class ManageCollections {
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
	 * ManageCollections constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct($databaseConnectionObject, $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Load collection info from table. Checks user rights
	 *
	 * @param string $id
	 * @param string $rightsMode
	 * @return array
	 */
	public function getCollection($id,$rightsMode="read") {
		$ret = array();

		if (Summoner::validate($id, 'digit')) {
			$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`, `c`.`created`
					FROM `".DB_PREFIX."_collection` AS c
					WHERE ".$this->_User->getSQLRightsString($rightsMode, "c")."
					AND `c`.`id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows > 0) {
					$ret = $query->fetch_assoc();
				}
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Get all available collections for display based on current user
	 *
	 * @return array
	 */
	public function getCollections() {
		$ret = array();

		$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`, `c`.`created`,
					`c`.`owner`, `c`.`group`, `c`.`rights`, 
					`u`.`name` AS username, `g`.`name` AS groupname
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString("read", "c")."
					ORDER BY `c`.`name`";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);

			if ($query !== false && $query->num_rows > 0) {
				while (($result = $query->fetch_assoc()) != false) {
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
	 * Retrieve the groups for selection based on user rights
	 *
	 * @return array
	 */
	public function getGroupsForSelection() {
		$ret = array();

		$queryStr = "SELECT `id`, `name`, `description` 
					FROM `".DB_PREFIX."_group` 
					WHERE ".$this->_User->getSQLRightsString()."
					ORDER BY `name`";
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
	 * Fetch all available users for selection based on current user rights
	 *
	 * @return array
	 */
	public function getUsersForSelection() {
		$ret = array();

		$queryStr = "SELECT `id`, `name`, `login`
						FROM `".DB_PREFIX."_user`
						WHERE ".$this->_User->getSQLRightsString()."";
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
	 * Fetch all available tools based on current user rights
	 *
	 * @return array
	 */
	public function getToolsForSelection() {
		$ret = array();

		$queryStr = "SELECT `id`, `name`, `description`
						FROM `".DB_PREFIX."_tool`
						WHERE ".$this->_User->getSQLRightsString()."";
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
	 * Create new collection entry in collection table. Provide valid data
	 * only $name will be checked again
	 *
	 * @param array $data
	 * @return bool
	 */
	public function createCollection($data) {
		$ret = false;

		if(!empty($data['name']) === true
			&& $this->_validNewCollectionName($data['name']) === true
		) {
			$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
			try {
				$queryStr = "INSERT INTO `".DB_PREFIX."_collection`
							SET `name` = '".$this->_DB->real_escape_string($data['name'])."',
								`description` = '".$this->_DB->real_escape_string($data['description'])."',
								`owner` = '".$this->_DB->real_escape_string($data['owner'])."',
								`group` = '".$this->_DB->real_escape_string($data['group'])."',
								`rights` = '".$this->_DB->real_escape_string($data['rights'])."',
								`defaultSearchField` = '".$this->_DB->real_escape_string($data['defaultSearchField'])."'";
				if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
				$this->_DB->query($queryStr);
				$newId = $this->_DB->insert_id;


				$queryEntry2lookup = "CREATE TABLE `".DB_PREFIX."_collection_entry2lookup_".$newId."` (
										`fk_field` int NOT NULL,
										`fk_entry` int NOT NULL,
										`value` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
										FULLTEXT KEY `value` (`value`)
										) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
				if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryEntry2lookup,true));
				$this->_DB->query($queryEntry2lookup);

				$queryCollectionFields = "CREATE TABLE `".DB_PREFIX."_collection_fields_".$newId."` (
										 `fk_field_id` int NOT NULL,
										 `sort` int NOT NULL,
										 UNIQUE KEY `fk_field_id` (`fk_field_id`),
										 KEY `sort` (`sort`)
										) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
				if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryCollectionFields,true));
				$this->_DB->query($queryCollectionFields);

				$queryCollectionEntry = "CREATE TABLE `".DB_PREFIX."_collection_entry_".$newId."` (
										 `id` int NOT NULL AUTO_INCREMENT,
										 `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
										 `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
										 `modificationuser` int NOT NULL,
										 `owner` int NOT NULL,
										 `group` int NOT NULL,
										 `rights` char(9) COLLATE utf8mb4_bin NOT NULL,
										 PRIMARY KEY (`id`)
										) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
				if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryCollectionEntry,true));
				$this->_DB->query($queryCollectionEntry);

				$this->_updateToolRelation($newId,$data['tool']);

				$this->_DB->commit();
				$ret = true;
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
				$this->_DB->rollback();
			}
		}

		return $ret;
	}

	/**
	 * Load the information from collection table for given $id
	 *
	 * @param string $id Number
	 * @return array
	 */
	public function getEditData($id) {
		$ret = array();

		if (Summoner::validate($id, 'digit')) {
			$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`, `c`.`created`,
					`c`.`owner`, `c`.`group`, `c`.`rights`, `c`.`defaultSearchField`,
					`u`.`name` AS username, `g`.`name` AS groupname
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString("read", "c")."
					AND `c`.`id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {
					$ret = $query->fetch_assoc();
					$ret['rights'] = Summoner::prepareRightsArray($ret['rights']);
					$ret['tool'] = $this->getAvailableTools($id);
				}
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Update collection with given data identified by given id
	 * See method for the fields
	 *
	 * @param array $data
	 * @return bool
	 */
	public function updateCollection($data) {
		$ret = false;

		if(DEBUG) error_log("[DEBUG] ".__METHOD__."  data: ".var_export($data,true));

		if(!empty($data['name']) === true
			&& $this->_validUpdateCollectionName($data['name'], $data['id']) === true
			&& Summoner::validate($data['id'], 'digit')
		) {
			$queryStr = "UPDATE `".DB_PREFIX."_collection`
						SET `name` = '".$this->_DB->real_escape_string($data['name'])."',
							`description` = '".$this->_DB->real_escape_string($data['description'])."',
							`owner` = '".$this->_DB->real_escape_string($data['owner'])."',
							`group` = '".$this->_DB->real_escape_string($data['group'])."',
							`rights` = '".$this->_DB->real_escape_string($data['rights'])."',
							`defaultSearchField` = '".$this->_DB->real_escape_string($data['defaultSearchField'])."'
						WHERE `id` = '".$this->_DB->real_escape_string($data['id'])."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$this->_DB->query($queryStr);
				$this->_updateToolRelation($data['id'],$data['tool']);
				$ret = true;
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
			}

			// update the search field if it is a field from the collection entry table
			// and add the index. The lookup table has already a fulltext index on value
			$queryCheck = "SHOW COLUMNS FROM `".DB_PREFIX."_collection_entry_".$data['id']."` 
								LIKE '".$this->_DB->real_escape_string($data['defaultSearchField'])."'";
			$queryStr = "CREATE FULLTEXT INDEX ".$this->_DB->real_escape_string($data['defaultSearchField'])."
						ON `".DB_PREFIX."_collection_entry_".$data['id']."`
							(`".$this->_DB->real_escape_string($data['defaultSearchField'])."`)";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryCheck,true));
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryCheck);
				if($query !== false && $query->num_rows > 0) {
					$this->_DB->query($queryStr);
					// altering or adding an index while data exists
					// ignores the collation (?)
					// optimize does a recreation and the column collation
					// is considered
					$this->_DB->query("OPTIMIZE TABLE `".DB_PREFIX."_collection_entry_".$data['id']."`");
				}
			} catch (Exception $e) {
				if($e->getCode() == "1061") {
					// duplicate key message if the index is already there.
					error_log("[NOTICE] ".__METHOD__."  mysql query: ".$e->getMessage());
				}
				else {
					error_log("[ERROR] ".__METHOD__."  mysql query: ".$e->getMessage());
				}
			}
		}

		return $ret;
	}

	/**
	 * Delete collection identified by given id
	 *
	 * @param string $id  Number
	 * @return bool
	 */
	public function deleteCollection($id) {
		$ret = false;

		// @todo Implement list deletion
		// what to do with the entries?
		exit('No implemented yet.');

		if(!empty($id) && Summoner::validate($id, 'digit')) {
			$queryStr = "DELETE FROM `".DB_PREFIX."_collection`
							WHERE `id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			$query = $this->_DB->query($queryStr);
			if($query !== false) {

				var_dump("Implement list deletion");
				$ret = true;
			}
		}

		return $ret;
	}

	/**
	 * Load the tools configured to the given collection
	 *
	 * @param string $id Number
	 * @return array
	 */
	public function getAvailableTools($id) {
		$ret = array();

		$queryStr = "SELECT `t`.`id`, `t`.`name`, `t`.`description`, `t`.`action`, `t`.`target`
					FROM `".DB_PREFIX."_tool2collection` AS t2c
					LEFT JOIN `".DB_PREFIX."_tool` AS t ON t2c.fk_collection_id = t.id
					WHERE t2c.fk_collection_id = '".$this->_DB->real_escape_string($id)."'";
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
			error_log("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
		}

		return  $ret;
	}

	/**
	 * Check if given name can be used as a new one
	 *
	 * @param string $name
	 * @return bool
	 */
	private function _validNewCollectionName($name) {
		$ret = false;
		if (Summoner::validate($name, 'nospace')) {
			$queryStr = "SELECT `id` FROM `".DB_PREFIX."_collection`
								WHERE `name` = '".$this->_DB->real_escape_string($name)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows < 1) {
					$ret = true;
				}
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Check if given name can be used as a new name for id
	 *
	 * @param string $name
	 * @param string $id Number
	 * @return bool
	 */
	private function _validUpdateCollectionName($name, $id) {
		$ret = false;

		if (Summoner::validate($name, 'nospace')
			&& Summoner::validate($id,'digit')
		) {
			$queryStr = "SELECT `id` FROM `".DB_PREFIX."_collection`
								WHERE `name` = '".$this->_DB->real_escape_string($name)."'
								AND `id` != '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows < 1) {
					$ret = true;
				}
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Update the given colletion ($id) with the given tool array
	 *
	 * @param string $id Number
	 * @param array $tool
	 * @return bool
	 */
	private function _updateToolRelation($id,$tool) {
		$ret = false;

		$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
		$queryStr = "DELETE FROM `".DB_PREFIX."_tool2collection`
								WHERE `fk_collection_id` = '".$this->_DB->real_escape_string($id)."'";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$this->_DB->query($queryStr);

			if(!empty($tool)) {
				foreach($tool as $k=>$v) {
					if(!empty($v)) {
						$insertQueryStr = "INSERT IGNORE INTO `".DB_PREFIX."_tool2collection`
											SET `fk_tool_id` = '".$this->_DB->real_escape_string($v)."',
												`fk_collection_id` = '".$this->_DB->real_escape_string($id)."'";
						if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($insertQueryStr,true));
						$this->_DB->query($insertQueryStr);
					}
				}
			}
			$this->_DB->commit();
			$ret = true;
		} catch (Exception $e) {
			$this->_DB->rollback();
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}
}
