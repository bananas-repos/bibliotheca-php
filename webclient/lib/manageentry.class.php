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

class Manageentry {
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
	 * Currently loaded collection to manage entries from
	 *
	 * @var string Number
	 */
	private string $_collectionId;

	/**
	 * Placeholder in query strings for inserted DB id
	 *
	 * @var string
	 */
	private string $_replaceEntryString = 'REPLACE_ENTERY';

	/**
	 * Store edit fields info for runtime
	 *
	 * @var array
	 */
	private array $_cacheEditFields = array();

	/**
	 * ManageCollections constructor.
	 *
	 * @param mysqli $databaseConnectionObject
	 * @param Doomguy $userObj
	 */
	public function __construct(mysqli $databaseConnectionObject, Doomguy $userObj) {
		$this->_DB = $databaseConnectionObject;
		$this->_User = $userObj;
	}

	/**
	 * Set the collection to manage entries from
	 *
	 * @param string $collectionId Number
	 */
	public function setCollection(string $collectionId) {
		if(!empty($collectionId)) {
			$this->_collectionId = $collectionId;
		}
	}

	/**
	 * Load the fields for the loaded collection
	 * Also load additional data based on fieldtype and _loadField_ method
	 *
	 * @param bool $refresh
	 * @return array
	 */
	public function getEditFields(bool $refresh=false): array {

		if($refresh === false && !empty($this->_cacheEditFields)) {
			return $this->_cacheEditFields;
		}

		if(!empty($this->_collectionId)) {
			$queryStr = "SELECT `cf`.`fk_field_id` AS id, `sf`.`type`, `sf`.`displayname`, `sf`.`identifier`,
							`sf`.`value`, `sf`.`inputValidation`
						FROM `".DB_PREFIX."_collection_fields_".$this->_DB->real_escape_string($this->_collectionId)."` AS cf
						LEFT JOIN `".DB_PREFIX."_sys_fields` AS sf ON `cf`.`fk_field_id` = `sf`.`id`
						ORDER BY `cf`.`sort`";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {
					while(($result = $query->fetch_assoc()) != false) {
						$_methodName = '_loadField_'.$result['type'];
						if(method_exists($this, $_methodName)) {
							$result = $this->$_methodName($result);
						}
						$this->_cacheEditFields[$result['id']] = $result;
					}
				}
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
			}
		}

		return $this->_cacheEditFields;
	}

	/**
	 * Load required data for edit. Uses some functions from Mancubus but has
	 * different data layout. Checks write edit too
	 *
	 * @param string $entryId Number
	 * @return array
	 */
	public function getEditData(string $entryId): array {
		$ret = array();

		if(!empty($this->_collectionId) && !empty($entryId)) {
			$queryStr = "SELECT * 
						FROM `".DB_PREFIX."_collection_entry_".$this->_DB->real_escape_string($this->_collectionId)."` 
						WHERE ".$this->_User->getSQLRightsString("write")."
						AND `id` = '".$this->_DB->real_escape_string($entryId)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);

				if($query !== false && $query->num_rows > 0) {
					$_entryFields = $this->getEditFields();

					if(($result = $query->fetch_assoc()) != false) {
						$ret = $this->_mergeEntryWithFields($result, $_entryFields);
						$ret['rights'] = Summoner::prepareRightsArray($result['rights']);
						$ret['_canDelete'] = $this->_canDelete($entryId);
						$ret['_isOwner'] = $this->_isOwner($result);
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
	 * Create an entry with given data
	 *
	 * @param array $data
	 * @param string $owner Number
	 * @param string $group Number
	 * @param string $rights
	 * @param mixed|false $update Either false for no update or the ID to update
	 * @return int
	 */
	public function create(array $data, string $owner, string $group, string $rights, mixed $update=false): int {
		$ret = 0;

		if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." data: ".Summoner::cleanForLog($data));
		if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." update: ".Summoner::cleanForLog($update));

		if(!empty($data) && !empty($owner) && !empty($group) && !empty($rights)) {

			// create the queryData array
			// init is the entry in the table. Needed for after stuff
			// after returns query and upload which then calls the extra methods
			$queryData['init'] = array();
			$queryData['after'] = array();
			foreach ($data as $i=>$d) {
				$_methodName = '_saveField_'.$d['type'];
                $_methodNameSpecial = $_methodName.'__'.$d['identifier'];
                if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." methodname: ".Summoner::cleanForLog($_methodName));
                if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." methodnamespecial: ".Summoner::cleanForLog($_methodNameSpecial));
                if(method_exists($this, $_methodNameSpecial)) {
                    $queryData = $this->$_methodNameSpecial($d, $queryData);
                }
				elseif(method_exists($this, $_methodName)) {
					$queryData = $this->$_methodName($d, $queryData);
				}
				else {
					if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." Missing query function for: ".Summoner::cleanForLog($d));
				}
			}

			if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." queryData: ".Summoner::cleanForLog($queryData));

			if(!empty($queryData['init']) || ($update !== false && is_numeric($update))) {

				$queryStr = "INSERT INTO `".DB_PREFIX."_collection_entry_".$this->_collectionId."`
								SET `modificationuser` = '".$this->_DB->real_escape_string($owner)."',
								`owner` = '".$this->_DB->real_escape_string($owner)."',
								`group` = '".$this->_DB->real_escape_string($group)."',
								`rights`= '".$this->_DB->real_escape_string($rights)."',";
				if($update !== false && is_numeric($update)) {
					$queryStr = "UPDATE `".DB_PREFIX."_collection_entry_".$this->_collectionId."`
								SET `modificationuser` = '".$this->_DB->real_escape_string($owner)."',
								`rights`= '".$this->_DB->real_escape_string($rights)."',";
				}
				$queryStr .= implode(", ",$queryData['init']);
				$queryStr = trim($queryStr,",");
				if($update !== false && is_numeric($update)) {
					$queryStr .= " WHERE `id` = '".$this->_DB->real_escape_string($update)."'";
				}

				if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));

				try {
					$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

					$this->_DB->query($queryStr);

					if($update !== false && is_numeric($update)) {
						$newId = $update;
					}
					else {
						$newId = $this->_DB->insert_id;
					}

					if(!empty($newId)) {
						if(!empty($queryData['after']) && isset($queryData['after']['query'])) {
							foreach ($queryData['after']['query'] as $q) {
								$this->_runAfter_query($q, $newId);
							}
						}

						if(!empty($queryData['after']) && isset($queryData['after']['upload'])) {
							foreach ($queryData['after']['upload'] as $q) {
								$this->_runAfter_upload($q, $newId);
							}
						}
					}
					else {
						throw new Exception('Failed to create entry');
					}

					$ret = $newId;
					$this->_DB->commit();
				}
				catch (Exception $e) {
					$this->_DB->rollback();
                    Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
				}
			}
			else {
				if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." empty init in: ".Summoner::cleanForLog($queryData));
			}
		}

		return $ret;
	}

	/**
	 * Delete given entryId from currently loaded collection
	 * Checks userrights too.
	 *
	 * @param string $entryId Number
	 * @return bool
	 */
	public function delete(string $entryId): bool {
		$ret = false;

		if(!empty($entryId) && !empty($this->_collectionId)) {

			if ($this->_canDelete($entryId)) {
				try {
					$this->_DB->begin_transaction(MYSQLI_TRANS_START_READ_WRITE);

					// remove assets
					$_path = PATH_STORAGE.'/'.$this->_collectionId.'/'.$entryId;
					if(is_dir($_path) && is_readable($_path)) {
						if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__."  remove assets :".$_path);
						$rmDir = Summoner::recursive_remove_directory($_path);
						if($rmDir === false) {
							throw new Exception("Failed to delete path: ".$_path);
						}
					}

					// delete data from lookup fields
					$queryStr = "DELETE 
						FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
						WHERE `fk_entry` = '".$this->_DB->real_escape_string($entryId)."'";
					if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." remove lookup queryStr: ".Summoner::cleanForLog($queryStr));
					if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
					$this->_DB->query($queryStr);

					// delete entry
					$queryStr = "DELETE
						FROM `".DB_PREFIX."_collection_entry_".$this->_collectionId."`
						WHERE `id` = '".$this->_DB->real_escape_string($entryId)."'
							AND " . $this->_User->getSQLRightsString("delete") . "";
					if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
					$this->_DB->query($queryStr);

					$this->_DB->commit();
					$ret = true;
				}
				catch (Exception $e) {
					$this->_DB->rollback();
                    Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
				}
			}
		}

		return $ret;
	}

	/**
	 * Validates that current use can write the given Entry
	 *
	 * @param string $entryId Number
	 * @return bool
	 */
	public function canEditEntry(string $entryId): bool {
		$ret = false;

		if(!empty($entryId) && !empty($this->_collectionId)) {

			$queryStr = "SELECT `id`
						FROM `".DB_PREFIX."_collection_entry_".$this->_collectionId."`
						WHERE `id` = '".$this->_DB->real_escape_string($entryId)."'
							AND ".$this->_User->getSQLRightsString("write")."";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows > 0) {
					if (($result = $query->fetch_assoc()) != false) {
						$ret = true;
					}
				}
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Check if given entryid can be deleted from current collection
	 * and user
	 *
	 * @param string $entryId Number
	 * @return bool
	 */
	private function _canDelete(string $entryId): bool {
		$ret = false;

		if(!empty($entryId) && !empty($this->_collectionId)) {

			$queryStr = "SELECT `id`
						FROM `".DB_PREFIX."_collection_entry_".$this->_collectionId."`
						WHERE `id` = '".$this->_DB->real_escape_string($entryId)."'
							AND ".$this->_User->getSQLRightsString("delete")."";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows > 0) {
					if (($result = $query->fetch_assoc()) != false) {
						$ret = true;
					}
				}
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
			}
		}

		return $ret;
	}

	/**
	 * Merge the loaded entryData with the to look up entryFields data
	 * In this case only the fields which have a _loadFieldValue_ method
	 * are loaded. More is not needed here.
	 *
	 * @param array $entryData
	 * @param array $entryFields
	 * @return array
	 */
	private function _mergeEntryWithFields(array $entryData, array $entryFields): array {
		if(!empty($entryFields)) {
			foreach($entryFields as $f) {
				$_mnValue = '_loadFieldValue_'.$f['type'];

				if(!isset($entryData[$f['identifier']]) && method_exists($this, $_mnValue) && isset($entryData['id']) ) {
					$entryData[$f['identifier']] = $this->$_mnValue($entryData['id'], $f);
				}
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
	 * @see Mancubus
	 */
	private function _loadFieldValue_lookupmultiple(string $entryId, array $fieldData): array {
		$ret = array();

		if(!empty($entryId) && !empty($fieldData) && !empty($this->_collectionId)) {
			$queryStr = "SELECT `value` 
						FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
						WHERE `fk_field` = '".$this->_DB->real_escape_string($fieldData['id'])."'
							AND `fk_entry` = '".$this->_DB->real_escape_string($entryId)."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if($query !== false && $query->num_rows > 0) {
					while(($result = $query->fetch_assoc()) != false) {
						$ret[] = $result['value'];
					}
				}
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
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
	 * @see Mancubus
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
	 * @see Mancubus
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
	 * Provide the options for a selection field by processing the $data['value']
	 * since the values are stored in the entry DB as a list
	 *
	 * @param array $data
	 * @return array
	 */
	private function _loadField_selection(array $data): array {
		if(!empty($data) && isset($data['value']) && !empty($data['value'])) {
			if(strstr($data['value'], ",")) {
				$data['options'] = explode(",", $data['value']);
			}
		}
		return $data;
	}

	/**
	 * Load suggestions based on the existing data for this field
	 *
	 * @param array $data Field data
	 * @return array
	 */
	private function _loadField_lookupmultiple(array $data): array {
		if(!empty($data) && isset($data['id']) && !empty($data['id'])) {
			$queryStr = "SELECT DISTINCT(`value`) 
						FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_DB->real_escape_string($this->_collectionId)."`
						WHERE `fk_field` = '".$this->_DB->real_escape_string($data['id'])."'";
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$query = $this->_DB->query($queryStr);
				if ($query !== false && $query->num_rows > 0) {
					while (($result = $query->fetch_assoc()) != false) {
						$data['suggestion'][] = $result['value'];
					}
				}
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
			}
		}
		return $data;
	}

	/**
	 * Create part of the insert statement for field type text
	 *
	 * @param array $data Field data
	 * @param array $queryData Query data array
	 * @return array
	 */
	private function _saveField_text(array $data, array $queryData): array {
		$queryData['init'][] = "`".$data['identifier']."` = '".$this->_DB->real_escape_string($data['valueToSave'])."'";
		return $queryData;
	}

	/**
	 * Create part of the insert statement for field type text3
	 *
	 * @param array $data Field data
	 * @param array $queryData Query data array
	 * @return array
	 */
	private function _saveField_text3(array $data, array $queryData): array {
		return $this->_saveField_text($data, $queryData);
	}

	/**
	 * Create part of the insert statement for field type textarea
	 *
	 * @param array $data Field data
	 * @param array $queryData Query data array
	 * @return array
	 */
	private function _saveField_textarea(array $data, array $queryData): array {
		return $this->_saveField_text($data, $queryData);
	}

	/**
	 * Create part of the insert statement for field type selection
	 *
	 * @param array $data Field data
	 * @param array $queryData Query data array
	 * @return array
	 */
	private function _saveField_selection(array $data, array $queryData): array {
		return $this->_saveField_text($data, $queryData);
	}

	/**
	 * Create part of the insert statement for field type year
	 * Uses some simple 4 digit patter to extract the year if the input is
	 * something like 2001-02-03
	 *
	 * @param array $data Field data
	 * @param array $queryData Query data array
	 * @return array
	 */
	private function _saveField_year(array $data, array $queryData): array {
		preg_match('/[0-9]{4}/', $data['valueToSave'], $matches);
		if(isset($matches[0]) && !empty($matches[0])) {
			$data['valueToSave'] = $matches[0];
		}
		return $this->_saveField_number($data, $queryData);
	}

	/**
	 * Create part of the insert statement for field type number
	 * Strips everything what is not a digit from it.
	 *
	 * @param array $data
	 * @param array $queryData
	 * @return mixed
	 */
	private function _saveField_number(array $data, array $queryData): array {
		// make sure there is something (int) to save
		if(empty($data['valueToSave'])) {
			$data['valueToSave'] = 0;
		}
		$data['valueToSave'] = preg_replace('/[^\p{N}]/u', '', $data['valueToSave']);
		$queryData['init'][] = "`".$data['identifier']."` = '".$this->_DB->real_escape_string($data['valueToSave'])."'";
		return $queryData;
	}

	/**
	 * Create part of the insert statement for field type lookupmultiple
	 *
	 * @param array $data Field data
	 * @param array $queryData Query data array
	 * @return array
	 */
	private function _saveField_lookupmultiple(array $data, array $queryData): array {
		$_d = trim($data['valueToSave']);
		$_d = trim($_d, ",");

		// first clean since the new data is everything
		$queryData['after']['query'][] = "DELETE FROM `".DB_PREFIX."_collection_entry2lookup_".$this->_collectionId."`
										WHERE `fk_field` = '".$this->_DB->real_escape_string($data['id'])."'
											AND `fk_entry` = '".$this->_replaceEntryString."'";
		if(!empty($_d)) {
			$_process = array($_d);
			if (strstr($data['valueToSave'], ",")) {
				$_process = explode(",", $data['valueToSave']);
			}
			foreach ($_process as $p) {
				$queryData['after']['query'][] = "INSERT IGNORE INTO `".DB_PREFIX."_collection_entry2lookup_".$this->_collectionId."`
								SET `fk_field` = '".$this->_DB->real_escape_string($data['id'])."',
									`fk_entry` = '".$this->_replaceEntryString."',
									`value` = '".$this->_DB->real_escape_string($p)."'";
			}
		}

		return $queryData;
	}

	/**
	 * Single upload field
	 *
	 * @param array $data The data from _FILES
	 * @param array $queryData
	 * @return array
	 */
	private function _saveField_upload(array $data, array $queryData): array {
		$_up = $data['uploadData'];

		// delete the single upload
		// this way the after query method is triggered without any upload
		if(isset($data['deleteData'])) {
			$queryData['after']['upload'][] = array(
				'identifier' => $data['identifier'],
				'multiple' => false,
				'deleteData' => $data['deleteData']
			);
		}

		if($_up['error'][$data['identifier']] === 0) {
			$_ext = pathinfo($_up['name'][$data['identifier']],PATHINFO_EXTENSION);
			$newFilename = sha1($_up['name'][$data['identifier']]).".".$_ext;

			if(!isset($_up['rebuildUpload'][$data['identifier']])) {
				$_up['rebuildUpload'][$data['identifier']] = false;
			}

			$queryData['after']['upload'][] = array(
				'identifier' => $data['identifier'],
				'name' => $newFilename,
				'tmp_name' => $_up['tmp_name'][$data['identifier']],
				'multiple' => false,
				'rebuildUpload' => $_up['rebuildUpload'][$data['identifier']]
			);
		}

		return $queryData;
	}

	/**
	 * Multiple upload field
	 *
	 * @param array $data The data from _FILES
	 * @param array $queryData
	 * @return array
	 */
	private function _saveField_upload_multiple(array $data, array $queryData): array {
		$_up = $data['uploadData'];

		if(isset($data['deleteData'])) {
			$queryData['after']['upload'][] = array(
				'identifier' => $data['identifier'],
				'multiple' => true,
				'deleteData' => $data['deleteData']
			);
		}

		foreach ($_up['error'][$data['identifier']] as $k=>$v) {
			if($v === 0) {
				$_ext = pathinfo($_up['name'][$data['identifier']][$k],PATHINFO_EXTENSION);
				$newFilename = sha1($_up['name'][$data['identifier']][$k]).".".$_ext;

				if(!isset($_up['rebuildUpload'][$data['identifier']][$k])) {
					$_up['rebuildUpload'][$data['identifier']][$k] = false;
				}

				$queryData['after']['upload'][] = array(
					'identifier' => $data['identifier'],
					'name' => $newFilename,
					'tmp_name' => $_up['tmp_name'][$data['identifier']][$k],
					'multiple' => true,
					'rebuildUpload' => $_up['rebuildUpload'][$data['identifier']][$k]
				);
			}
		}

		return $queryData;
	}

    /**
     * Special for single upload and subtype coverimage.
     * Uses the theme settings for image resize. Modifies the result from _saveField_upload if it is an image
     *
     * @param array $data
     * @param array $queryData
     * @return array
     */
    private function _saveField_upload__coverimage(array $data, array $queryData): array {
        $queryData = $this->_saveField_upload($data, $queryData);

        $workWith = $queryData['after']['upload'][0]['tmp_name'];
        if(file_exists($workWith)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $workWith);
            finfo_close($finfo);
            if(str_contains('image/jpeg, image/png, image/webp', $mime)) {
                list($width, $height) = getimagesize($workWith);
                $_maxThemeWidth = Summoner::themeConfig('coverImageMaxWidth', UI_THEME);
                if(!empty($_maxThemeWidth) && ($width > $_maxThemeWidth)) {
                    $_ratio = $_maxThemeWidth/$width;
                    $newWidth = (int) $_maxThemeWidth;
                    $newHeight = (int) $height * $_ratio;
                    if(DEBUG)Summoner::sysLog("[DEBUG] ".__METHOD__." image ratio: ".$_ratio);
                    if(DEBUG)Summoner::sysLog("[DEBUG] ".__METHOD__." image width: ".$width);
                    if(DEBUG)Summoner::sysLog("[DEBUG] ".__METHOD__." image height: ".$height);
                    if(DEBUG)Summoner::sysLog("[DEBUG] ".__METHOD__." image new width: ".$newWidth);
                    if(DEBUG)Summoner::sysLog("[DEBUG] ".__METHOD__." image new height: ".$newHeight);
                    $_tmp_image = imagecreatetruecolor($newWidth, $newHeight);
                    switch($mime) {
                        case 'image/jpeg':
                            $src = imagecreatefromjpeg($workWith);
                            imagecopyresampled($_tmp_image, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                            imagejpeg($_tmp_image, $workWith, 100);
                            break;

                        case 'image/png':
                            $src = imagecreatefrompng($workWith);
                            imagecopyresampled($_tmp_image, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                            imagepng($_tmp_image, $workWith, 0);
                            break;

                        case 'image/webp':
                            $src = imagecreatefromwebp($workWith);
                            imagecopyresampled($_tmp_image, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                            imagewebp($_tmp_image, $workWith,100);
                            break;
                    }
                    imagedestroy($_tmp_image);
                    imagedestroy($src);
                }
            }
        }

        return $queryData;
    }

	/**
	 * runs the query and throws query exception if false
	 *
	 * @param string $queryString
	 * @param string $insertId Number
	 */
	private function _runAfter_query(string $queryString, string $insertId): void {
		if(!empty($queryString) && !empty($insertId)) {
			// replace only once to avoid replacing actual data
			$queryStr = Summoner::replaceOnce($queryString,$this->_replaceEntryString, $insertId);
			if(QUERY_DEBUG) Summoner::sysLog("[QUERY] ".__METHOD__." query: ".Summoner::cleanForLog($queryStr));
			try {
				$this->_DB->query($queryStr);
			}
			catch (Exception $e) {
                Summoner::sysLog("[ERROR] ".__METHOD__."  mysql catch: ".$e->getMessage());
			}
		}
	}

	/**
	 * Move uploaded into right directory
	 * If single upload (multiple=false) then remove all the files for this type field first. Works the same
	 * if you want to remove the upload via edit
	 *
	 * Also removes the defined uploads from multiple upload field
	 *
	 * @param array $uploadData
	 * @param string $insertId Number
	 * @throws Exception
	 */
	private function _runAfter_upload(array $uploadData, string $insertId): void {
		if(!empty($uploadData) && !empty($insertId)) {
			if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." uploadata: ".Summoner::cleanForLog($uploadData));
			$_path = PATH_STORAGE.'/'.$this->_collectionId.'/'.$insertId;
			if(!is_dir($_path)) {
				if(!mkdir($_path, 0777, true)) {
					throw new Exception("Failed to create storage path: ".$_path);
				}
			}

			if($uploadData['multiple'] === false) {
				// single upload. Delete existing first.
				// also triggered if the single needs to be deleted
				$_existingFiles = glob($_path.'/'.$uploadData['identifier'].'-*');
				if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." remove single existing: ".Summoner::cleanForLog($_existingFiles));
				if(!empty($_existingFiles)) {
					foreach ($_existingFiles as $f) {
						unlink($f);
					}
					clearstatcache();
				}
			}

			if($uploadData['multiple'] === true && isset($uploadData['deleteData'])) {
				if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." remove multiple existing: ".Summoner::cleanForLog($uploadData['deleteData']));
				foreach ($uploadData['deleteData'] as $k=>$v) {
					$_file = $_path.'/'.$v;
					if(file_exists($_file)) {
						unlink($_file);
					}
					clearstatcache();
				}
			}

			if(isset($uploadData['tmp_name']) && isset($uploadData['name'])) {
				// special case if the image is already uploaded and not a real POST/FILES request
				if(isset($uploadData['rebuildUpload']) && $uploadData['rebuildUpload'] === true) {
					if(!rename($uploadData['tmp_name'],$_path.'/'.$uploadData['identifier'].'-'.$uploadData['name'])) {
						throw new Exception("Can not rename file to: ".$_path.'/'.$uploadData['identifier'].'-'.$uploadData['name']);
					}
				}
				elseif(!move_uploaded_file($uploadData['tmp_name'],$_path.'/'.$uploadData['identifier'].'-'.$uploadData['name'])) {
					throw new Exception("Can not move file to: ".$_path.'/'.$uploadData['identifier'].'-'.$uploadData['name']);
				}
			}
		}
	}


	/**
	 * If the given entry has the current user as its owner
	 * or if root
	 *
	 * @param $data array The entry data from getEditData
	 * @return bool
	 */
	private function _isOwner(array $data): bool {
		$ret = false;

		if($this->_User->param('isRoot')) {
			$ret = true;
		}
		elseif($data['owner'] == $this->_User->param('id')) {
			$ret = true;
		}

		return $ret;
	}
}
