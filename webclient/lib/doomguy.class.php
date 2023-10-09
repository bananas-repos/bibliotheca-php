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
 * User object
 * access rights and information about the current logged in user
 */
class Doomguy {

	/**
	 * the global DB object
	 *
	 * @var mysqli
	 */
	private mysqli $_DB;

	/**
	 * if the user is logged in or not
	 *
	 * @var boolean
	 */
	protected bool $isSignedIn = false;

	/**
	 * the data from the current user
	 *
	 * @var array
	 */
	protected array $userData = array();

	/**
	 * the user ID from user management or default
	 *
	 * @var string|int
	 */
	protected string|int $userID = 0;

	/**
	 * the rights string defined the mysql query !
	 * the syntax is for mysql only
	 *
	 * @var array
	 */
	protected array $_rightsArray = array(
		'user' => array(
			'read' => 'r________',
			'write' => 'rw_______',
			'delete' => 'rwx______'
		),
		'group' => array(
			'read' => '___r_____',
			'write' => '___rw____',
			'delete' => '___rwx___'
		),
		'world' => array(
			'read' => '______r__',
			'write' => '______rw_',
			'delete' => '______rwx'
		)
	);

	/**
	 * Doomguy constructor.
	 *
	 * @param mysqli $db The database object
	 * @return void
	 */
	public function __construct(mysqli $db) {
		$this->_DB = $db;

		if($this->_checkSession() === true) {
			$this->isSignedIn = true;
			$this->_loadUser();
		}
		else {
			# anonymoose ;-)
			$this->userID = ANON_USER_ID;
			$this->_loadUser();
		}
	}

	/**
	 * get the value of the specified param from the user data array
	 *
	 * @param string $param
	 * @return bool|mixed
	 */
	public function param(string $param): mixed {
		$ret = false;

		$param = trim($param);

		if(!empty($param) && isset($this->userData[$param])) {
			$ret = $this->userData[$param];
		}

		return $ret;
	}

	/**
	 * Get the currently loaded user data info from $this->userData
	 *
	 * @return array
	 */
	public function getAllUserData(): array {
		return $this->userData;
	}

	/**
	 * return the isSignedIn status.
	 *
	 * @return bool
	 */
	public function isSignedIn(): bool {
		return $this->isSignedIn;
	}

	/**
	 * Log out the current loaded user
	 *
	 * @return boolean
	 */
	public function logOut (): bool {
		$ret = false;

		if($this->_checkAgainstSessionTable() === true) {
			$this->_destroySession();
			$ret = true;
		}

		return $ret;
	}

	/**
	 * check if the loaded user is in this group
	 * if the user is in ADMIN_GROUP_ID, the he is automatically "in" every group
	 *
	 * @param integer $groupID
	 * @return bool
	 */
	public function isInGroup(int $groupID): bool {
		$ret = false;

		if($this->userData['isRoot'] === true) {
			$ret = true;
		}
		elseif(in_array($groupID, array_keys($this->userData['group']))) {
			$ret = true;
		}

		return $ret;
	}

	/**
	 * Authenticate the user. Create session and db entries
	 *
	 * @param string $username
	 * @param string $password
	 * @return boolean
	 */
	public function authenticate(string $username, string $password): bool {
		$ret = false;

		if(!empty($username) && !empty($password)) {
			$do = $this->_checkAgainstUserTable($username);
			if($do === true) {
				# valid user now load the user data and compare password etc.
				$this->_loadUser();
				if(password_verify($password,$this->userData['password'])) {
					# everything ok

					# create the session info
					$tokenInfo = $this->_createToken();
					$_SESSION[SESSION_NAME]['bibliothecatoken'] = $tokenInfo['token'];

					$queryStr = "INSERT INTO `".DB_PREFIX."_userSession`
								SET `token` = '".$this->_DB->real_escape_string($tokenInfo['token'])."',
								`loginTime` = NOW(),
								`area` = '".$this->_DB->real_escape_string(SESSION_NAME)."',
								`fk_user_id` = '".$this->_DB->real_escape_string($this->userID)."',
								`salt` = '".$this->_DB->real_escape_string($tokenInfo['salt'])."'
								ON DUPLICATE KEY UPDATE
								   `token` = '".$this->_DB->real_escape_string($tokenInfo['token'])."',
								   `salt` = '".$this->_DB->real_escape_string($tokenInfo['salt'])."',
								   `loginTime` = NOW()";
					if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
					try {
						$this->_DB->query($queryStr);

						# do some actions
						$this->_loginActions();
					}
					catch (Exception $e) {
						error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
					}

					$ret = true;
				}
			}
		}

		return $ret;
	}

	/**
	 * Use the user identified by apitoken
	 *
	 * @param string $token
	 * @return void
	 */
	public function authByApiToken(string $token): void {
		if(!empty($token)) {
			$queryStr = "SELECT `id`
						FROM `".DB_PREFIX."_user`
						WHERE `apiToken` = '".$this->_DB->real_escape_string($token)."'
						AND `apiTokenValidDate` > NOW()";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows > 0) {
					$result = $query->fetch_assoc();
					$this->userID = $result['id'];
					$this->isSignedIn = true;
					$this->_loadUser();
					$this->_loginActions();
				}
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}
	}

	/**
	 * create the sql string for rights sql
	 *
	 * @param string $mode
	 * @param string $tableName
	 * @return string
	 */
	public function getSQLRightsString(string $mode = "read", string $tableName = ''): string {
		$str = '';
		$prefix = '';

		if(!empty($tableName)) {
			$prefix = "`".$tableName."`.";
		}
		if(isset($this->_rightsArray['user'][$mode]) && isset($this->_rightsArray['group'][$mode]) && isset($this->_rightsArray['world'][$mode])) {
			$uid = $this->userID;
			$gids = implode("','", array_keys($this->userData['groups']));

			if($this->userData['isRoot'] === true) {
				$str = "( ($prefix`rights` LIKE '".$this->_rightsArray['user'][$mode]."') ";
				$str .= "OR ($prefix`rights` LIKE '".$this->_rightsArray['group'][$mode]."') ";
				$str .= "OR ($prefix`rights` LIKE '".$this->_rightsArray['world'][$mode]."') )";
			}
			else {
				$str = "( ($prefix`owner` = ".$uid." AND $prefix`rights` LIKE '".$this->_rightsArray['user'][$mode]."') ";
				$str .= "OR ($prefix`group` IN ('".$gids."') AND $prefix`rights` LIKE '".$this->_rightsArray['group'][$mode]."') ";
				$str .= "OR ($prefix`rights` LIKE '".$this->_rightsArray['world'][$mode]."') )";
			}
		}
		else {
			error_log("[ERROR] ".__METHOD__."  invalid rights string: ".var_export($this->_rightsArray, true));
		}

		return $str;
	}

	/**
	 * check if we can use session
	 * we only use session if we can use cookies with the session
	 * THIS DOES NOT CHECK IF THE USER HAS COOKIES ACTIVATED !
	 *
	 * @return bool
	 */
	protected function _checkSession(): bool {

		if(ini_set('session.use_only_cookies',true) === false ||
			ini_set('session.cookie_httponly',true) === false ||
			ini_set('session.use_cookies',true) === false) {

			return false;
		}

		$garbage_timeout = SESSION_LIFETIME + 300;
		ini_set('session.gc_maxlifetime', $garbage_timeout);
		# the % rate how often the session.gc is run
		# http://de.php.net/manual/en/session.configuration.php#ini.session.gc-probability
		ini_set('session.gc_probability',10); // 100 = everytime = 100%

		session_save_path(SESSION_SAVE_PATH);
		session_set_cookie_params(SESSION_LIFETIME);
		session_name(SESSION_NAME);
		session_start();
		# produce problems
		# multiple request at once will confuse the script and loose session information
		#session_regenerate_id(true);

		if(isset($_SESSION[SESSION_NAME]['bibliothecatoken']) && !empty($_SESSION[SESSION_NAME]['bibliothecatoken'])) {
			return $this->_checkAgainstSessionTable();
		}

		return false;
	}

	/**
	 * we have session data available. Now check if those data is valid
	 *
	 * @return bool
	 */
	protected function _checkAgainstSessionTable(): bool {
		$ret = false;

		$timeframe = date("Y-m-d H:i:s",time()-SESSION_LIFETIME);

		$queryStr = "SELECT s.fk_user_id, s.salt, s.token FROM `".DB_PREFIX."_userSession` AS s
			INNER JOIN `".DB_PREFIX."_user` AS u ON s.fk_user_id = u.id
			WHERE s.token = '".$this->_DB->real_escape_string($_SESSION[SESSION_NAME]['bibliothecatoken'])."'
			AND s.salt <> ''
			AND s.loginTime >= '".$timeframe."'";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);

			if ($query !== false && $query->num_rows > 0) {
				# existing session info
				$result = $query->fetch_assoc();

				# validate the token
				$_check = $this->_createToken($result['salt']);
				if (!empty($_check) && $result['token'] === $_check['token']) {
					$this->userID = $result['fk_user_id'];
					$ret = true;
				}
				else {
					error_log("[ERROR] ".__METHOD__." mismatched token.");
					if(isset($result['fk_user_id']) && !empty($result['fk_user_id'])) {
						$this->userID = $result['fk_user_id'];
					}
					$this->_destroySession();
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

	/**
	 * check if the given username is set in user table
	 * if so load the user data
	 *
	 * @param string $u
	 * @return bool
	 */
	protected function _checkAgainstUserTable(string $u): bool {
		$ret = false;

		if(!empty($u)) {
			$queryStr = "SELECT `id`
					FROM `".DB_PREFIX."_user`
					WHERE `login` = '". $this->_DB->real_escape_string($u)."'
					AND `active` = '1'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows > 0) {
					$result = $query->fetch_assoc();
					$this->userID = $result['id'];
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
	 * if we have to run some at login
	 *
	 * @return void
	 */
	protected function _loginActions(): void {
		# clean old sessions on session table
		$timeframe = date("Y-m-d H:i:s",time()-SESSION_LIFETIME);
		$queryStr = "DELETE FROM `".DB_PREFIX."_userSession`
				WHERE `loginTime` <= '".$timeframe."'";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$this->_DB->query($queryStr);
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}
	}

	/**
	 * load the user and groups and fill $this->userData
	 *
	 * @return void
	 */
	protected function _loadUser(): void {
		if(!empty($this->userID)) {
			$queryStr = "SELECT u.`id`, u.`baseGroupId`,u.`protected`,u.`password`,u.`login`,u.`name`,
								u.`apiToken`,u.`apiTokenValidDate`,
							g.name AS groupName, g.description AS groupDescription, g.id AS groupId
						FROM `".DB_PREFIX."_user` AS u
						LEFT JOIN `".DB_PREFIX."_user2group` AS u2g ON u2g.fk_user_id = u.id
						LEFT JOIN `".DB_PREFIX."_group` AS g ON g.id= u2g.fk_group_id
						WHERE u.`id` = '".$this->_DB->real_escape_string($this->userID)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {

					while(($result = $query->fetch_assoc()) != false) {
						$this->userData['id'] = $result['id'];
						$this->userData['baseGroupId'] = $result['baseGroupId'];
						$this->userData['protected'] = $result['protected'];
						$this->userData['password'] = $result['password'];
						$this->userData['login'] = $result['login'];
						$this->userData['name'] = $result['name'];
						$this->userData['apiToken'] = $result['apiToken'];
						$this->userData['apiTokenValidDate'] = $result['apiTokenValidDate'];

						$this->userData['groups'][$result['groupId']] = array(
							'groupName' => $result['groupName'],
							'groupDescription' => $result['groupDescription']
						);
					}

					$this->userData['baseGroupName'] = $this->userData['groups'][$this->userData['baseGroupId']]['groupName'];

					$this->userData['isRoot'] = false;
					$grIds = array_keys($this->userData['groups']);
					if(in_array(ADMIN_GROUP_ID,$grIds)) {
						$this->userData['isRoot'] = true;
					}
				}
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}
	}

	/**
	 * destroy and remove the current session from SESSION and session table
	 *
	 * @return bool
	 */
	protected function _destroySession(): bool {
		$timeframe = date("Y-m-d H:i:s",time()-SESSION_LIFETIME);
		$queryStr = "DELETE FROM `".DB_PREFIX."_userSession`
				WHERE `fk_user_id` = '".$this->_DB->real_escape_string($this->userID)."'
				OR `loginTime` <= '".$timeframe."'";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$this->_DB->query($queryStr);
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		unset($_SESSION);
		unset($_COOKIE);
		session_destroy();

		return true;
	}

	/**
	 * create the usertoken based on the $_SERVER information:
	 * HTTP_USER_AGENT, REMOTE_ADDR, HTTP_DNT, HTTP_VIA, PATH, SHELL, SESSION_MANAGER, USER
	 * and a salt
	 *
	 * @param string $salt
	 * @return array
	 */
	protected function _createToken(string $salt = ''): array {
		$ret = array();

		if(empty($salt)) {
			# 8 chars
			$salt = bin2hex(openssl_random_pseudo_bytes(4));
		}

		if(!isset($_SERVER['HTTP_USER_AGENT'])) $_SERVER['HTTP_USER_AGENT'] = $salt;
		if(!isset($_SERVER['REMOTE_ADDR'])) $_SERVER['REMOTE_ADDR'] = $salt;
		if(!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $salt;
		if(!isset($_SERVER['HTTP_VIA'])) $_SERVER['HTTP_VIA'] = $salt;
		if(!isset($_SERVER['HTTP_DNT'])) $_SERVER['HTTP_DNT'] = $salt;

		// cli info
		if(!isset($_SERVER['PATH'])) $_SERVER['PATH'] = $salt;
		if(!isset($_SERVER['SHELL'])) $_SERVER['SHELL'] = $salt;
		if(!isset($_SERVER['SESSION_MANAGER'])) $_SERVER['SESSION_MANAGER'] = $salt;
		if(!isset($_SERVER['USER'])) $_SERVER['USER'] = $salt;

		$finalString = $_SERVER['HTTP_USER_AGENT']
			.$_SERVER['REMOTE_ADDR']
			.$_SERVER['HTTP_ACCEPT_LANGUAGE']
			.$_SERVER['HTTP_DNT']
			.$_SERVER['HTTP_VIA']
			.$_SERVER['PATH']
			.$_SERVER['SHELL']
			.$_SERVER['SESSION_MANAGER']
			.$_SERVER['USER'];

		$ret['token'] = sha1($finalString.$salt);
		$ret['salt'] = $salt;

		return $ret;
	}
}
