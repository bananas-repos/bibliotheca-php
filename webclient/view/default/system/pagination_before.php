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

/**
 * split pagination building in two parts to make it includeable
 * - pagination_before
 *   this one sets the defaults and checks for requestparams
 * - pagination_after
 *   this one gets the results from the queries and builds the required infos for the pagination view
 */

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
	'orderby' => $_sort,
	'sortDirection' => $_sortDirection
);
