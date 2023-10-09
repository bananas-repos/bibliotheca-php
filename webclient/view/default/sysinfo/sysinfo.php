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
