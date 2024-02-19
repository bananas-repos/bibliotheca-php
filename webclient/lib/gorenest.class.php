<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2024 Johannes Keßler
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

		$queryStr = "SELECT `id`, `text`, `action`, `icon`, `category`, `contextaction`
					FROM `".DB_PREFIX."_menu`
					WHERE ".$this->_User->getSQLRightsString()."				
						ORDER BY position";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
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
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
