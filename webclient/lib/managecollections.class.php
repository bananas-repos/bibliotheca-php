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
 * Class ManageCollections to manage collections
 */
class ManageCollections {
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
	 * ManageCollections constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct(mysqli $databaseConnectionObject, Doomguy $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Get all available collections for display based on current user
	 *
	 * @return array
	 */
	public function getCollections(): array{
		$ret = array();

		$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`, `c`.`created`,
					`c`.`owner`, `c`.`group`, `c`.`rights`, 
					`u`.`name` AS username, `g`.`name` AS groupname
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString("write", "c")."
					ORDER BY `c`.`name`";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$query = $this->_DB->query($queryStr);
			if ($query !== false && $query->num_rows > 0) {
				while (($result = $query->fetch_assoc()) != false) {
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
	 * Retrieve the groups for selection based on user rights
	 *
	 * @return array
	 */
	public function getGroupsForSelection(): array {
		$ret = array();

		$queryStr = "SELECT `id`, `name`, `description` 
					FROM `".DB_PREFIX."_group` 
					WHERE ".$this->_User->getSQLRightsString()."
					ORDER BY `name`";
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
	 * Fetch all available users for selection based on current user rights
	 *
	 * @return array
	 */
	public function getUsersForSelection(): array {
		$ret = array();

		$queryStr = "SELECT `id`, `name`, `login`
						FROM `".DB_PREFIX."_user`
						WHERE ".$this->_User->getSQLRightsString()."";
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
	 * Fetch all available tools based on current user rights
	 *
	 * @return array
	 */
	public function getToolsForSelection(): array {
		$ret = array();

		$queryStr = "SELECT `id`, `name`, `description`
						FROM `".DB_PREFIX."_tool`
						WHERE ".$this->_User->getSQLRightsString()."";
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
	 * Create new collection entry in collection table. Provide valid data
	 * only $name will be checked again
	 *
	 * @param array $data
	 * @return bool
	 */
	public function createCollection(array $data): bool {
		$ret = false;

		if(!empty($data['name']) === true
			&& $this->_validNewCollectionName($data['name']) === true
		) {

			try {
				$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

				$queryStr = "INSERT INTO `".DB_PREFIX."_collection`
							SET `name` = '".$this->_DB->real_escape_string($data['name'])."',
								`description` = '".$this->_DB->real_escape_string($data['description'])."',
								`owner` = '".$this->_DB->real_escape_string($data['owner'])."',
								`group` = '".$this->_DB->real_escape_string($data['group'])."',
								`rights` = '".$this->_DB->real_escape_string($data['rights'])."',
								`defaultSearchField` = '".$this->_DB->real_escape_string($data['defaultSearchField'])."',
								`defaultSortField` = '".$this->_DB->real_escape_string($data['defaultSortField'])."',
								`defaultSortOrder` = '".$this->_DB->real_escape_string($data['defaultSortOrder'])."',
								`advancedSearchTableFields` = '".$this->_DB->real_escape_string($data['advancedSearchTableFields'])."'";
				if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
				$this->_DB->query($queryStr);
				$newId = $this->_DB->insert_id;

				$this->_updateToolRelation($newId,$data['tool']);
				$this->_DB->commit();


				// mysql implicit commit with create table
				// rollback does not really solve if there is an error
				$queryEntry2lookup = "CREATE TABLE `".DB_PREFIX."_collection_entry2lookup_".$newId."` (
										`fk_field` int NOT NULL,
										`fk_entry` int NOT NULL,
										`value` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
										KEY `fk_entry` (`fk_entry`),
 										KEY `fk_field` (`fk_field`),
										FULLTEXT KEY `value` (`value`)
										) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
				if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryEntry2lookup));
				$this->_DB->query($queryEntry2lookup);

				$queryCollectionFields = "CREATE TABLE `".DB_PREFIX."_collection_fields_".$newId."` (
										 `fk_field_id` int NOT NULL,
										 `sort` int NOT NULL,
										 UNIQUE KEY `fk_field_id` (`fk_field_id`),
										 KEY `sort` (`sort`)
										) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
				if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryCollectionFields));
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
				if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryCollectionEntry));
				$this->_DB->query($queryCollectionEntry);

				$ret = true;
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	public function getEditData(string $id): array {
		$ret = array();

		if (Summoner::validate($id, 'digit')) {
			$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`, `c`.`created`,
					`c`.`owner`, `c`.`group`, `c`.`rights`, `c`.`defaultSearchField`,
					`c`.`defaultSortField`, `c`.`advancedSearchTableFields`,
					`c`.`defaultSortOrder`,
					`u`.`name` AS username, `g`.`name` AS groupname
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString("write", "c")."
					AND `c`.`id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {
					$ret = $query->fetch_assoc();
					$ret['rights'] = Summoner::prepareRightsArray($ret['rights']);
					$ret['tool'] = $this->getAvailableTools($id);
					$ret['advancedSearchTableFields'] = $this->_loadAdvancedSearchTableFields($ret['advancedSearchTableFields']);
				}
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	public function updateCollection(array $data): bool {
		$ret = false;

		if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__."  data: ".Summoner::cleanForLog($data));

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
							`defaultSearchField` = '".$this->_DB->real_escape_string($data['defaultSearchField'])."',
							`defaultSortField` = '".$this->_DB->real_escape_string($data['defaultSortField'])."',
							`defaultSortOrder` = '".$this->_DB->real_escape_string($data['defaultSortOrder'])."',
							`advancedSearchTableFields` = '".$this->_DB->real_escape_string($data['advancedSearchTableFields'])."'
						WHERE `id` = '".$this->_DB->real_escape_string($data['id'])."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$this->_DB->query($queryStr);
				$this->_updateToolRelation($data['id'],$data['tool']);
				if($data['doRightsForEntries'] === true) {
					$this->_updateEntryRights($data['id'], $data['owner'], $data['group'], $data['rights']);
				}
				$ret = true;
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
			}

			// update the search field if it is a field from the collection entry table
			// and add the index. The lookup table has already a fulltext index on value
			$queryCheck = "SHOW COLUMNS FROM `".DB_PREFIX."_collection_entry_".$data['id']."` 
								LIKE '".$this->_DB->real_escape_string($data['defaultSearchField'])."'";
			$queryStr = "CREATE FULLTEXT INDEX ".$this->_DB->real_escape_string($data['defaultSearchField'])."
						ON `".DB_PREFIX."_collection_entry_".$data['id']."`
							(`".$this->_DB->real_escape_string($data['defaultSearchField'])."`)";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryCheck));
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
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
                    Summoner::sysLog("[NOTICE] ".__METHOD__."  mysql query: ".$e->getMessage());
				}
				else {
                    Summoner::sysLog("[ERROR] ".__METHOD__."  mysql query: ".$e->getMessage());
				}
			}
		}

		return $ret;
	}

	/**
	 * Delete collection identified by given id
	 * This removes everything and drops tables!
	 *
	 * @param string $id Number
	 * @return bool
	 */
	public function deleteCollection(string $id): bool {
		$ret = false;

		if(!empty($id) && Summoner::validate($id, 'digit')) {
			$queryStr = "DELETE FROM `".DB_PREFIX."_collection`
							WHERE `id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));

			$queryStrTool = "DELETE FROM `".DB_PREFIX."_tool2collection`
							WHERE `fk_collection_id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStrTool));

			$queryStre2l = "DROP TABLE `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($id)."`";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStre2l));

			$queryStrEntry = "DROP TABLE `".DB_PREFIX."_collection_entry_".$this->_DB->real_escape_string($id)."`";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStrEntry));

			$queryStrFields = "DROP TABLE `".DB_PREFIX."_collection_fields_".$this->_DB->real_escape_string($id)."`";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStrFields));


			// mysql implicit commit with drop command
			// transaction does not really help here.
			// https://dev.mysql.com/doc/refman/8.0/en/implicit-commit.html
			try {
				$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

				$this->_DB->query($queryStr);
				$this->_DB->query($queryStrTool);
				$this->_DB->commit();

				$this->_DB->query($queryStre2l);
				$this->_DB->query($queryStrEntry);
				$this->_DB->query($queryStrFields);

				Summoner::recursive_remove_directory(PATH_STORAGE.'/'.$id);

				$ret = true;
			}
			catch (Exception $e) {
				$this->_DB->rollback();
                Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
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
	public function getAvailableTools(string $id): array {
		$ret = array();

		$queryStr = "SELECT `t`.`id`, `t`.`name`, `t`.`description`, `t`.`action`, `t`.`target`
					FROM `".DB_PREFIX."_tool2collection` AS t2c
					LEFT JOIN `".DB_PREFIX."_tool` AS t ON t.id = t2c.fk_tool_id
					WHERE t2c.fk_collection_id = '".$this->_DB->real_escape_string($id)."'";
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
            Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
		}

		return  $ret;
	}

	/**
	 * Check if given name can be used as a new one
	 *
	 * @param string $name
	 * @return bool
	 */
	private function _validNewCollectionName(string $name): bool {
		$ret = false;
		if (Summoner::validate($name, 'nospace')) {
			$queryStr = "SELECT `id` FROM `".DB_PREFIX."_collection`
								WHERE `name` = '".$this->_DB->real_escape_string($name)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows < 1) {
					$ret = true;
				}
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	private function _validUpdateCollectionName(string $name, string $id): bool {
		$ret = false;

		if (Summoner::validate($name, 'nospace')
			&& Summoner::validate($id,'digit')
		) {
			$queryStr = "SELECT `id` FROM `".DB_PREFIX."_collection`
								WHERE `name` = '".$this->_DB->real_escape_string($name)."'
								AND `id` != '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows < 1) {
					$ret = true;
				}
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	private function _updateToolRelation(string $id, array $tool): bool {
		$ret = false;


		$queryStr = "DELETE FROM `".DB_PREFIX."_tool2collection`
								WHERE `fk_collection_id` = '".$this->_DB->real_escape_string($id)."'";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

			$this->_DB->query($queryStr);

			if(!empty($tool)) {
				foreach($tool as $k=>$v) {
					if(!empty($v)) {
						$insertQueryStr = "INSERT IGNORE INTO `".DB_PREFIX."_tool2collection`
											SET `fk_tool_id` = '".$this->_DB->real_escape_string($v)."',
												`fk_collection_id` = '".$this->_DB->real_escape_string($id)."'";
						if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($insertQueryStr));
						$this->_DB->query($insertQueryStr);
					}
				}
			}
			$this->_DB->commit();
			$ret = true;
		}
		catch (Exception $e) {
			$this->_DB->rollback();
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

	/**
	 * Update the rights from the group to the entries in this collection
	 *
	 * @param string $collectionId
	 * @param string $owner
	 * @param string $group
	 * @param string $rights
	 */
	private function _updateEntryRights(string $collectionId, string $owner='', string $group='', string $rights=''): void {
		if(!empty($collectionId)) {
			$queryStr = "UPDATE `".DB_PREFIX."_collection_entry_".$collectionId."` SET";

			if(Summoner::validate($owner, "digit")) {
				$queryStr .= " `owner` = '".$this->_DB->real_escape_string($owner)."',";
			}
			if(Summoner::validate($group, "digit")) {
				$queryStr .= " `group` = '".$this->_DB->real_escape_string($group)."',";
			}
			if(Summoner::validate($rights, "rights")) {
				$queryStr .= " `rights` = '".$this->_DB->real_escape_string($rights)."',";
			}
			$queryStr = trim($queryStr, ",");

			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$this->_DB->query($queryStr);
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}
	}

	/**
	 * Make a key=>value array of a comma seperated string and use the value as key
	 *
	 * @param string $data
	 * @return array
	 */
	private function _loadAdvancedSearchTableFields(string $data): array {
		$ret = array();

		$_t = explode(',',$data);
		foreach($_t as $e) {
			$ret[$e] = $e;
		}

		return $ret;
	}
}
