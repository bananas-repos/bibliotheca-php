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

require_once 'lib/mancubus.class.php';
$Mancubus = new Mancubus($DB,$Doomguy);
require_once 'lib/trite.class.php';
$Trite = new Trite($DB,$Doomguy);

$_collection = false;
if(isset($_GET['collection']) && !empty($_GET['collection'])) {
	$_collection = trim($_GET['collection']);
	$_collection = Summoner::validate($_collection,'digit') ? $_collection : false;
}

// field identifier to search within
$_fid = false;
if(isset($_GET['fid']) && !empty($_GET['fid'])) {
	$_fid = trim($_GET['fid']);
	$_fid = Summoner::validate($_fid,'nospace') ? $_fid : false;
}

// field value to look up
$_fv = false;
if(isset($_GET['fv']) && !empty($_GET['fv'])) {
	$_fv = trim($_GET['fv']);
	$_fv = Summoner::validate($_fv) ? $_fv : false;
}

$_search = false;
if(isset($_POST['navSearch'])) {
	$_search = trim($_POST['navSearch']);
	$_search = Summoner::validate($_search) ? $_search :  false;
}

## pagination
$TemplateData['pagination'] = array('pages' => 0);

$_curPage = 1;
if(isset($_GET['page']) && !empty($_GET['page'])) {
	$_curPage = trim($_GET['page']);
	$_curPage = Summoner::validate($_curPage,'digit') ? $_curPage : 1;
}

$_sort = false;
if(isset($_GET['s']) && !empty($_GET['s'])) {
	$_sort = trim($_GET['s']);
	$_sort = Summoner::validate($_sort,'nospace') ? $_sort : false;
}

$_sortDirection = false;
if(isset($_GET['sd']) && !empty($_GET['sd'])) {
	$_sortDirection = trim($_GET['sd']);
	$_sortDirection = Summoner::validate($_sortDirection,'nospace') ? $_sortDirection : false;
}

$_queryOptions = array(
	'limit' => RESULTS_PER_PAGE,
	'offset' => (RESULTS_PER_PAGE * ($_curPage-1)),
	'sort' => $_sort,
	'sortDirection' => $_sortDirection
);
## pagination end

$TemplateData['pageTitle'] = "Collection overview";
$TemplateData['loadedCollection'] = array();
$TemplateData['storagePath'] = '';
$TemplateData['entries'] = array();
$TemplateData['collections'] = array();
$TemplateData['search'] = false;
// needed for pagination link building
$TemplateData['pagination']['currentGetParameters']['p'] = 'collections';
$TemplateData['pagination']['currentGetParameters']['collection'] = $_collection;

if(!empty($_collection)) {
	$TemplateData['loadedCollection'] = $Trite->load($_collection);
	if(!empty($TemplateData['loadedCollection'])) {
		$Mancubus->setCollection($Trite->param('id'));

		$TemplateData['defaultSortField'] = $defaultSortField = $Trite->param('defaultSortField');
		$TemplateData['simpleSearchFields'] = $Trite->getSimpleSearchFields();
		if(!empty($TemplateData['defaultSortField'])) {
			unset($TemplateData['simpleSearchFields'][$TemplateData['defaultSortField']]);
			if(empty($_queryOptions['sort'])) {
				$_queryOptions['sort'] = $TemplateData['defaultSortField'];
			}
		}
		$Mancubus->setQueryOptions($_queryOptions);

		$TemplateData['storagePath'] = PATH_WEB_STORAGE . '/' . $Trite->param('id');
		$TemplateData['entryLinkPrefix'] = "index.php?p=entry&collection=".$Trite->param('id');
		$TemplateData['searchAction'] = 'index.php?p=collections&collection='.$Trite->param('id');

		$_fd = $Trite->getCollectionFields();

		$_sdata = array();
		if (!empty($_fv) && !empty($_fid)) {
			$_sdata[0] = array(
				'colName' => $_fd[$_fid]['identifier'],
				'colValue' => $_fv,
				'fieldData' => $_fd[$_fid],
				'exactTagMatch' => true
			);
			$_search = $_fv;
			$TemplateData['pagination']['currentGetParameters']['fid'] = $_fid;
			$TemplateData['pagination']['currentGetParameters']['fv'] = $_fv;
		}
		elseif(isset($_fd[$Trite->param('defaultSearchField')])) {
			$_sdata[0] = array(
				'colName' => $Trite->param('defaultSearchField'),
				'colValue' => $_search,
				'fieldData' =>$_fd[$Trite->param('defaultSearchField')]
			);
		}

		$TemplateData['entries'] = $Mancubus->getEntries($_sdata);
		if (!empty($_search)) {
			$TemplateData['search'] = $_search;
		}

		$TemplateData['pageTitle'] = $Trite->param('name');

	}
	else {
		$TemplateData['message']['content'] = "Can not load given collection.";
		$TemplateData['message']['status'] = "error";
	}
}
else {
	$TemplateData['collections'] = $Trite->getCollections();
}

# pagination
if(!empty($TemplateData['entries']['amount'])) {
	$TemplateData['pagination']['pages'] = ceil($TemplateData['entries']['amount'] / RESULTS_PER_PAGE);
	$TemplateData['pagination']['curPage'] = $_curPage;

	$TemplateData['pagination']['currentGetParameters']['page'] = $_curPage;
	$TemplateData['pagination']['currentGetParameters']['s'] = $_sort;
	$TemplateData['pagination']['currentGetParameters']['sd'] = $_sortDirection;
}

if($TemplateData['pagination']['pages'] > 11) {
	# first pages
	$TemplateData['pagination']['visibleRange'] = range(1,3);
	# last pages
	foreach(range($TemplateData['pagination']['pages']-2, $TemplateData['pagination']['pages']) as $e) {
		array_push($TemplateData['pagination']['visibleRange'], $e);
	}
	# pages before and after current page
	$cRange = range($TemplateData['pagination']['curPage']-1, $TemplateData['pagination']['curPage']+1);
	foreach($cRange as $e) {
		array_push($TemplateData['pagination']['visibleRange'], $e);
	}
	$TemplateData['pagination']['currentRangeStart'] = array_shift($cRange);
	$TemplateData['pagination']['currentRangeEnd'] = array_pop($cRange);
}
else {
	$TemplateData['pagination']['visibleRange'] = range(1,$TemplateData['pagination']['pages']);
}
# pagination end
