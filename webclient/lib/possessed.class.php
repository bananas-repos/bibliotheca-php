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
 * Class Possessed
 * User management
 * There is no group management yet. It uses the defined groups
 * from the initial setup. Don't change em, could break something.
 *
 * passwords used here: password_hash("somePassword", PASSWORD_DEFAULT);
 *
 */
class Possessed {
    /**
     * the global DB object
     *
     * @var object
     */
    private $_DB;

    public function __construct($db) {
        $this->_DB = $db;
    }

    /**
     * Retrieve the groups for selection
     *
     * @return array
     */
    public function getGroups() {
        $ret = array();

        $queryStr = "SELECT `id`, `name`, `description` FROM `".DB_PREFIX."_group` ORDER BY `name`";
        $query = $this->_DB->query($queryStr);
        if($query !== false && $query->num_rows > 0) {
            while(($result = $query->fetch_assoc()) != false) {
                $ret[$result['id']] = $result;
            }
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
                        FROM `".DB_PREFIX."_user`";
        $query = $this->_DB->query($queryStr);
        if($query !== false && $query->num_rows > 0) {
            while(($result = $query->fetch_assoc()) != false) {
                $ret[$result['id']] = $result;
                $ret[$result['id']]['groups'] = $this->_loadUserGroupInfo($result['id']);
            }
        }

        return $ret;
    }

    /**
     * Create or update a user and set the required user releations
     *
     * @param $username string
     * @param $login string
     * @param $password string
     * @param $group string Number
     * @param bool $active
     * @return bool
     */
    public function createUser($username, $login, $password, $group, $active=false) {
        $ret = false;

        if(!empty($login) === true
            && $this->_validNewLogin($login) == true
            && $this->_validUsergroup($group) == true
            &&(!empty($password))
        ) {
            if ($active === true) {
                $active = "1";
            } else {
                $active = "0";
            }
            $this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

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
            $query = $this->_DB->query($queryStr);

            if ($query !== false) {
                $_userid = $this->_DB->insert_id;
                $this->_DB->query("UPDATE `".DB_PREFIX . "_user`
                                    SET `owner` = '".$this->_DB->real_escape_string($_userid)."'
                                    WHERE `id` = '".$this->_DB->real_escape_string($_userid)."'");
                $_setGroupRelation = $this->_setGroupReleation($_userid,$group);
                if($_setGroupRelation !== false) {
                    $this->_DB->commit();
                    $ret = true;
                }
                $this->_DB->rollback();
                error_log('ERROR Failed to insert user releation: '.var_export($queryStr, true));
            } else {
                $this->_DB->rollback();
                error_log('ERROR Failed to insert user: '.var_export($queryStr, true));
            }
        }

        return $ret;
    }

    /**
     * Update given user id with given data
     *
     * @param $id
     * @param $username
     * @param $login
     * @param $password
     * @param $group
     * @param bool $active
     * @param bool $refreshApiToken
     * @return bool
     */
    public function updateUser($id, $username, $login, $password, $group, $active=false, $refreshApiToken=false) {
        $ret = false;

        if(!empty($login) === true
            && $this->_validUpdateLogin($login,$id) == true
            && $this->_validUsergroup($group) == true
            && !empty($id)
        ) {
            if ($active === true) {
                $active = "1";
            } else {
                $active = "0";
            }

            $_password = password_hash($password, PASSWORD_DEFAULT);

            $this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

            $queryStr = "UPDATE `".DB_PREFIX . "_user`
                        SET `name` = '".$this->_DB->real_escape_string($username)."',
                            `login` = '".$this->_DB->real_escape_string($login)."',
                            `active` = '".$this->_DB->real_escape_string($active)."',
                            `baseGroupId` = '".$this->_DB->real_escape_string($group)."'";
            if(Summoner::validate($password,'text')) {
                $queryStr .= ", `password` = '".$this->_DB->real_escape_string($_password)."'";
            }
            if($refreshApiToken === true) {
                $queryStr .= ", `apiToken` = '".md5(base64_encode(openssl_random_pseudo_bytes(30)))."'";
                $queryStr .= ", `apiTokenValidDate` = CURRENT_TIMESTAMP() + INTERVAL 1 DAY";
            }
            $queryStr .= " WHERE `id` = '".$this->_DB->real_escape_string($id)."'
                        AND `protected` = '0'";
            $query = $this->_DB->query($queryStr);

            if ($query !== false) {
                $_setGroupRelation = $this->_setGroupReleation($id,$group, true);
                if($_setGroupRelation !== false) {
                    $this->_DB->commit();
                    $ret = true;
                }
                $this->_DB->rollback();
                error_log('ERROR Failed to insert user releation: '.var_export($queryStr, true));
            } else {
                $this->_DB->rollback();
                error_log('ERROR Failed to insert user: '.var_export($queryStr, true));
            }
        }

        return $ret;
    }

    /**
     * Load the userinformation and groups for given id
     *
     * @param $userId string Number
     * @return array
     */
    public function getEditData($userId) {
        $ret = array();

        if(Summoner::validate($userId,'digit')) {
            $queryStr = "SELECT `id`, `login`, `name`, `active`, `baseGroupId`, `created`,`apiToken`,`apiTokenValidDate`
                        FROM `".DB_PREFIX."_user`
                        WHERE `protected` = '0'
                        AND `id` = '".$this->_DB->real_escape_string($userId)."'";
            $query = $this->_DB->query($queryStr);
            if($query !== false && $query->num_rows == 1) {
                $ret = $query->fetch_assoc();
                $ret['groups'] = $this->_loadUserGroupInfo($userId);
            }

        }

        return $ret;
    }

    /**
     * Delete user by given user id
     *
     * @param $id string Number
     * @return bool
     */
    public function deleteUser($id) {
        $ret = false;

        if(!empty($id)) {
            $this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

            $d1 = $this->_DB->query("DELETE FROM `".DB_PREFIX."_user` 
                WHERE `id` = '".$this->_DB->real_escape_string($id)."'
                AND `protected` = '0'");
            $d2 = $this->_DB->query("DELETE FROM `".DB_PREFIX."_user2group` WHERE `fk_user_id` = '".$this->_DB->real_escape_string($id)."'");
            $d3 = $this->_DB->query("DELETE FROM `".DB_PREFIX."_userSession` WHERE `fk_user_id` = '".$this->_DB->real_escape_string($id)."'");

            if($d1 !== false && $d2 !== false && $d3 !== false) {
                $this->_DB->commit();
                $ret = true;
            }
            else {
                $this->_DB->rollback();
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
            $query = $this->_DB->query($queryStr);
            if ($query !== false && $query->num_rows < 1) {
                $ret = true;
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
        if (Summoner::validate($login, 'nospace')) {
            $queryStr = "SELECT `id` FROM `" . DB_PREFIX . "_user`
                                WHERE `login` = '".$this->_DB->real_escape_string($login)."'
                                AND `id` != '".$this->_DB->real_escape_string($id)."'";
            $query = $this->_DB->query($queryStr);
            if ($query !== false && $query->num_rows < 1) {
                $ret = true;
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
            $query = $this->_DB->query($queryStr);
            if($query !== false && $query->num_rows > 0) {
                $ret = true;
            }
        }

        return $ret;
    }

    /**
     * Set user to group releation in database.
     * clean will delete all existing ones for given userid first.
     *
     * @param string $userid Number
     * @param string $groupid Number
     * @param bool $clean
     * @return bool
     */
    private function _setGroupReleation($userid, $groupid, $clean=false) {
        $ret = false;

        if(Summoner::validate($userid,'digit')
            && Summoner::validate($groupid,'digit')) {

            if($clean === true) {
                $this->_DB->query("DELETE FROM `".DB_PREFIX."_user2group`
                    WHERE `fk_user_id` = '".$this->_DB->real_escape_string($userid)."'");
            }

            $queryStr = "INSERT IGNORE INTO `".DB_PREFIX."_user2group`
                            SET `fk_user_id` = '".$this->_DB->real_escape_string($userid)."',
                                `fk_group_id` = '".$this->_DB->real_escape_string($groupid)."'";
            $ret = $this->_DB->query($queryStr);
        }

        return $ret;
    }

    /**
     * Load all the groups the user is in and the information of them
     * 
     * @param $userId string Number
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
        $query = $this->_DB->query($queryStr);
        if($query !== false && $query->num_rows > 0) {
            while(($result = $query->fetch_assoc()) != false) {
                $ret[$result['groupId']] = array(
                    'groupName' => $result['groupName'],
                    'groupDescription' => $result['groupDescription']
                );
            }
        }

        return $ret;
    }
}
