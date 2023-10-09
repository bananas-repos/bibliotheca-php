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
 * Class ManageTags to manage the tags of a collection
 */
class ManageTags {
	/**
	 * The database object
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
	 * Currently loaded collection to work with
	 *
	 * @var string Number
	 */
	private string $_collectionId;

	/**
	 * ManageTags constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct(mysqli $databaseConnectionObject, Doomguy $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Set the to work with collection id
	 *
	 * @param string $collectionId Number
	 */
	public function setCollection(string $collectionId): void {
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
	public function doWithTag(string $ident, array $data): string {
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
	private function _move(string $field, string $from, string $to): string {
		$ret = '';

		if(!Summoner::validate($field,'digit')) return 'Invalid field id for move/rename';
		if(!Summoner::validate($from)) return 'Invalid use data for move/rename';
		if(!Summoner::validate($to)) return 'Invalid to data for move/rename';

		$queryStr = "UPDATE `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
					SET `value` = '".$this->_DB->real_escape_string($to)."'
					WHERE `fk_field` = '".$this->_DB->real_escape_string($field)."'
						AND `value` = BINARY '".$this->_DB->real_escape_string($from)."'";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$this->_DB->query($queryStr);
		}
		catch (Exception $e) {
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	private function _delete(string $field, string $what): string {
		$ret = '';

		if(!Summoner::validate($field,'digit')) return 'Invalid field id for delete';
		if(!Summoner::validate($what)) return 'Invalid use data for delete';

		$queryStr = "DELETE FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
					WHERE `fk_field` = '".$this->_DB->real_escape_string($field)."'
						AND `value` = BINARY '".$this->_DB->real_escape_string($what)."'";
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$this->_DB->query($queryStr);
		}
		catch (Exception $e) {
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			$ret = 'Error in delete query. See logs.';
		}

		return $ret;
	}
}
