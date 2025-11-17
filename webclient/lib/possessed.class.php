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
 *  along with this program. If not, see http://www.gnu.org/licenses/gpl-3.0
 */

/**
 * Class Possessed
 * User and group management
 * Some groups are protected and should not be removed.
 *
 * passwords used here: password_hash("somePassword", PASSWORD_DEFAULT);
 */
class Possessed {
	/**
	 * the global DB object
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
	 * Possessed constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct(mysqli $databaseConnectionObject, Doomguy $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Retrieve the groups for selection
	 *
	 * @return array
	 */
	public function getGroups(): array {
		$ret = array();

		$queryStr = "SELECT `id`, `name`, `description`, `created`, `protected`
		 				FROM `".DB_PREFIX."_group`
		 				WHERE ".$this->_User->getSQLRightsString("delete")." 
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
	 * Fetch all available users for management
	 *
	 * @return array
	 */
	public function getUsers(): array {
		$ret = array();

		$queryStr = "SELECT `id`, `login`, `name`, `active`, `baseGroupId`, `protected`, `created`
						FROM `".DB_PREFIX."_user`
						WHERE ".$this->_User->getSQLRightsString("delete")."";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
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
			Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	public function createUser(string $username, string $login, string $password, string $group, array $groups, bool $active=false): bool {
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
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

				$query = $this->_DB->query($queryStr);

				if ($query !== false) {
					$_userid = $this->_DB->insert_id;
					$queryStrOwner = "UPDATE `".DB_PREFIX . "_user`
										SET `owner` = '".$this->_DB->real_escape_string($_userid)."'
										WHERE `id` = '".$this->_DB->real_escape_string($_userid)."'";
					if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStrOwner));
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
				Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	public function updateUser(string $id, string $username, string $login, string $password, string $group,
							   array $groups, bool $active=false, bool $refreshApiToken=false): bool {
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
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
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
				Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	public function getEditData(string $userId): array {
		$ret = array();

		if(Summoner::validate($userId,'digit')) {
			$queryStr = "SELECT `id`, `login`, `name`, `active`, `baseGroupId`, 
						`created`,`apiToken`,`apiTokenValidDate`, `protected`
						FROM `".DB_PREFIX."_user`
						WHERE ".$this->_User->getSQLRightsString("delete")."
						AND `id` = '".$this->_DB->real_escape_string($userId)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows == 1) {
					$ret = $query->fetch_assoc();
					$ret['groups'] = $this->_loadUserGroupInfo($userId);
				}
			}
			catch (Exception $e) {
				Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	public function deleteUser(string $id): bool {
		$ret = false;

		if(Summoner::validate($id,'digit')) {

            if(!$this->_checkIfUserIsInUse($id)) {
                try {
                    $this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);
                    $d1 = $this->_DB->query("DELETE FROM `".DB_PREFIX."_user` 
                        WHERE `id` = '".$this->_DB->real_escape_string($id)."'
                        AND ".$this->_User->getSQLRightsString("delete")."
                        AND `protected` = '0'");
                    $d2 = $this->_DB->query("DELETE FROM `".DB_PREFIX."_user2group` WHERE `fk_user_id` = '".$this->_DB->real_escape_string($id)."'");
                    $d3 = $this->_DB->query("DELETE FROM `".DB_PREFIX."_userSession` WHERE `fk_user_id` = '".$this->_DB->real_escape_string($id)."'");

                    if ($d1 === false || $d2 === false || $d3 === false) {
                        throw new Exception('Failed to delete the user');
                    }
                    $this->_DB->commit();
                    $ret = true;
                } catch (Exception $e) {
                    $this->_DB->rollback();
                    Summoner::sysLog("[ERROR] " . __METHOD__ . " mysql catch: " . $e->getMessage());
                }
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
	public function createGroup(string $name, string $description): bool {
		$ret = false;

		if($this->_validNewGroup($name)) {
			$queryStr = "INSERT INTO `".DB_PREFIX."_group` SET 
						`name` = '".$this->_DB->real_escape_string($name)."',
						`description` = '".$this->_DB->real_escape_string($description)."',
						`modificationuser` = '".$this->_DB->real_escape_string($this->_User->param('id'))."',
						`owner` = '".$this->_DB->real_escape_string($this->_User->param('id'))."',
						`group` = '".ADMIN_GROUP_ID."',
						`rights` = 'rwxr--r--'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$this->_DB->query($queryStr);
				$ret = true;
			}
			catch (Exception $e) {
				Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	public function updateGroup(string $id, string $name, string $description): bool {
		$ret = false;

		if($this->_validUpdateGroup($name, $id)) {
			$queryStr = "UPDATE `".DB_PREFIX."_group` SET 
						`name` = '".$this->_DB->real_escape_string($name)."',
						`description` = '".$this->_DB->real_escape_string($description)."',
						`modificationuser` = '".$this->_DB->real_escape_string($this->_User->param('id'))."'
						WHERE `id` = '".$this->_DB->real_escape_string($id)."'
							AND ".$this->_User->getSQLRightsString("delete")."";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$this->_DB->query($queryStr);
				$ret = true;
			}
			catch (Exception $e) {
				Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	public function deleteGroup(string $id): bool {
		$ret = false;

		if(Summoner::validate($id,'digit')) {
            if(!$this->_checkIfGroupIsInUse($id)) {
                $queryStr = "DELETE FROM `" . DB_PREFIX . "_group`
                            WHERE " . $this->_User->getSQLRightsString("delete") . "
                                AND `protected` = '0'
                                AND `id` = '" . $this->_DB->real_escape_string($id) . "'";
                if (QUERY_DEBUG) Summoner::sysLog("[QUERY] " . __METHOD__ . " query: " . Summoner::cleanForLog($queryStr));
                try {
                    $this->_DB->query($queryStr);
                    $ret = true;
                } catch (Exception $e) {
                    Summoner::sysLog("[ERROR] " . __METHOD__ . " mysql catch: " . $e->getMessage());
                }
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
	public function getEditGroupData(string $id): array {
		$ret = array();

		if(Summoner::validate($id,'digit')) {
			$queryStr = "SELECT `id`, `name`, `description`, `created`, `protected`
							FROM `".DB_PREFIX."_group`
							WHERE ".$this->_User->getSQLRightsString("delete")." 
							AND `id` = '".$this->_DB->real_escape_string($id)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {
					$ret = $query->fetch_assoc();
				}
			}
			catch (Exception $e) {
				Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	private function _validNewGroup(string $name): bool {
		$ret = false;

		if (Summoner::validate($name, 'nospace')) {
			$queryStr = "SELECT `id` FROM `".DB_PREFIX."_group`
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
	 * Check if given group name can be used as an update to given group id
	 *
	 * @param string $name
	 * @param string $id Number
	 * @return bool
	 */
	private function _validUpdateGroup(string $name, string $id): bool {
		$ret = false;

		if (Summoner::validate($name, 'nospace') && Summoner::validate($id,"digit")) {
			$queryStr = "SELECT `id` FROM `" . DB_PREFIX . "_group`
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
	 * Check if given login can be used as a new one
	 *
	 * @param string $login
	 * @return bool
	 */
	private function _validNewLogin(string $login): bool {
		$ret = false;

		if (Summoner::validate($login, 'nospace')) {
			$queryStr = "SELECT `id` FROM `".DB_PREFIX."_user`
								WHERE `login` = '".$this->_DB->real_escape_string($login)."'";
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
	 * Check if given $login can be used as a new login for given id
	 *
	 * @param string $login
	 * @param string $id Number
	 * @return bool
	 */
	private function _validUpdateLogin(string $login, string $id): bool {
		$ret = false;

		if (Summoner::validate($login, 'nospace') && Summoner::validate($id,"digit")) {
			$queryStr = "SELECT `id` FROM `" . DB_PREFIX . "_user`
								WHERE `login` = '".$this->_DB->real_escape_string($login)."'
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
	 * check if given group id is present
	 *
	 * @param string $groupId Number
	 * @return bool
	 */
	private function _validUsergroup(string $groupId): bool {
		$ret = false;

		if(Summoner::validate($groupId,'digit')) {
			$queryStr = "SELECT `id` FROM `".DB_PREFIX."_group`
						WHERE `id` = '".$this->_DB->real_escape_string($groupId)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {
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
	 * Set user to group relation in database.
	 * clean will delete all existing ones for given userid first.
	 *
	 * @param string $userid Number
	 * @param array $group Array with group ids
	 * @param bool $clean
	 * @return bool
	 */
	private function _setGroupReleation(string $userid, array $group, bool $clean=false): bool {
		$ret = false;

		if(Summoner::validate($userid,'digit')
			&& is_array($group) && !empty($group)) {

			try {
				if($clean === true) {
					$queryStrDelete = "DELETE FROM `".DB_PREFIX."_user2group`
						WHERE `fk_user_id` = '".$this->_DB->real_escape_string($userid)."'";
					if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStrDelete));
					$this->_DB->query($queryStrDelete);
				}

				$queryStr = "INSERT IGNORE INTO `".DB_PREFIX."_user2group` (`fk_user_id`, `fk_group_id`) VALUES ";
				foreach($group as $g) {
					$queryStr .= "('".$this->_DB->real_escape_string($userid)."','".$this->_DB->real_escape_string($g)."'),";
				}
				$queryStr = trim($queryStr, ",");
				if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
				$ret = $this->_DB->query($queryStr);
			}
			catch (Exception $e) {
				Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Load all the groups the user is in and the information of them
	 *
	 * @param string $userId Number
	 * @return array
	 * @todo Not really needed. Can be done in one query. See Doomguy class
	 *
	 */
	private function _loadUserGroupInfo(string $userId): array{
		$ret = array();

		$queryStr = "SELECT g.name AS groupName,
					g.description AS groupDescription,
					g.id AS groupId
					FROM `".DB_PREFIX."_user2group` AS u2g,
						`".DB_PREFIX."_group` AS g
					WHERE u2g.fk_user_id = '".$this->_DB->real_escape_string($userId)."'
					AND u2g.fk_group_id = g.id";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
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
			Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

    /**
     * Check if given userId is used and should not be deleted.
     *
     * @param string $userId
     * @return bool
     */
    private function _checkIfUserIsInUse(string $userId): bool {
        $ret = false;

        $queryStr = "SELECT `id` FROM `".DB_PREFIX."_collection` 
                    WHERE `owner` = '".$this->_DB->real_escape_string($userId)."'";
        if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
        try {
            $query = $this->_DB->query($queryStr);
            if($query !== false && $query->num_rows > 0) {
                $ret = true;
            }
        }
        catch (Exception $e) {
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
        }

        if(!$ret) {
            $queryStr = "SELECT `id` FROM `".DB_PREFIX."_user2group` 
                    WHERE `fk_user_id` = '".$this->_DB->real_escape_string($userId)."'";
            if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
            try {
                $query = $this->_DB->query($queryStr);
                if($query !== false && $query->num_rows > 0) {
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
     * Check if given groupId is used and should not be deleted.
     *
     * @param string $groupId
     * @return bool
     */
    private function _checkIfGroupIsInUse(string $groupId): bool {
        $ret = false;

        $queryStr = "SELECT `id` FROM `".DB_PREFIX."_collection` 
                    WHERE `group` = '".$this->_DB->real_escape_string($groupId)."'";
        if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
        try {
            $query = $this->_DB->query($queryStr);
            if($query !== false && $query->num_rows > 0) {
                $ret = true;
            }
        }
        catch (Exception $e) {
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
        }

        if(!$ret) {
            $queryStr = "SELECT `fk_group_id` FROM `".DB_PREFIX."_user2group` 
                    WHERE `fk_group_id` = '".$this->_DB->real_escape_string($groupId)."'";
            if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
            try {
                $query = $this->_DB->query($queryStr);
                if($query !== false && $query->num_rows > 0) {
                    $ret = true;
                }
            }
            catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
            }
        }

        return $ret;
    }
}
