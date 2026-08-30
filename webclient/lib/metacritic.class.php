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
 * Class Metacritic
 *
 * Retrive the HTML from a metacritic URL and parse the json+ld information
 */

class Metacritic {
    /**
     * Set this to true if you run into problems.
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

    /**
    * @var string The directory to store cache files
    */
    private string $_CACHE_DIR = "";

    /**
    * @var array The fields to return
    */
    private array $_attributes;

    private string $_BASE_URL_MOVIES = 'https://www.metacritic.com/movie/%s/';

    /**
    * @var int Maximum cache time.
    */
    private int $_CACHE_TIME = 1440;

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

    public function fetch(string $urlPart): array {
        $ret = array();

        if (empty($urlPart)) return $ret;
        $url = sprintf($this->_BASE_URL_MOVIES, $urlPart);
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
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.metacritic.com');

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
            } else {
                Summoner::sysLog('[WARN] ' . __METHOD__ . ' non 200 return status ' . Summoner::cleanForLog($http_code));
            }
        }
        curl_close($ch);

        if ($this->_DEBUG) {
            Summoner::sysLog('[DEBUG] ' . __METHOD__ . ' headers ' . Summoner::cleanForLog($_headers));
        }

        return $ret;
    }

    /**
     * The html from the curl call should contain application/ld+json
     * and it holds a json object with all the information we currently need.
     *
     * @param string $data
     * @return string
     */
    private function _extractData(string $data): string {
        $ret = "";

        $m = preg_match('~type="application/ld\+json".*?>([^<]+)</script>~s', $data, $matches);
        if(!empty($m)) {
            $ret = $matches[1];
        } elseif($this->_DEBUG) {
            Summoner::sysLog("[DEBUG] ".__METHOD__." no script with type application/ld+json found.");
        }
        Summoner::sysLog("[DEBUG] ".__METHOD__." extracted json data.".Summoner::cleanForLog($ret));
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

        if(isset($jsonData["name"])) {
            foreach($this->_attributes as $att) {
                $_m = "_get_".$att;
                if(method_exists($this, $_m)) {
                    $ret[$att] = $this->$_m($jsonData);
                } elseif (isset($jsonData[$att])) {
                    $ret[$att] = $jsonData[$att];
                } else {
                    $ret[$att] = "";
                }
            }
        }

        return $ret;
    }

    private function _get_ratingValue(array $data): string {
        $t = $this->_retrieveDataFromArray(array("aggregateRating","ratingValue"),$data);
        return $t;
    }

    private function _get_genre(array $data): array {
        $ret = array();
        foreach($data['genre'] as $g) {
            $ret[] = $g;
        }
        return $ret;
    }

    private function _get_actor(array $data): array {
        $ret = array();
        foreach($data['actor'] as $e) {
            $ret[] = $e['name'];
        }
        return $ret;
    }

    private function _get_director(array $data): array {
        $ret = array();
        foreach($data['director'] as $e) {
            $ret[] = $e['name'];
        }
        return $ret;
    }

    private function _get_datePublished(array $data): string {
        $ret = '';
        if(isset($data['datePublished'])) {
            $time = strtotime($data['datePublished']);
            $ret = date('Y', $time);
        }
        return $ret;
    }



    /**
     * The duration something like this: PT2M11S
     * It is https://iso8601.com/#duration but wrong?
     * The above example is from a movie which is 2h and 11m
     * and NOT 2m 11s
     *
     * @param array $data
     * @return array
     */
    private function _get_duration(array $data): string {
        $ret = '';
        if(isset($data['duration'])) {
            try {
                $interval = new DateInterval($data['duration']);
                $ret = $interval->format("%i")*60;
                $ret = $ret+$interval->format("%s"); // not very cool...
            } catch (Exception $e) {
                Summoner::sysLog("[DEBUG] ".__METHOD__." can not duration ".$data['duration']);
            }
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
