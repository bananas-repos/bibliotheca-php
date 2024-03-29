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
 * Class Musicbrainz
 *
 * Simple search for an artist and album
 *
 * https://musicbrainz.org/doc/Development
 * https://musicbrainz.org/doc/MusicBrainz_API/Examples
 * https://musicbrainz.org/doc/MusicBrainz_API/Search
 * https://musicbrainz.org/doc/MusicBrainz_API
 */
class Musicbrainz {

	/**
	 * @var bool DEBUG
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
	 * @var string The musicbrainz API release endpoint
	 */
	private string $_RELEASE_ENDPOINT = 'http://musicbrainz.org/ws/2/release/';

	/**
	 * @var string The endpoint for images
	 */
	private string $_IMAGE_ENDPOINT = 'http://coverartarchive.org/release/';

	/**
	 * @var int The amount of entries returned for release search
	 */
	private int $_resultLimit = 10;

	/**
	 * Musicbrainz constructor.
	 *
	 * @param $options array
	 */
	public function __construct(array $options) {
		if(isset($options['debug']) && !empty($options['debug'])) {
			$this->_DEBUG = true;
		}

		if(isset($options['resultLimit']) && !empty($options['resultLimit'])) {
			$this->_resultLimit = $options['resultLimit'];
		}

		$this->_BROWSER_AGENT = $options['browserAgent'];
		$this->_BROWSER_LANG = $options['browserLang'];
		$this->_BROWSER_ACCEPT = $options['browserAccept'];
	}


	/**
	 * Search for a release fpr the given artist and album name
	 *
	 * http://musicbrainz.org/ws/2/release/?query=artist:broilers%20AND%20release:muerte%20AND%20format:CD&fmt=json
	 *
	 * [releaseID] = title - artist - status - date - country - disambiguation - packaging - track-count
	 *
	 *
	 * @param string $artist The artist to search for
	 * @param string $album The album of the artist to search for
	 *
	 * @return array
	 */
	public function searchForRelease(string $artist, string $album): array {
		$ret = array();

		if(!empty($artist) && !empty($album)) {
			$artist = urlencode($artist);
			$album = urlencode($album);
			$url = $this->_RELEASE_ENDPOINT;
			$url .= '?&fmt=json&limit='.$this->_resultLimit.'&query=';
			$url .= 'artist:'.$artist.'%20AND%20release:'.$album.'%20AND%20format:CD';

			if(DEBUG) Summoner::sysLog("[DEBUG] musicbrainz release url: $url");

			$do = $this->_curlCall($url);
			$data = '';
			if(!empty($do)) {
				$data = json_decode($do, true);
				if(!empty($data)) {
					if(DEBUG) Summoner::sysLog("[DEBUG] musicbrainz releases json data:".Summoner::cleanForLog($data));
				}
				else {
                    Summoner::sysLog("[ERROR] musicbrainz invalid releases json data:".Summoner::cleanForLog($do));
				}
			}

			if(!empty($data)) {
				if(isset($data['releases'])) {
					foreach($data['releases'] as $release) {
						if(isset($release['title'])
							&& isset($release['status'])
							&& isset($release['date'])
							&& isset($release['country'])
							&& isset($release['artist-credit'][0]['name'])) {

							$ret[$release['id']] = $release['title'].' - '.$release['artist-credit'][0]['name'].'; '.$release['status'].'; '.$release['date'].'; '.$release['country'];

							if(isset($release['disambiguation'])) {
								$ret[$release['id']] .= '; '.$release['disambiguation'];
							}
							if(isset($release['packaging'])) {
								$ret[$release['id']] .= '; '.$release['packaging'];
							}

							if(isset($release['track-count'])) {
								$ret[$release['id']] .= '; tracks: '.$release['track-count'];
							}
						}
					}
				}
			}

		}

		return $ret;
	}

	/**
	 * Get the information from musicBrainz by given release ID
	 * https://musicbrainz.org/doc/MusicBrainz_API/Examples#Release
	 *
	 * http://musicbrainz.org/ws/2/release/59211ea4-ffd2-4ad9-9a4e-941d3148024a?inc=recordings&fmt=json
	 *
	 * [album] => title
	 * [date] => date
	 * [artist] => artist-credit name
	 * [tracks] => number - title - min
	 * [image] => img url
	 * [runtime] => summed up runtime in minutes from tracks
	 *
	 * @param string $releaseId
	 * @return array
	 */
	public function getReleaseInfo(string $releaseId): array {
		$ret = array();

		if(!empty($releaseId)) {
			$url = $this->_RELEASE_ENDPOINT;
			$url .= $releaseId;
			$url .= '?&fmt=json&inc=recordings+artist-credits';

			$do = $this->_curlCall($url);
			$data = '';
			if(!empty($do)) {
				$data = json_decode($do, true);
				if(!empty($data)) {
					if(DEBUG) Summoner::sysLog("[DEBUG] musicbrainz release json data:".Summoner::cleanForLog($data));
				}
				else {
                    Summoner::sysLog("[ERROR] musicbrainz invalid release json data:".Summoner::cleanForLog($do));
				}
			}

			if(!empty($data)) {
				$ret['id'] = isset($data['id']) ? $data['id'] : '';
				$ret['album'] = isset($data['title']) ? $data['title'] : '';
				$ret['date'] = isset($data['date']) ? $data['date'] : '';
				$ret['artist'] = isset($data['artist-credit'][0]['name']) ? $data['artist-credit'][0]['name'] : '';
				$ret['tracks'] = '';
				$ret['image'] = '';
				$ret['runtime'] = 0;

				foreach($data['media'] as $media) {
					foreach($media['tracks'] as $track) {
						$ret['runtime'] += $track['length'];
						$l = (int) round($track['length'] / 1000);
						$l = date("i:s",$l);
						$ret['tracks'] .= $track['number'].' - '.$track['title'].' - '.$l."\n";
					}
				}

				$ret['runtime'] = round($ret['runtime'] / 1000 / 60);

				// image
				$do = $this->_curlCall($this->_IMAGE_ENDPOINT.$releaseId);
				if(!empty($do)) {
					$imageData = json_decode($do, true);
					if(!empty($imageData)) {
						if(DEBUG) Summoner::sysLog("[DEBUG] image release json data:".Summoner::cleanForLog($imageData));
						$ret['image'] = isset($imageData['images'][0]['image']) ? $imageData['images'][0]['image'] : '';
					}
					else {
                        Summoner::sysLog("[ERROR] image invalid release json data:".Summoner::cleanForLog($do));
					}
				}
			}
		}

		return $ret;
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

		$_tmpFile = tempnam(sys_get_temp_dir(), "bibliotheca-");
		$fh = fopen($_tmpFile,"w+");
		if($fh !== false) {

			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_FILE, $fh);

			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
			curl_setopt($ch, CURLOPT_USERAGENT, $this->_BROWSER_AGENT);

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

			curl_exec($ch);
			curl_close($ch);

			if($this->_DEBUG) {
                Summoner::sysLog('[DEBUG] '.__METHOD__.' headers '.Summoner::cleanForLog($_headers));
			}

			$ret = $_tmpFile;
		}
		fclose($fh);

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
			'Accept: '.$this->_BROWSER_ACCEPT,
			'Accept-Language: '.$this->_BROWSER_LANG)
		);

		if($this->_DEBUG) {
			$_headers = array();
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
