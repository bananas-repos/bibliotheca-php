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
 * the menu class. Provides the menu based on user
 */
class GoreNest {

	/**
	 * the global DB object
	 *
	 * @var mysqli
	 */
	private $_DB;

	/**
	 * the current loaded user
	 *
	 * @var Doomguy
	 */
	private $_User;

	/**
	 * the already loaded menu information
	 * to avoid multiple calls to the DB
	 *
	 * @var array
	 */
	private $_menuData = array();

	/**
	 * Array for faster check which call is allowed
	 *
	 * @var array
	 */
	private $_allowedPageRequests = array();

	/**
	 * GoreNest constructor.
	 *
	 * @param mysqli $db
	 * @param Doomguy $user
	 */
	public function __construct($db, $user) {
		$this->_DB = $db;
		$this->_User = $user;
	}

	/**
	 * Load the complete menu
	 *
	 * @return void
	 */
	public function loadMenu() {
		# reset the menu
		$this->_menuData = array();

		$queryStr = "SELECT id, text, action, icon, category
					FROM `".DB_PREFIX."_menu`
					WHERE ".$this->_User->getSQLRightsString()."				
						ORDER BY position";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query  = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$this->_menuData[$result['category']][$result['id']] = $result;
					$this->_allowedPageRequests[$result['action']] = $result['action'];
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}
	}

	/**
	 * Get the menu data for given area and category.
	 * This shows only entries which have a category set.
	 * No category can be used for hidden entries.
	 *
	 * @param string $category
	 * @param bool $reload
	 * @return array
	 */
	public function get($category,$reload=false) {

		if(empty($category)) return false;

		if(empty($reload) && isset($this->_menuData[$category])) {
			return $this->_menuData[$category];
		}
		$this->loadMenu($reload);

		return $this->_menuData[$category];
	}

	/**
	 * Return allowed page requests
	 *
	 * @return array
	 */
	public function allowedPageRequests() {
		return $this->_allowedPageRequests;
	}
}
