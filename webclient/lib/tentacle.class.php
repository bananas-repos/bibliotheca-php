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
 * Class Tentacle
 * Tools management
 */
class Tentacle {
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
	 * Tentacle constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct($databaseConnectionObject, $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Validate if given action is a valid tool and if the user has access
	 *
	 * @param string $identifier
	 * @return bool
	 */
	public function validate($identifier) {
		$ret = false;

		$queryStr = "SELECT `name`,`description`,`action`
					FROM `".DB_PREFIX."_tool`  
					WHERE ".$this->_User->getSQLRightsString()."
					AND `action` = '".$this->_DB->real_escape_string($identifier)."'";
		try {
			$query = $this->_DB->query($queryStr);
			if ($query !== false && $query->num_rows > 0) {
				$ret = $query->fetch_assoc();
			}

		} catch (Exception $e) {
			if(DEBUG) error_log("[DEBUG] ".__METHOD__." mysql catch: ".$e->getMessage());
			if(DEBUG) error_log("[DEBUG] ".__METHOD__." mysql query: ".$queryStr);
		}

		return $ret;
	}

	/**
	 * Default creation info based on current user
	 *
	 * @return array
	 */
	public function getDefaultCreationInfo() {
		return array(
			'id' => $this->_User->param('id'),
			'group' => $this->_User->param('baseGroupId'),
			'rights' => 'rwxrwxr--'
		);
	}
}
