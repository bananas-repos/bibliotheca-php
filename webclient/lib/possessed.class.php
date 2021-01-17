<?php
/**
 * Bibliotheca
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
 * Class Possessed
 * User and group management
 * Some groups are protected and should not be removed.
 *
 * passwords used here: password_hash("somePassword", PASSWORD_DEFAULT);
 *
 */
class Possessed {
	/**
	 * the global DB object
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
	 * Possessed constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct($databaseConnectionObject, $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Retrieve the groups for selection
	 *
	 * @return array
	 */
	public function getGroups() {
		$ret = array();

		$queryStr = "SELECT `id`, `name`, `description`, `created`, `protected`
		 				FROM `".DB_PREFIX."_group`
		 				WHERE ".$this->_User->getSQLRightsString("delete")." 
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
	 * Fetch all available users for management
	 *
	 * @return array
	 */
	public function getUsers() {
		$ret = array();

		$queryStr = "SELECT `id`, `login`, `name`, `active`, `baseGroupId`, `protected`, `created`
						FROM `".DB_PREFIX."_user`
						WHERE ".$this->_User->getSQLRightsString("delete")."";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$ret[$result['id']] = $result;
					$ret[$result['id']]['groups'] = $this->_loadUserGroupInfo($result['id']);
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

	/**
	 * Create or update a user and set the required user relations
	 *
	 * @param string $username
	 * @param string $login
	 * @param string $password
	 * @param string $group Number
	 * @param array $groups
	 * @param bool $active
	 * @return bool
	 */
	public function createUser($username, $login, $password, $group, $groups, $active=false) {
		$ret = false;

		if($this->_validNewLogin($login) && $this->_validUsergroup($group)) {
			if ($active === true) {
				$active = "1";
			} else {
				$active = "0";
			}

			$_password = password_hash($password, PASSWORD_DEFAULT);

			$queryStr = "INSERT INTO `".DB_PREFIX . "_user`
						SET `name` = '".$this->_DB->real_escape_string($username)."',
							`login` = '".$this->_DB->real_escape_string($login)."',
							`password` = '".$this->_DB->real_escape_string($_password)."',
							`active` = '".$this->_DB->real_escape_string($active)."',
							`baseGroupId` = '".$this->_DB->real_escape_string($group)."',
							`rights` = 'rwxr--r--',
							`owner` = 0,
							`group` = '".$this->_DB->real_escape_string($group)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

				$query = $this->_DB->query($queryStr);

				if ($query !== false) {
					$_userid = $this->_DB->insert_id;
					$queryStrOwner = "UPDATE `".DB_PREFIX . "_user`
										SET `owner` = '".$this->_DB->real_escape_string($_userid)."'
										WHERE `id` = '".$this->_DB->real_escape_string($_userid)."'";
					if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStrOwner,true));
					$this->_DB->query($queryStrOwner);
					if(!empty($groups)) {
						$groups[] = $group;
					}
					else {
						$groups = array($group);
					}
					$_setGroupRelation = $this->_setGroupReleation($_userid,$groups);
					if($_setGroupRelation === false) {
						throw new Exception("Failed to insert user relation");
					}
				} else {
					throw new Exception("Failed to insert user");
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
	 * Update given user id with given data
	 *
	 * @param string $id Number
	 * @param string $username
	 * @param string $login
	 * @param string $password
	 * @param string $group
	 * @param array $groups
	 * @param bool $active
	 * @param bool $refreshApiToken
	 * @return bool
	 */
	public function updateUser($id, $username, $login, $password, $group, $groups, $active=false, $refreshApiToken=false) {
		$ret = false;

		if($this->_validUpdateLogin($login,$id) && $this->_validUsergroup($group)) {
			if ($active === true) {
				$active = "1";
			} else {
				$active = "0";
			}

			$queryStr = "UPDATE `".DB_PREFIX . "_user`
						SET `name` = '".$this->_DB->real_escape_string($username)."',
							`login` = '".$this->_DB->real_escape_string($login)."',
							`active` = '".$this->_DB->real_escape_string($active)."',
							`baseGroupId` = '".$this->_DB->real_escape_string($group)."'";
			if(Summoner::validate($password)) {
				$_password = password_hash($password, PASSWORD_DEFAULT);
				$queryStr .= ", `password` = '".$this->_DB->real_escape_string($_password)."'";
			}
			if($refreshApiToken === true) {
				$queryStr .= ", `apiToken` = '".md5(base64_encode(openssl_random_pseudo_bytes(30)))."'";
				$queryStr .= ", `apiTokenValidDate` = CURRENT_TIMESTAMP() + INTERVAL 1 DAY";
			}
			$queryStr .= " WHERE `id` = '".$this->_DB->real_escape_string($id)."'
						AND ".$this->_User->getSQLRightsString("delete")."";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

				$query = $this->_DB->query($queryStr);

				if ($query !== false) {
					if(!empty($groups)) {
						$groups[] = $group;
					}
					else {
						$groups = array($group);
					}
					$_setGroupRelation = $this->_setGroupReleation($id,$groups,true);
					if($_setGroupRelation === false) {
						throw new Exception('Failed to insert user relation');
					}
				} else {
					throw new Exception('Failed to insert user');
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
	 * Load the userinformation and groups for given id
	 *
	 * @param string $userId Number
	 * @return array
	 */
	public function getEditData($userId) {
		$ret = array();

		if(Summoner::validate($userId,'digit')) {
			$queryStr = "SELECT `id`, `login`, `name`, `active`, `baseGroupId`, 
						`created`,`apiToken`,`apiTokenValidDate`, `protected`
						FROM `".DB_PREFIX."_user`
						WHERE ".$this->_User->getSQLRightsString("delete")."
						AND `id` = '".$this->_DB->real_escape_string($userId)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows == 1) {
					$ret = $query->fetch_assoc();
					$ret['groups'] = $this->_loadUserGroupInfo($userId);
				}
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Delete user by given user id
	 *
	 * @param string $id Number
	 * @return bool
	 */
	public function deleteUser($id) {
		$ret = false;

		if(Summoner::validate($id,'digit')) {
			try {
				$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
				$d1 = $this->_DB->query("DELETE FROM `".DB_PREFIX."_user` 
					WHERE `id` = '".$this->_DB->real_escape_string($id)."'
					AND ".$this->_User->getSQLRightsString("delete")."
					AND `protected` = '0'");
				$d2 = $this->_DB->query("DELETE FROM `".DB_PREFIX."_user2group` WHERE `fk_user_id` = '".$this->_DB->real_escape_string($id)."'");
				$d3 = $this->_DB->query("DELETE FROM `".DB_PREFIX."_userSession` WHERE `fk_user_id` = '".$this->_DB->real_escape_string($id)."'");

				if($d1 === false || $d2 === false || $d3 === false) {
					throw new Exception('Failed to delete the user');
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
	 * Create group with given data. Validates duplicates based on name
	 *
	 * @param string $name
	 * @param string $description
	 * @return bool
	 */
	public function createGroup($name, $description) {
		$ret = false;

		if($this->_validNewGroup($name)) {
			$queryStr = "INSERT INTO `".DB_PREFIX."_group` SET 
						`name` = '".$this->_DB->real_escape_string($name)."',
						`description` = '".$this->_DB->real_escape_string($description)."',
						`modificationuser` = '".$this->_DB->real_escape_string($this->_User->param('id'))."',
						`owner` = '".$this->_DB->real_escape_string($this->_User->param('id'))."',
						`group` = '".ADMIN_GROUP_ID."',
						`rights` = 'rwxr--r--'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$this->_DB->query($queryStr);
				$ret = true;
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Update given group identified by id with given name and description
	 * Checks for duplicate
	 *
	 * @param string $id Number
	 * @param string $name
	 * @param string $description
	 * @return bool
	 */
	public function updateGroup($id, $name, $description) {
		$ret = false;

		if($this->_validUpdateGroup($name, $id)) {
			$queryStr = "UPDATE `".DB_PREFIX."_group` SET 
						`name` = '".$this->_DB->real_escape_string($name)."',
						`description` = '".$this->_DB->real_escape_string($description)."',
						`modificationuser` = '".$this->_DB->real_escape_string($this->_User->param('id'))."'
						WHERE `id` = '".$this->_DB->real_escape_string($id)."'
							AND ".$this->_User->getSQLRightsString("delete")."";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$this->_DB->query($queryStr);
				$ret = true;
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Delete given group identified by id from group table. No relation check yet.
	 *
	 * @param string $id Number
	 * @return bool
	 */
	public function deleteGroup($id) {
		$ret = false;

		if(Summoner::validate($id,'digit')) {
			$queryStr = "DELETE FROM `".DB_PREFIX."_group`
						WHERE ".$this->_User->getSQLRightsString("delete")."
							AND `protected` = '0'
							AND `id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$this->_DB->query($queryStr);
				$ret = true;
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Load groupd data from group table fo edit
	 *
	 * @param string $id Number
	 * @return array
	 */
	public function getEditGroupData($id) {
		$ret = array();

		if(Summoner::validate($id,'digit')) {
			$queryStr = "SELECT `id`, `name`, `description`, `created`, `protected`
							FROM `".DB_PREFIX."_group`
							WHERE ".$this->_User->getSQLRightsString("delete")." 
							AND `id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {
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
	 * Check if given group name can be used as a new one
	 *
	 * @param string $name
	 * @return bool
	 */
	private function _validNewGroup($name) {
		$ret = false;

		if (Summoner::validate($name, 'nospace')) {
			$queryStr = "SELECT `id` FROM `".DB_PREFIX."_group`
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
	 * Check if given group name can be used as an update to given group id
	 *
	 * @param string $name
	 * @param string $id Number
	 * @return bool
	 */
	private function _validUpdateGroup($name,$id) {
		$ret = false;

		if (Summoner::validate($name, 'nospace') && Summoner::validate($id,"digit")) {
			$queryStr = "SELECT `id` FROM `" . DB_PREFIX . "_group`
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
	 * Check if given login can be used as a new one
	 *
	 * @param string $login
	 * @return bool
	 */
	private function _validNewLogin($login) {
		$ret = false;

		if (Summoner::validate($login, 'nospace')) {
			$queryStr = "SELECT `id` FROM `".DB_PREFIX."_user`
								WHERE `login` = '".$this->_DB->real_escape_string($login)."'";
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
	 * Check if given $login can be used as a new login for given id
	 *
	 * @param string $login
	 * @param string $id Number
	 * @return bool
	 */
	private function _validUpdateLogin($login,$id) {
		$ret = false;

		if (Summoner::validate($login, 'nospace') && Summoner::validate($id,"digit")) {
			$queryStr = "SELECT `id` FROM `" . DB_PREFIX . "_user`
								WHERE `login` = '".$this->_DB->real_escape_string($login)."'
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
	 * check if given group id is present
	 *
	 * @param string $groupId Number
	 * @return bool
	 */
	private function _validUsergroup($groupId) {
		$ret = false;

		if(Summoner::validate($groupId,'digit')) {
			$queryStr = "SELECT `id` FROM `".DB_PREFIX."_group`
						WHERE `id` = '".$this->_DB->real_escape_string($groupId)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {
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
	 * Set user to group relation in database.
	 * clean will delete all existing ones for given userid first.
	 *
	 * @param string $userid Number
	 * @param array $group Array with group ids
	 * @param bool $clean
	 * @return bool
	 */
	private function _setGroupReleation($userid, $group, $clean=false) {
		$ret = false;

		if(Summoner::validate($userid,'digit')
			&& is_array($group) && !empty($group)) {

			try {
				if($clean === true) {
					$queryStrDelete = "DELETE FROM `".DB_PREFIX."_user2group`
						WHERE `fk_user_id` = '".$this->_DB->real_escape_string($userid)."'";
					if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStrDelete,true));
					$this->_DB->query($queryStrDelete);
				}

				$queryStr = "INSERT IGNORE INTO `".DB_PREFIX."_user2group` (`fk_user_id`, `fk_group_id`) VALUES ";
				foreach($group as $g) {
					$queryStr .= "('".$this->_DB->real_escape_string($userid)."','".$this->_DB->real_escape_string($g)."'),";
				}
				$queryStr = trim($queryStr, ",");
				if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
				$ret = $this->_DB->query($queryStr);
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Load all the groups the user is in and the information of them
	 *
	 * @todo Not really needed. Can be done in one query. See Doomguy class
	 *
	 * @param string $userId Number
	 * @return array
	 */
	private function _loadUserGroupInfo($userId) {
		$ret = array();

		$queryStr = "SELECT g.name AS groupName,
					g.description AS groupDescription,
					g.id AS groupId
					FROM `".DB_PREFIX."_user2group` AS u2g,
						`".DB_PREFIX."_group` AS g
					WHERE u2g.fk_user_id = '".$this->_DB->real_escape_string($userId)."'
					AND u2g.fk_group_id = g.id";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$ret[$result['groupId']] = array(
						'groupName' => $result['groupName'],
						'groupDescription' => $result['groupDescription']
					);
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}
}
