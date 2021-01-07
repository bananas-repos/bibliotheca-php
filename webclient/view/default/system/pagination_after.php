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

// this is comes from pagination_before
// $TemplateData['pagination']

if(!empty($TemplateData['entries']['amount'])) {
	$TemplateData['pagination']['pages'] = ceil($TemplateData['entries']['amount'] / RESULTS_PER_PAGE);
	$TemplateData['pagination']['curPage'] = $_curPage;

	$TemplateData['pagination']['currentGetParameters']['page'] = $_curPage;
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
