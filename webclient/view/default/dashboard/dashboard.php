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
 *  along with this program. If not, see http://www.gnu.org/licenses/gpl-3.0
 */

require_once 'lib/mancubus.class.php';
$Mancubus = new Mancubus($DB,$Doomguy);

$TemplateData['search'] = false;

$_search = '';
if(isset($_GET['navSearch'])) {
	$_search = trim($_GET['navSearch']);
	$_search = urldecode($_search);
	$_search = Summoner::validate($_search,'text') ? $_search : '';
	$TemplateData['navSearched'] = '&navSearch='.urlencode($_search);
}

$TemplateData['latest'] = $Mancubus->getLatest(5,6,$_search);
if (!empty($_search)) {
	$TemplateData['search'] = $_search;
}
