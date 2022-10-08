<?php
/**
* Bibliotheca
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

$TemplateData['bibVersion'] = BIB_VERSION;
$TemplateData['apacheVersion'] = apache_get_version();
$TemplateData['phpVersion'] = phpversion();
$TemplateData['mysqlVersion'] = mysqli_get_server_info($DB);

$overallTableSize = 0; // MB
$queryStr = "SELECT (DATA_LENGTH + INDEX_LENGTH) AS `size`
						FROM information_schema.TABLES
						WHERE TABLE_SCHEMA = 'bibliotheca'
						ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC";
if(QUERY_DEBUG) error_log("[QUERY] ".__METHOD__." query: ".var_export($queryStr,true));
try {
	$query = $DB->query($queryStr);
	if($query !== false && $query->num_rows > 0) {
		while(($result = $query->fetch_assoc()) != false) {
			$overallTableSize += $result['size'];
		}
	}
}
catch (Exception $e) {
	error_log("[ERROR] ".__METHOD__." mysql catch: ".$e->getMessage());
}
$TemplateData['overallTableSize'] = Summoner::bytesToHuman($overallTableSize);

$TemplateData['overallStorageSize'] = Summoner::bytesToHuman(Summoner::folderSize(PATH_STORAGE));

require_once 'lib/trite.class.php';
$Trite = new Trite($DB,$Doomguy);
$TemplateData['existingCollections'] = $Trite->getCollections("write");
foreach($TemplateData['existingCollections'] as $k=>$v) {
	$Trite->load($k);
	$TemplateData['existingCollections'][$k] = $Trite->getStats();
}
