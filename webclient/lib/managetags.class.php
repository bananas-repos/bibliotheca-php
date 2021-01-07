<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2021 Johannes Keßler
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
 * Class ManageTags to manage the tags of a collection
 */
class ManageTags {
	/**
	 * The database object
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
	 * Currently loaded collection to work with
	 *
	 * @var string Number
	 */
	private $_collectionId;

	/**
	 * ManageTags constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct($databaseConnectionObject, $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Set the to work with collection id
	 *
	 * @param string $collectionId Number
	 */
	public function setCollection($collectionId) {
		if(!empty($collectionId)) {
			$this->_collectionId = $collectionId;
		}
	}

	/**
	 * Either move, rename or delete (only one of those) with the given field
	 * and its value
	 *
	 * Return strategy here: empty string if everything works or nothing is to do. String error msg for error
	 *
	 * @param string $ident ID to use in lookup table
	 * @param array $data Needs use=fromString, move=toString, doDelete=true
	 * @return string
	 */
	public function doWithTag($ident, $data) {
		$ret = '';

		if(!empty($this->_collectionId) && !empty($ident) && !empty($data) && isset($data['use']) && !empty($data['use'])) {
			if(isset($data['move']) && !empty($data['move'])) {
				$ret = $this->_move($ident, $data['use'], $data['move']);
			}
			elseif (isset($data['rename']) && !empty($data['rename'])) {
				$ret = $this->_move($ident, $data['use'], $data['rename']);
			}
			elseif (isset($data['doDelete']) && !empty($data['doDelete'])) {
				$ret = $this->_delete($ident, $data['use']);
			}
		}

		return $ret;
	}

	/**
	 * Move in field from to given new string
	 * Does a BINARY compare in SQL for $from
	 *
	 * @param string $field Field ID to use in lookup table
	 * @param string $from Value string to search for in lookup table
	 * @param string $to Value string to set to in lookup table
	 * @return string
	 */
	private function _move($field, $from, $to) {
		$ret = '';

		if(!Summoner::validate($field,'digit')) return 'Invalid field id for move/rename';
		if(!Summoner::validate($from)) return 'Invalid use data for move/rename';
		if(!Summoner::validate($to)) return 'Invalid to data for move/rename';

		$queryStr = "UPDATE `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
					SET `value` = '".$this->_DB->real_escape_string($to)."'
					WHERE `fk_field` = '".$this->_DB->real_escape_string($field)."'
						AND `value` = BINARY '".$this->_DB->real_escape_string($from)."'";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$this->_DB->query($queryStr);
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			$ret = 'Error in move/rename query. See logs.';
		}

		return $ret;
	}

	/**
	 * Delete the given $what for field $field in entry lookup table.
	 * Does a BINARY compare in SQL for $what
	 *
	 * @param string $field Field ID to use in lookup table
	 * @param string $what Value to search for and delete from lookup table
	 * @return string
	 */
	private function _delete($field, $what) {
		$ret = '';

		if(!Summoner::validate($field,'digit')) return 'Invalid field id for delete';
		if(!Summoner::validate($what)) return 'Invalid use data for delete';

		$queryStr = "DELETE FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
					WHERE `fk_field` = '".$this->_DB->real_escape_string($field)."'
						AND `value` = BINARY '".$this->_DB->real_escape_string($what)."'";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$this->_DB->query($queryStr);
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			$ret = 'Error in delete query. See logs.';
		}

		return $ret;
	}
}
