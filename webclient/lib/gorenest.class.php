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
	 * @var object
	 */
	private $_DB;

	/**
	 * the current loaded user
	 * @var object
	 */
	private $_User;

	/**
	 * the already loaded menu information
	 * to avoid multiple calls to the DB
	 * @var array
	 */
	private $_menuData = array();

	/**
	 * GoreNest constructor.
	 * @param $db
	 * @param $user
	 */
	public function __construct($db, $user) {
		$this->_DB = $db;
		$this->_User = $user;
	}

	/**
	 * Get the menu data for given area and category.
	 * This shows only entries which have a category set.
	 * No category can be used for hidden entries.
	 *
	 * @param $category
	 * @param bool $reload
	 * @return array
	 */
	public function get($category,$reload=false) {

		if(empty($category)) return false;

		if(empty($reload) && isset($this->_menuData[$category])) {
			return $this->_menuData[$category];
		}

		# reset the menu
		$this->_menuData[$category] = array();

		$queryStr = "SELECT id, text, action, icon, category
					FROM `".DB_PREFIX."_menu`
					WHERE ".$this->_User->getSQLRightsString()."
						AND `category` = '".$this->_DB->real_escape_string($category)."'
						ORDER BY position";
		$query  = $this->_DB->query($queryStr);
		if($query !== false && $query->num_rows > 0) {
			while(($result = $query->fetch_assoc()) != false) {
				$this->_menuData[$result['category']][$result['id']] = $result;
			}
		}

		return $this->_menuData[$category];
	}

	/**
	 * Allowed page requests based on the menu entries and user
	 * @return array
	 */
	public function allowedPageRequests() {
		$ret = array();
		$queryStr = "SELECT id, action
					FROM `".DB_PREFIX."_menu`
					WHERE ".$this->_User->getSQLRightsString()."";
		$query  = $this->_DB->query($queryStr);
		if($query !== false && $query->num_rows > 0) {
			while(($result = $query->fetch_assoc()) != false) {
				$ret[$result['action']] = $result['action'];
			}
		}

		return $ret;
	}
}
