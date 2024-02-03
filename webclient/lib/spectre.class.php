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
 * Class Spectre
 * API for Bibliotheca
 */
class Spectre {
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
	 * Allowed request params
	 *
	 * @var array
	 */
	private array $_allowedRequests = array('default','list','add','addInfo');

	/**
	 * Spectre constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct(mysqli $databaseConnectionObject, Doomguy $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Validate given request string
	 *
	 * @param string $request
	 * @return bool
	 */
	public function allowedRequests(string $request): bool {
		$ret = false;

		if(in_array($request, $this->_allowedRequests)) {
			$ret = true;
		}

		return $ret;
	}

	/**
	 * With given data build the structure to create a add post
	 * request
	 *
	 * @param array $data
	 * @return array
	 */
	public function buildAddStructure(array $data): array {
		$ret = array();

		if(!empty($data) && is_array($data)) {
			foreach($data as $k=>$v) {
				$ret[$k] = array('input' => $v['apiinfo']);
			}
		}

		return $ret;
	}

	/**
	 * rewrite the data from curl into the format the
	 * POST via web frontend creates
	 * "The problem occurs when you have a form that uses both single file and HTML array feature."
	 *
	 * @param array $data
	 * @return array
	 */
	public function prepareFilesArray(array $data): array {
		$ret = array();

		if(!empty($data)) {
			foreach($data as $fieldName=>$fdata) {
				foreach($fdata as $k=>$v) {
					$ret[$k][$fieldName] = $v;
				}

			}
		}

		return $ret;
	}
}
