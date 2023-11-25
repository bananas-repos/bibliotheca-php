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
 * Class Mancubus everything to show an entry
 */
class Mancubus {
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
	 * Options for db queries
	 *  'limit' => int,
	 *  'offset' => int,
	 *  'orderby' => string,
	 *  'sortDirection' => ASC|DESC
	 *
	 * @var array
	 */
	private array $_queryOptions;

	/**
	 * Store the all the values for an entry from lookup table
	 *
	 * @var array
	 */
	private array $_cacheLookupValuesForEntry = array();

	/**
	 * Store entryFields for run time
	 *
	 * @var array
	 */
	private array $_cacheEntryFields = array();

	/**
	 * Mancubus constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct(mysqli $databaseConnectionObject, Doomguy $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;

		$this->_setDefaults();
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
	public function setQueryOptions(array $options): void {

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
	public function getLatest(string $selections, string $entries, string $search = ''): array {
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
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$query = $this->_DB->query($queryStr);

			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$_mObj = new Mancubus($this->_DB,$this->_User);
					$_mObj->setCollection($result['id']);

					if(!empty($search)) {
						require_once 'lib/trite.class.php';
						$_colObj = new Trite($this->_DB,$this->_User);
						$_colObj->load($result['id']);
						$_fd = $_colObj->getCollectionFields();
						$_defSearchField = $_colObj->param('defaultSearchField');

                        $_mObj->setQueryOptions(array(
                            'limit' => $entries,
                            'sortDirection' => $_colObj->param('defaultSortOrder'),
                            'sort' => $_colObj->param('defaultSortField')
                        ));
						$_defSearchField = $_colObj->param('defaultSearchField');

						if(!empty($_defSearchField)) {
							$result['entries'] = $_mObj->getEntries(
								array(
									0 => array(
										'colName' => $_defSearchField,
										'colValue' => $search,
										'fieldData' => $_fd[$_defSearchField]
									)
								)
							);
						}
						else {
                            Summoner::sysLog("[WARN] ".__METHOD__." missing default search field for collectionid: ".$result['id']);
						}
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
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	public function getEntries(array $searchData = array()): array {
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
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));

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
					if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStrCount));
					$query = $this->_DB->query($queryStrCount);
					$result = $query->fetch_assoc();
					$ret['amount'] = $result['amount'];
				}
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Retrieve all the data needed to display the entry for given entryId
	 *
	 * @param string $entryId Number
	 * @return array
	 */
	public function getEntry(string $entryId): array {
		$ret = array();

		if(!empty($this->_collectionId) && !empty($entryId)) {
			$queryStr = "SELECT * 
						FROM `".DB_PREFIX."_collection_entry_".$this->_DB->real_escape_string($this->_collectionId)."` 
						WHERE ".$this->_User->getSQLRightsString()."
						AND `id` = '".$this->_DB->real_escape_string($entryId)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
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
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	public function getEntriesByFieldValue(string $fieldId, string $fieldValue): array {
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
		if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
		try {
			$query = $this->_DB->query($queryStr);

			if($query !== false && $query->num_rows > 0) {
				while(($result = $query->fetch_assoc()) != false) {
					$_r = $this->getEntry($result['fk_entry']);
					$ret['results'][$_r['id']] = $_r;
				}

				$queryCountStr = "SELECT COUNT(t.value) AS amount ".$queryFrom.$queryWhere;
				if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryCountStr));
				$query = $this->_DB->query($queryCountStr);
				$result = $query->fetch_assoc();
				$ret['amount'] = $result['amount'];
			}
		}
		catch (Exception $e) {
            Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
		}

		return $ret;
	}

	/**
	 * Return the storage info for loaded collection
	 * Used by API
	 *
	 * @return array
	 */
	public function getEntryStructure(): array {
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
	private function _getEntryFields(): array {

		if(!empty($this->_cacheEntryFields)) {
			return $this->_cacheEntryFields;
		}

		if(!empty($this->_collectionId)) {
			$queryStr = "SELECT `cf`.`fk_field_id` AS id, `sf`.`type`, `sf`.`displayname`, `sf`.`identifier`,
								`sf`.`value` AS preValue, `sf`.`apiinfo` , `sf`.`searchtype`
						FROM `".DB_PREFIX."_collection_fields_".$this->_DB->real_escape_string($this->_collectionId)."` AS cf
						LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
						ORDER BY `cf`.`sort`";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {
					while(($result = $query->fetch_assoc()) != false) {
						$this->_cacheEntryFields[$result['id']] = $result;
					}
				}
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	private function _mergeEntryWithFields(array $entryData, array $entryFields): array {
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
	private function _loadFieldValue_lookupmultiple(string $entryId, array $fieldData): array {
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
				if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
				try {
					$query = $this->_DB->query($queryStr);
					if($query !== false && $query->num_rows > 0) {
						while(($result = $query->fetch_assoc()) != false) {
							$this->_cacheLookupValuesForEntry[$this->_collectionId][$result['fk_entry']][$result['fk_field']][$result['value']] = $result['value'];
						}
					}
				}
				catch (Exception $e) {
                    Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
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
	private function _loadFieldValue_upload(string $entryId, array $fieldData): string {
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
	 * @param array $fieldData
	 * @return array
	 */
	private function _loadFieldValue_upload_multiple(string $entryId, array $fieldData): array {
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
	private function _loadFieldSelection_selection(string $data): array {
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
	private function _setDefaults(): void {
		// default query options
		$options['limit'] = 5;
		$options['offset'] = false;
		$options['sort'] = false;
		$options['sortDirection'] = false;
		$this->setQueryOptions($options);
	}
}
