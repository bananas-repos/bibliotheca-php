<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2022 Johannes Keßler
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
	private mysqli $_DB;

	/**
	 * The user object to query with
	 *
	 * @var Doomguy
	 */
	private Doomguy $_User;

	/**
	 * Tentacle constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 *
	 */
	public function __construct(mysqli $databaseConnectionObject, Doomguy $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Validate if given action is a valid tool and if the user has access
	 *
	 * @param string $identifier
	 * @return array
	 */
	public function validate(string $identifier): array {
		$ret = array();

		$queryStr = "SELECT `name`,`description`,`action`
					FROM `".DB_PREFIX."_tool`  
					WHERE ".$this->_User->getSQLRightsString()."
					AND `action` = '".$this->_DB->real_escape_string($identifier)."'";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);
			if ($query !== false && $query->num_rows > 0) {
				$ret = $query->fetch_assoc();
			}

		} catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			if(DEBUG) error_log("[DEBUG] ".__METHOD__." mysql query: ".$queryStr);
		}

		return $ret;
	}

	/**
	 * Default creation info based on current user
	 *
	 * @return array
	 */
	public function getDefaultCreationInfo(): array {
		return array(
			'id' => $this->_User->param('id'),
			'group' => $this->_User->param('baseGroupId'),
			'rights' => 'rwxrwxr--'
		);
	}
}
