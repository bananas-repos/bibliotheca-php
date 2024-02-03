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
 * Class GoogleBooks
 *
 * Search for book information with google books
 *
 * https://developers.google.com/books/docs/overview
 *
 * possible alternative if google does  limit the access: https://openlibrary.org/dev/docs/api/books
 */
class GoogleBooks {

	/**
	 * @var String The google api endpoint
	 */
	private string $_VOLUMES_ENDPOINT = 'https://www.googleapis.com/books/v1/volumes';

	/**
	 * @var bool DEBUG
	 */
	private bool $_DEBUG = false;

	/**
	 * @var string The user agent used to make curl calls
	 */
	private string $_BROWSER_AGENT = '';

	/**
	 * @var string The user agent lang used to make curl calls
	 */
	private string $_BROWSER_LANG = '';

	/**
	 * @var string The user agent accept used to make curl calls
	 */
	private string $_BROWSER_ACCEPT = '';

	public function __construct(array $options) {
		if(isset($options['debug']) && !empty($options['debug'])) {
			$this->_DEBUG = true;
		}

		$this->_BROWSER_AGENT = $options['browserAgent'];
		$this->_BROWSER_LANG = $options['browserLang'];
		$this->_BROWSER_ACCEPT = $options['browserAccept'];
	}

	/**
	 * Use a given ISBN and query the google books API with it.
	 * https://developers.google.com/books/docs/overview
	 * for example: https://www.googleapis.com/books/v1/volumes?q=isbn:9780812972153
	 */
	public function searchForISBN(string $isbn) : array {
		$data = array();

		if(!empty($isbn)) {
			$isbn = urlencode($isbn);
			$url = $this->_VOLUMES_ENDPOINT;
			$url .= '?q=isbn:'.$isbn;

			if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." isbn query url: $url");

			$do = $this->_curlCall($url);
			if(!empty($do)) {
				$data = json_decode($do, true);
				if(!empty($data)) {
					if(DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." isbn json data:".Summoner::cleanForLog($data));
					$data = $this->_buildDataFromISBNsearch($data);
				}
				else {
                    Summoner::sysLog("[ERROR] ".__METHOD__." invalid isbn json data:".Summoner::cleanForLog($do));
				}
			}

		}

		return $data;
	}

	/**
	 * Download given URL to a tmp file
	 * make sure to remove the tmp file after use
	 *
	 * @param string $url
	 * @return string
	 */
	public function downloadCover(string $url): string {
		$ret = '';

		// replace zoom=1 with zoom=0 or even remove to get the full picture
		// http://books.google.com/books/content?id=yyaxyKjyp2YC&printsec=frontcover&img=1&zoom=1&source=gbs_api

		$url = str_replace("zoom=1", "zoom=0",$url);

		$_tmpFile = tempnam(sys_get_temp_dir(), "bibliotheca-");
		$fh = fopen($_tmpFile,"w+");
		if($this->_DEBUG) {
            Summoner::sysLog('[DEBUG] '.__METHOD__.' url '.Summoner::cleanForLog($url));
		}

		if($fh !== false) {

			// modified curl call for fetching an image
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_FILE, $fh);

			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
			curl_setopt($ch, CURLOPT_USERAGENT, $this->_BROWSER_AGENT);

			curl_exec($ch);
			curl_close($ch);

			$ret = $_tmpFile;
		}
		fclose($fh);

		return $ret;
	}

	/**
	 * Use the given isb search data and build a nice return array
	 * Since the search is for a isbn, there should be only one result
	 *
	 * @param array $rawData
	 * @return array
	 */
	private function _buildDataFromISBNsearch(array $rawData) : array {
		$data = array();

		if(!empty($rawData) && isset($rawData['items'][0]['volumeInfo'])) {
			$_d = $rawData['items'][0]['volumeInfo'];

			$data['title'] = $_d['title'] ?? '';
			$data['subtitle'] = $_d['subtitle'] ?? '';
			$data['publisher'] = $_d['publisher'] ?? '';
			$data['publishedDate'] = $_d['publishedDate'] ?? '';
			$data['description'] = $_d['description'] ?? '';
			$data['authors'] = isset($_d['authors']) ? implode(",", $_d['authors']) : '';
			$data['categories'] = isset($_d['categories']) ? implode(",", $_d['categories']) : '';
			$data['cover'] = $_d['imageLinks']['thumbnail'] ?? '';

			$data['isbn'] = '';
			if(isset($_d['industryIdentifiers']) && is_array($_d['industryIdentifiers'])) {
				foreach($_d['industryIdentifiers'] as $k=>$v) {
					if($v['type'] == "ISBN_13") {
						$data['isbn'] = $v['identifier'];
					}
				}
			}
		}

		return $data;
	}

	/**
	 * execute a curl call to the given $url
	 *
	 * @param string $url The request url
	 * @return string
	 */
	private function _curlCall(string $url): string {
		$ret = '';

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->_BROWSER_AGENT);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Accept: '.$this->_BROWSER_ACCEPT,
				'Accept-Language: '.$this->_BROWSER_LANG)
		);

		$_headers = array();
		if($this->_DEBUG) {
			curl_setopt($ch, CURLOPT_VERBOSE, true);

			curl_setopt($ch, CURLOPT_HEADERFUNCTION,
				function($curl, $header) use (&$_headers) {
					$len = strlen($header);
					$header = explode(':', $header, 2);
					if (count($header) < 2) { // ignore invalid headers
						return $len;
					}
					$_headers[strtolower(trim($header[0]))][] = trim($header[1]);
					return $len;
				}
			);
		}

		$do = curl_exec($ch);
		if(is_string($do) === true) {
			$ret = $do;
		}
		curl_close($ch);

		if($this->_DEBUG) {
            Summoner::sysLog('[DEBUG] '.__METHOD__.' headers '.Summoner::cleanForLog($_headers));
		}

		return $ret;
	}

}
