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
 * Class Mancubus everything to show an entry
 */
class Mancubus {
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
	 * Options for db queries
	 *  'limit' => int,
	 *  'offset' => int,
	 *  'orderby' => string,
	 *  'sortDirection' => ASC|DESC
	 *
	 * @var array
	 */
	private $_queryOptions;

	/**
	 * Store the all the values for an entry from lookup table
	 *
	 * @var array
	 */
	private $_cacheLookupValuesForEntry = array();

	/**
	 * Store entryFields for run time
	 *
	 * @var array
	 */
	private $_cacheEntryFields = array();

	/**
	 * Mancubus constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct($databaseConnectionObject, $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;

		$this->_setDefaults();
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
	 * Set the following options which can be used in DB queries
	 * array(
	 *  'limit' => RESULTS_PER_PAGE,
	 *  'offset' => (RESULTS_PER_PAGE * ($_curPage-1)),
	 *  'orderby' => $_sort,
	 *  'sortDirection' => $_sortDirection
	 * );
	 *
	 * @param array $options
	 */
	public function setQueryOptions($options) {

		if(!isset($options['limit'])) $options['limit'] = 5;
		if(!isset($options['offset'])) $options['offset'] = false;
		if(!isset($options['sort'])) $options['sort'] = false;
		if(!isset($options['sortDirection'])) $options['sortDirection'] = false;

		$this->_queryOptions = $options;
	}

	/**
	 * Get all available collections for display based on current user
	 *
	 * @param string $selections Number of selections
	 * @param string $entries Number of entries
	 * @param string $search Search string to search for
	 * @return array
	 */
	public function getLatest(string $selections, string $entries, $search=''): array {
		$ret = array();

		$queryStr = "SELECT `c`.`id`, `c`.`name`, `c`.`description`, `c`.`created`,
					`c`.`owner`, `c`.`group`, `c`.`rights`, 
					`u`.`name` AS username, `g`.`name` AS groupname
					FROM `".DB_PREFIX."_collection` AS c
					LEFT JOIN `".DB_PREFIX."_user` AS u ON `c`.`owner` = `u`.`id`
					LEFT JOIN `".DB_PREFIX."_group` AS g ON `c`.`group` = `g`.`id`
					WHERE ".$this->_User->getSQLRightsString("read", "c")."
					ORDER BY `c`.`name`
					LIMIT $selections";
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);

			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$_mObj = new Mancubus($this->_DB,$this->_User);
					$_mObj->setCollection($result['id']);
					$_mObj->setQueryOptions(array('limit' => $entries));

					if(!empty($search)) {
						require_once 'lib/trite.class.php';
						$_colObj = new Trite($this->_DB,$this->_User);
						$_colObj->load($result['id']);
						$_fd = $_colObj->getCollectionFields();

						$result['entries'] = $_mObj->getEntries(
							array(
								0 => array(
									'colName' => $_colObj->param('defaultSearchField'),
									'colValue' => $search,
									'fieldData' => $_fd[$_colObj->param('defaultSearchField')]
								)
							)
						);
					}
					else {
						$result['entries'] = $_mObj->getEntries();
					}
					$ret[$result['id']] = $result;
					unset($_mObj);
				}
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

	/**
	 * Get entries for loaded collection limited by search
	 * and already set query options
	 *
	 * array[0] => array(
	 * 		'colName' => 'column name to search in',
	 * 		'colValue' => 'Value to search for',
	 * 		'fieldData' => field data from Trite->getCollectionFields()
	 * 		'exactTagMatch' => true to make a binary compare. false for match against search
	 * )
	 *
	 * return array(
	 * 		'results' => array(),
	 * 		'amount' => int,
	 * 		'ids' => array()
	 * )
	 *
	 * @param array $searchData
	 * @return array
	 */
	public function getEntries($searchData=array()): array {
		$ret = array();

		if(!empty($this->_collectionId)) {
			// split since part of it is used later
			$querySelect = "SELECT *";
			$queryFrom = " FROM `".DB_PREFIX."_collection_entry_".$this->_DB->real_escape_string($this->_collectionId)."` AS t";
			$queryJoin = '';
			$queryWhere = " WHERE ".$this->_User->getSQLRightsString("read", "t")."";

			$_isFulltext = false;
			if(!empty($searchData)) {
				// this search supports fulltext search and number <> search.
				// also can search in the entry2lookup table.
				// not perfect but it works
				foreach($searchData as $k=>$sd) {
					if(!isset($sd['colName']) || !isset($sd['colValue']) || empty($sd['colValue'])) continue;

					if($sd['fieldData']['searchtype'] == "tag") {
						$_isFulltext = true;

						$queryJoin = " LEFT JOIN `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."` AS e2l ON e2l.fk_entry=t.id";

						$queryWhere .= " AND e2l.fk_field = '".$this->_DB->real_escape_string($sd['fieldData']['id'])."'";
						if(isset($sd['exactTagMatch']) && $sd['exactTagMatch'] === true) {
							$queryWhere .= " AND e2l.value = BINARY '".$this->_DB->real_escape_string($sd['colValue'])."'";
							$_isFulltext = false;
						}
						else {
							$queryWhere .= " AND MATCH (e2l.value) AGAINST ('".$this->_DB->real_escape_string($sd['colValue'])."' IN BOOLEAN MODE)";
						}
					}
					elseif ($sd['fieldData']['searchtype'] == "entrySingleNum" && strstr($sd['colValue'],'<')) {
						$_s = str_replace('<','',$sd['colValue']);
						$queryWhere .= " AND `t`.`".$this->_DB->real_escape_string($sd['colName'])."` < ".(int)$_s."";
					}
					elseif ($sd['fieldData']['searchtype'] == "entrySingleNum" && strstr($sd['colValue'],'>')) {
						$_s = str_replace('>','',$sd['colValue']);
						$queryWhere .= " AND `t`.`".$this->_DB->real_escape_string($sd['colName'])."` > ".(int)$_s."";
					}
					elseif($sd['fieldData']['searchtype'] == "entryText") {
						$_isFulltext = true;
						$queryWhere .= " AND MATCH (`t`.`".$this->_DB->real_escape_string($sd['colName'])."`) 
											AGAINST ('".$this->_DB->real_escape_string($sd['colValue'])."' IN BOOLEAN MODE)";
					}
					else {
						$queryWhere .= " AND `t`.`".$this->_DB->real_escape_string($sd['colName'])."` = '".$this->_DB->real_escape_string($sd['colValue'])."'";
					}
				}
			}

			$queryOrder = '';
			if(!$_isFulltext) { // fulltext do not order. Which results in ordering be relevance of the match
				$queryOrder = " ORDER BY";
				if (!empty($this->_queryOptions['sort'])) {
					$queryOrder .= ' t.'.$this->_queryOptions['sort'];
				}
				else {
					$queryOrder .= " t.created";
				}
				if (!empty($this->_queryOptions['sortDirection'])) {
					$queryOrder .= ' '.$this->_queryOptions['sortDirection'];
				}
				else {
					$queryOrder .= " ASC";
				}
			}

			$queryLimit = '';
			if(!empty($this->_queryOptions['limit'])) {
				$queryLimit .= " LIMIT ".$this->_queryOptions['limit'];
				# offset can be 0
				if($this->_queryOptions['offset'] !== false) {
					$queryLimit .= " OFFSET ".$this->_queryOptions['offset'];
				}
			}

			$queryStr = $querySelect.$queryFrom.$queryJoin.$queryWhere.$queryOrder.$queryLimit;
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));

			try {
				$query = $this->_DB->query($queryStr);

				if($query !== false && $query->num_rows > 0) {
					$_entryFields = $this->_getEntryFields();

					while(($result = $query->fetch_assoc()) != false) {
						$result = $this->_mergeEntryWithFields($result, $_entryFields);

						$ret['results'][$result['id']] = $result;
						$ret['ids'][] = $result['id'];
					}

					$queryStrCount = "SELECT COUNT(t.id) AS amount ".$queryFrom.$queryJoin.$queryWhere;
					if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStrCount,true));
					$query = $this->_DB->query($queryStrCount);
					$result = $query->fetch_assoc();
					$ret['amount'] = $result['amount'];
				}
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Retrieve all the data needed to display the entry for given entryId
	 *
	 * @param string $entryId Number
	 * @return array|mixed
	 */
	public function getEntry(string $entryId): array {
		$ret = array();

		if(!empty($this->_collectionId) && !empty($entryId)) {
			$queryStr = "SELECT * 
						FROM `".DB_PREFIX."_collection_entry_".$this->_DB->real_escape_string($this->_collectionId)."` 
						WHERE ".$this->_User->getSQLRightsString()."
						AND `id` = '".$this->_DB->real_escape_string($entryId)."'";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);

				if($query !== false && $query->num_rows > 0) {
					$_entryFields = $this->_getEntryFields();

					if(($result = $query->fetch_assoc()) != false) {
						$ret = $this->_mergeEntryWithFields($result, $_entryFields);
					}
				}
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Get entries for loaded collection by looking for the given value in given field
	 *
	 * @param string $fieldId Number ID of the field to search in
	 * @param string $fieldValue Value of the field
	 * @return array
	 */
	public function getEntriesByFieldValue($fieldId, $fieldValue) {
		$ret = array();

		$_entryFields = $this->_getEntryFields();
		if(isset($_entryFields[$fieldId])) {
			$fieldData = $_entryFields[$fieldId];
		}

		if(empty($fieldData)) return $ret;

		if($fieldData['type'] !== "lookupmultiple") {
			return $this->getEntries(
				array(
					0 => array(
						'colName' => $fieldData['identifier'],
						'colValue' => $fieldValue,
						'fieldData' => $fieldData
					)
				)
			);

		}

		$querySelect = "SELECT `fk_entry`";
		$queryFrom = " FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."` AS t";
		$queryWhere = " WHERE t.fk_field = '".$this->_DB->real_escape_string($fieldId)."'
					AND t.value = '".$this->_DB->real_escape_string($fieldValue)."'";

		$queryOrder = " ORDER BY";
		if(!empty($this->_queryOptions['sort'])) {
			$queryOrder .= ' t.'.$this->_queryOptions['sort'];
		}
		else {
			$queryOrder .= " t.value";
		}
		if(!empty($this->_queryOptions['sortDirection'])) {
			$queryOrder .= ' '.$this->_queryOptions['sortDirection'];
		}
		else {
			$queryOrder .= " ASC";
		}

		$queryLimit = '';
		if(!empty($this->_queryOptions['limit'])) {
			$queryLimit .= " LIMIT ".$this->_queryOptions['limit'];
			# offset can be 0
			if($this->_queryOptions['offset'] !== false) {
				$queryLimit .= " OFFSET ".$this->_queryOptions['offset'];
			}
		}

		$queryStr = $querySelect.$queryFrom.$queryWhere.$queryOrder.$queryLimit;
		if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
		try {
			$query = $this->_DB->query($queryStr);

			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$_r = $this->getEntry($result['fk_entry']);
					$ret['results'][$_r['id']] = $_r;
				}

				$queryCountStr = "SELECT COUNT(t.value) AS amount ".$queryFrom.$queryWhere;
				if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryCountStr,true));
				$query = $this->_DB->query($queryCountStr);
				$result = $query->fetch_assoc();
				$ret['amount'] = $result['amount'];
			}
		}
		catch (Exception $e) {
			error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

	/**
	 * Return the storage info for loaded collection
	 * Used by API
	 *
	 * @return array|mixed
	 */
	public function getEntryStructure() {
		$ret = array();

		$_entryFields = $this->_getEntryFields();
		$ret = $this->_mergeEntryWithFields($ret, $_entryFields);

		return $ret;
	}

	/**
	 * Load the fields from the loaded collection
	 *
	 * @return array
	 */
	private function _getEntryFields() {

		if(!empty($this->_cacheEntryFields)) {
			return $this->_cacheEntryFields;
		}

		if(!empty($this->_collectionId)) {
			$queryStr = "SELECT `cf`.`fk_field_id` AS id, `sf`.`type`, `sf`.`displayname`, `sf`.`identifier`,
								`sf`.`value` AS preValue, `sf`.`apiinfo` , `sf`.`searchtype`
						FROM `".DB_PREFIX."_collection_fields_".$this->_DB->real_escape_string($this->_collectionId)."` AS cf
						LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
						ORDER BY `cf`.`sort`";
			if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {
					while(($result = $query->fetch_assoc()) != false) {
						$this->_cacheEntryFields[$result['id']] = $result;
					}
				}
			}
			catch (Exception $e) {
				error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $this->_cacheEntryFields;
	}

	/**
	 * Merge the loaded information from collection_entry with the given
	 * configured fields
	 *
	 * @param array $entryData Loaded entry
	 * @param array $entryFields Loaded fields
	 * @return mixed
	 */
	private function _mergeEntryWithFields($entryData, $entryFields) {
		if(!empty($entryFields)) {
			foreach($entryFields as $f) {
				$_mnValue = '_loadFieldValue_'.$f['type'];

				if(isset($entryData[$f['identifier']])) {
					$f['value'] = $entryData[$f['identifier']];
					unset($entryData[$f['identifier']]);
				} elseif(method_exists($this, $_mnValue) && isset($entryData['id'])) {
					$f['value'] = $this->$_mnValue($entryData['id'], $f);
				}

				$_mnSelectionValue = '_loadFieldSelection_'.$f['type'];
				if(method_exists($this, $_mnSelectionValue) && isset($f['preValue'])) {
					$f['preValue'] = $this->$_mnSelectionValue($f['preValue']);
				}

				$entryData['fields'][$f['identifier']] = $f;
			}
		}
		return $entryData;
	}

	/**
	 * Load the values for given $entryId for $fieldData
	 * lookup function for field type lookupmultiple
	 *
	 * @param string $entryId Number
	 * @param array $fieldData
	 * @return array
	 */
	private function _loadFieldValue_lookupmultiple($entryId, $fieldData) {
		$ret = array();

		if(!empty($entryId) && !empty($fieldData) && !empty($this->_collectionId)) {

			// avoid db query for each wanted value
			if(isset($this->_cacheLookupValuesForEntry[$this->_collectionId])) {
				if(isset($this->_cacheLookupValuesForEntry[$this->_collectionId][$entryId][$fieldData['id']])) {
					$ret =  $this->_cacheLookupValuesForEntry[$this->_collectionId][$entryId][$fieldData['id']];
				}
			}
			else {
				$queryStr = "SELECT `fk_field`, `value`, `fk_entry`
							FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`";
				if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
				try {
					$query = $this->_DB->query($queryStr);
					if($query !== false && $query->num_rows > 0) {
						while(($result = $query->fetch_assoc()) != false) {
							$this->_cacheLookupValuesForEntry[$this->_collectionId][$result['fk_entry']][$result['fk_field']][$result['value']] = $result['value'];
						}
					}
				}
				catch (Exception $e) {
					error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
				}
				if(isset($this->_cacheLookupValuesForEntry[$this->_collectionId][$entryId][$fieldData['id']])) {
					$ret =  $this->_cacheLookupValuesForEntry[$this->_collectionId][$entryId][$fieldData['id']];
				}
			}
		}

		return $ret;
	}

	/**
	 * Get the single upload file from storage location
	 * lookup function for field type upload
	 *
	 * @param string $entryId Number
	 * @param array $fieldData
	 * @return string
	 */
	private function _loadFieldValue_upload($entryId, $fieldData) {
		$ret = "";
		if(!empty($entryId) && !empty($fieldData) && !empty($this->_collectionId)) {

			$uploadedFile = glob(PATH_STORAGE.'/'.$this->_collectionId.'/'.$entryId.'/'.$fieldData['identifier'].'-*');
			if(!empty($uploadedFile)) {
				foreach ($uploadedFile as $f) {
					$ret = basename($f);
					break;
				}
			}
		}
		return $ret;
	}

	/**
	 * Get the multiple upload files from storage location
	 * lookup function for field type upload_multiple
	 *
	 * @param string $entryId Number
	 * @param string $fieldData
	 * @return array
	 */
	private function _loadFieldValue_upload_multiple($entryId, $fieldData) {
		$ret = array();
		if(!empty($entryId) && !empty($fieldData) && !empty($this->_collectionId)) {

			$uploadedFile = glob(PATH_STORAGE.'/'.$this->_collectionId.'/'.$entryId.'/'.$fieldData['identifier'].'-*');
			if(!empty($uploadedFile)) {
				foreach ($uploadedFile as $f) {
					$ret[] = basename($f);
				}
			}
		}
		return $ret;
	}

	/**
	 * Load and prepare the value for a selection field
	 *
	 * @param string $data
	 * @return array
	 */
	private function _loadFieldSelection_selection($data) {
		$ret = array();

		if(is_string($data)) {
			if(strstr($data, ',')) {
				$ret = explode(',',$data);
			}
			else {
				$ret[] = $data;
			}
		}

		return $ret;
	}

	/**
	 * set some defaults by init of the class
	 *
	 * @return void
	 */
	private function _setDefaults() {
		// default query options
		$options['limit'] = 5;
		$options['offset'] = false;
		$options['sort'] = false;
		$options['sortDirection'] = false;
		$this->setQueryOptions($options);
	}
}
