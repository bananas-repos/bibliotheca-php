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
 * Class Spectre
 * API for Bibliotheca
 */
class Spectre {
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
	 * Allowed request params
	 *
	 * @var array
	 */
	private $_allowedRequests = array('default','list','add','addInfo');

	/**
	 * Spectre constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct($databaseConnectionObject, $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Validate given request string
	 *
	 * @param string $request
	 * @return bool
	 */
	public function allowedRequests($request) {
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
	public function buildAddStructure($data) {
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
	public function prepareFilesArray($data) {
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
