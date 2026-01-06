<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2026 Johannes Keßler
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

/**
 * Class IMDBWEB
 *
 * Based on the idea of  https://github.com/FabianBeiner/PHP-IMDB-Grabber/
 * But since the web srcaping does not work anymore, here is an own implementation
 *
 * Main and important action is at _processData
 */
class IMDBWEB {
    /**
     * Set this to true if you run into problems.
     */
    private bool $_DEBUG = false;

    /**
     * @var string The user agent used to make curl calls
     */
    private mixed $_BROWSER_AGENT = '';

    /**
     * @var string The user agent lang used to make curl calls
     */
    private mixed $_BROWSER_LANG = '';

    /**
     * @var string The user agent accept used to make curl calls
     */
    private mixed $_BROWSER_ACCEPT = '';

    /**
     * This is used to get the base informations
     *
     * A good start for further URLs is: https://www.imdb.com/title/%s/reference/
     * @var string The imdb URL to get the HTML from
     */
    private string $_FULLCREDITS_URL = 'https://www.imdb.com/title/%s/fullcredits/';

    /**
     * This is used to get more text
     * Not implemented yet.
     *
     * A good start for further URLs is: https://www.imdb.com/title/%s/reference/
     * @var string The imdb URL to get the HTML from
     */
    private string $_REFERENCE_URL = "https://www.imdb.com/title/%s/reference/";

    /**
     * @var string The directory to store cache files
     */
    private string $_CACHE_DIR = "";

    /**
     * @var int Maximum cache time.
     */
    private int $_CACHE_TIME = 1440;

    /**
     * @var array The fields to return
     */
    private array $_attributes;

    /**
     * ImdbWeb constructor
     *
     * @param array $options
     */
    public function __construct(array $options) {
        if (isset($options['debug']) && !empty($options['debug'])) {
            $this->_DEBUG = true;
        }

        $this->_CACHE_DIR = $options['storage'];
        if (!is_writable($this->_CACHE_DIR) && !mkdir($this->_CACHE_DIR)) {
            Summoner::sysLog("[ERROR] Missing directory or write access: ".$this->_CACHE_DIR);
        }

        $this->_BROWSER_AGENT = $options['browserAgent'];
        $this->_BROWSER_LANG = $options['browserLang'];
        $this->_BROWSER_ACCEPT = $options['browserAccept'];

        $this->_attributes = array();
        if (isset($options['attributes']) && !empty($options['attributes'])) {
            $this->_attributes = $options['attributes'];
        }
    }

    /**
     * Not really a search. Since this does only really work with a given imdb ID an exact
     * URL is called and processed.
     * Decides if the data will called of cache is used.
     * Does return the data based on $this->_attributes
     *
     * @param string $search
     * @return array
     */
    public function search(string $search): array {
        $ret = array();

        if (empty($search)) return $ret;
        $url = sprintf($this->_FULLCREDITS_URL, $search);
        if ($this->_DEBUG) Summoner::sysLog("[DEBUG] ".__METHOD__." using url : " . $url);

        // Does a cache of this movie exist?
        $_cacheFile = $this->_CACHE_DIR.'/'.md5($url).'.cache';
        $_htmlData = "";
        if (is_readable($_cacheFile)) {
            $_timeDiff = round(abs(time() - filemtime($_cacheFile)) / 60);
            if (($_timeDiff < $this->_CACHE_TIME) && !$this->_DEBUG) {
                $_htmlData = file_get_contents($_cacheFile);
            } else {
                $_htmlData = $this->_curlCall($url);
                if (!empty($_htmlData)) {
                    file_put_contents($_cacheFile, $_htmlData);
                }
            }
        } else {
            $_htmlData = $this->_curlCall($url);
            if (!empty($_htmlData)) {
                file_put_contents($_cacheFile, $_htmlData);
            }
        }

        if(!empty($_htmlData)) {
            $jsonString = $this->_extractData($_htmlData);
            if(!empty($jsonString)) {
                $ret = $this->_processData($jsonString);
            }
        }

        return $ret;
    }

    /**
     * Retrieve the title cover from the given URL and return a tmp file
     *
     * @param string $url
     * @return string
     */
    public function downloadCover(string $url): string {
        $ret = "";

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
     * The html from $this->_REFERENCE_URL should contain a script with the id __NEXT_DATA__
     * and it holds a json object with all the information we currently need.
     *
     * @param string $data
     * @return string
     */
    private function _extractData(string $data): string {
        $ret = "";

        $m = preg_match('~id="__NEXT_DATA__".*?>([^<]+)</script>~s', $data, $matches);
        if(!empty($m)) {
            $ret = $matches[1];
        } elseif($this->_DEBUG) {
            Summoner::sysLog("[DEBUG] ".__METHOD__." no script with id __NEXT_DATA__ found.");
        }
        //Summoner::sysLog("[DEBUG] ".__METHOD__." extracted json data.".Summoner::cleanForLog($ret));
        return $ret;
    }

    /**
     * Take the given jsonString and extract the wanted information defined by $this->_attributes
     *
     * @param string $jsonString
     * @return array
     */
    private function _processData(string $jsonString): array {
        $ret = array();

        $jsonData = json_decode($jsonString, true);
        if(!empty(json_last_error())) {
            Summoner::sysLog("[DEBUG] ".__METHOD__." can not decode json. ".json_last_error_msg());
            return $ret;
        }
        if(isset($jsonData["props"]["pageProps"]["contentData"]["data"]["title"])) {
            foreach($this->_attributes as $att) {
                $_m = "_get_".$att;
                if(method_exists($this, $_m)) {
                    $ret[$att] = $this->$_m($jsonData["props"]["pageProps"]["contentData"]["data"]["title"]);
                } else {
                    $ret[$att] = "";
                }
            }
        }

        return $ret;
    }

    private function _get_runtime(array $data): string {
        $t = $this->_retrieveDataFromArray(array("runtime","seconds"),$data);
        return floor($t / 60);
    }

    private function _get_rating(array $data): string {
        return $this->_retrieveDataFromArray(array("ratingsSummary","aggregateRating"), $data);
    }

    private function _get_title(array $data): string {
        return $this->_retrieveDataFromArray(array("titleText","text"), $data);
    }

    private function _get_originalTitle(array $data): string {
        return $this->_retrieveDataFromArray(array("originalTitleText","text"), $data);
    }

    private function _get_year(array $data): string {
        return $this->_retrieveDataFromArray(array("releaseYear","year"), $data);
    }

    private function _get_plot(array $data): string {
        return $this->_retrieveDataFromArray(array("plot","plotText","plainText"),$data);
    }

    private function _get_synopses(array $data): string {
        return $this->_retrieveDataFromArray(array("synopses","edges",0,"node","plotText","plaidHtml"), $data);
    }

    private function _get_coverImage(array $data): string {
        return $this->_retrieveDataFromArray(array("primaryImage","url"), $data);
    }

    private function _get_genres(array $data): array {
        $ret = array();
        foreach($data["titleGenres"]["genres"] as $g) {
            $ret[] = $g["genre"]["text"];
        }
        return $ret;
    }

    /**
     * Looks like there is a unique id for director(s)
     * amzn1.imdb.concept.name_credit_category.ace5cb4c-8708-4238-9542-04641e7c8171
     *
     * @param array $data
     * @return array
     */
    private function _get_directors(array $data): array {
        return $this->_resolveCreditGroupings($data, "amzn1.imdb.concept.name_credit_category.ace5cb4c-8708-4238-9542-04641e7c8171");
    }

    /**
     * Looks like there is a unique id for writer(s)
     * amzn1.imdb.concept.name_credit_category.c84ecaff-add5-4f2e-81db-102a41881fe3
     *
     * @param array $data
     * @return array
     */
    private function _get_writers(array $data): array {
        return $this->_resolveCreditGroupings($data, "amzn1.imdb.concept.name_credit_category.c84ecaff-add5-4f2e-81db-102a41881fe3");
    }

    /**
     * Looks like there is a unique id for the cast
     * amzn1.imdb.concept.name_credit_group.7caf7d16-5db9-4f4f-8864-d4c6e711c686
     *
     * @param array $data
     * @return array
     */
    private function _get_cast(array $data): array {
        return $this->_resolveCreditGroupings($data, "amzn1.imdb.concept.name_credit_group.7caf7d16-5db9-4f4f-8864-d4c6e711c686");
    }

    /**
     * Extract the information from the creditGroupings array.
     *
     * @param array $data
     * @param string $gId
     * @return array
     */
    private function _resolveCreditGroupings(array $data, string $gId): array {
        $ret = array();
        foreach($data["creditGroupings"]["edges"] as $edge) {
            $_gid = $this->_retrieveDataFromArray(array("node", "grouping", "groupingId"), $edge);
            if($_gid == $gId) {
                foreach($edge["node"]["credits"]["edges"] as $e) {
                    $ret[] = $this->_retrieveDataFromArray(array("node", "name", "nameText", "text"), $e);
                }
                break;
            }
        }
        return $ret;
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
                'Accept: ' . $this->_BROWSER_ACCEPT,
                'Accept-Charset: utf-8, iso-8859-1;q=0.5',
                'Accept-Language: ' . $this->_BROWSER_LANG)
        );
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.imdb.com');

        if ($this->_DEBUG) {
            $_headers = array();
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION,
                function ($curl, $header) use (&$_headers) {
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
        if (!curl_errno($ch)) {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code == 200) {
                if (is_string($do) === true) {
                    $ret = $do;
                }
            }
        }
        curl_close($ch);

        if ($this->_DEBUG) {
            Summoner::sysLog('[DEBUG] ' . __METHOD__ . ' headers ' . Summoner::cleanForLog($_headers));
        }

        return $ret;
    }

    /**
     * Accessing a nested array can fail if a nested key is not present.
     * Checks every given key, nested, for availablity
     *
     * @param array $toGet
     * @param array $data
     * @return mixed
     */
    private function _retrieveDataFromArray(array $toGet, array $data): mixed {
        $ret = "";

        foreach($toGet as $key) {
            if(isset($data[$key])) {
                $data = $data[$key];
                $ret = $data;
            } else {
                $ret = ""; // reset otherwise the last found will be returned
            }
        }

        return $ret;
    }
}
