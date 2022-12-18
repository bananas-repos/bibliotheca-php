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
 * the menu class. Provides the menu based on user
 */
class GoreNest {

	/**
	 * the global DB object
	 *
	 * @var mysqli
	 */
	private mysqli $_DB;

	/**
	 * the current loaded user
	 *
	 * @var Doomguy
	 */
	private Doomguy $_User;

	/**
	 * the already loaded menu information
	 * to avoid multiple calls to the DB
	 *
	 * @var array
	 */
	private array $_menuData = array();

	/**
	 * Array for faster check which call is allowed
	 *
	 * @var array
	 */
	private array $_allowedPageRequests = array();

	/**
	 * GoreNest constructor.
	 *
	 * @param mysqli $db
	 * @param Doomguy $user
	 */
	public function __construct(mysqli $db, Doomguy $user) {
		$this->_DB = $db;
		$this->_User = $user;
		$this->_loadMenu();
	}

	/**
	 * Get the menu data for given area and category.
	 * This shows only entries which have a category set.
	 * No category can be used for hidden entries.
	 *
	 * @param string $category
	 * @param bool $reload
	 * @param array $_contextActions
	 * @return array
	 */
	public function get(string $category, bool $reload = false, array $_contextActions = array()): array {
		$ret = array();

		if(empty($category)) return $ret;

		if($reload === false && isset($this->_menuData[$category])) {
			return $this->_updateContextActions($this->_menuData[$category], $_contextActions);
		}

		$this->_loadMenu();
		if(isset($this->_menuData[$category])) {
			$ret = $this->_menuData[$category];
		}

		return $this->_updateContextActions($ret, $_contextActions);
	}

	/**
	 * Load the complete menu
	 *
	 * @return void
	 */
	private function _loadMenu(): void {
		# reset the menu
		$this->_menuData = array();

		$queryStr = "SELECT id, text, action, icon, category, contextaction
					FROM `".DB_PREFIX."_menu`
					WHERE ".$this->_User->getSQLRightsString()."				
						ORDER BY position";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query  = $this->_DB->query($queryStr);
			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$this->_allowedPageRequests[$result['action']] = $result['action'];
					$this->_menuData[$result['category']][$result['id']] = $result;
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}
	}

	/**
	 * Return allowed page requests
	 *
	 * @return array
	 */
	public function allowedPageRequests(): array {
		return $this->_allowedPageRequests;
	}

	/**
	 * Check if there is the need to modify the action value based on contextaction column
	 * and $_contextActions array
	 *
	 * @param array $_menuData
	 * @param array $_contextActions
	 * @return array
	 */
	private function _updateContextActions(array $_menuData, array $_contextActions): array {
		if(!empty($_contextActions)) {
			foreach($_menuData as $id=>$data) {
				if(isset($_contextActions[$data['contextaction']])) {
					$_menuData[$id]['action'] = $data['action'].'&'.$data['contextaction'].'='.$_contextActions[$data['contextaction']];
				}
			}
		}
		return $_menuData;
	}
}
