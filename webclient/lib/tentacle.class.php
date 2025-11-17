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
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$query = $this->_DB->query($queryStr);
			if ($query !== false && $query->num_rows > 0) {
				$ret = $query->fetch_assoc();
			}

		} catch (Exception $e) {
            Summoner::cleanForLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			if(QUERY_DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." mysql query: ".$queryStr);
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
