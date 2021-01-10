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
 * a static helper class
 */
class Summoner {

	/**
	 * Return path to given theme file with fallback to default theme
	 *
	 * @param string $file relative path from THEME/
	 * @param string $theme Theme name
	 * @param string $defaultTheme Default theme name can be overwritten
	 * @return bool|string False of nothing is found
	 */
	static function themefile($file, $theme, $defaultTheme='default') {
		$ret = false;

		if(file_exists('view/'.$theme.'/'.$file)) {
			$ret = 'view/'.$theme.'/'.$file;
		}
		elseif (file_exists('view/'.$defaultTheme.'/'.$file)) {
			$ret = 'view/'.$defaultTheme.'/'.$file;
		}

		return $ret;
	}

	/**
	 * validate the given string with the given type. Optional check the string
	 * length
	 *
	 * @param string $input The string to check
	 * @param string $mode How the string should be checked
	 * @param mixed $limit If int given the string is checked for length
	 *
	 * @see http://de.php.net/manual/en/regexp.reference.unicode.php
	 * http://www.sql-und-xml.de/unicode-database/#pc
	 *
	 * the pattern replaces all that is allowed. the correct result after
	 * the replace should be empty, otherwise are there chars which are not
	 * allowed
	 *
	 * @return bool
	 */
	static function validate($input,$mode='text',$limit=false) {
		// check if we have input
		$input = trim($input);

		if($input == "") return false;

		$ret = false;

		switch ($mode) {
			case 'mail':
				if(filter_var($input,FILTER_VALIDATE_EMAIL) === $input) {
					return true;
				}
				else {
					return false;
				}
			break;

			case 'rights':
				return self::isRightsString($input);
			break;

			case 'url':
				if(filter_var($input,FILTER_VALIDATE_URL) === $input) {
					return true;
				}
				else {
					return false;
				}
			break;

			case 'nospace':
				// text without any whitespace and special chars
				$pattern = '/[\p{L}\p{N}]/u';
			break;

			case 'nospaceP':
				// text without any whitespace and special chars
				// but with Punctuation other
				# http://www.sql-und-xml.de/unicode-database/po.html
				$pattern = '/[\p{L}\p{N}\p{Po}\-]/u';
			break;

			case 'digit':
				// only numbers and digit
				// warning with negative numbers...
				$pattern = '/[\p{N}\-]/u';
			break;

			case 'pageTitle':
				// text with whitespace and without special chars
				// but with Punctuation
				$pattern = '/[\p{L}\p{N}\p{Po}\p{Z}\s-]/u';
			break;

			# strange. the \p{M} is needed.. don't know why..
			case 'filename':
				$pattern = '/[\p{L}\p{N}\p{M}\-_\.\p{Zs}]/u';
			break;

			case 'text':
			default:
				$pattern = '/[\p{L}\p{N}\p{P}\p{S}\p{Z}\p{M}\s]/u';
		}

		$value = preg_replace($pattern, '', $input);

		#if($input === $value) {
		if($value === "") {
			$ret = true;
		}

		if(!empty($limit)) {
			# isset starts with 0
			if(isset($input[$limit])) {
				# too long
				$ret = false;
			}
		}

		return $ret;
	}

	/**
	 * return if the given string is utf8
	 * http://php.net/manual/en/function.mb-detect-encoding.php
	 *
	 * @param string $string
	 * @return number
	 */
	static function is_utf8 ( $string ) {
	   // From http://w3.org/International/questions/qa-forms-utf-8.html
	   return preg_match('%^(?:
			 [\x09\x0A\x0D\x20-\x7E]            # ASCII
		   | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		   |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
		   | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
		   |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
		   |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
		   | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
		   |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
	   )*$%xs', $string);
	}

	/**
	 * check if the given string is a rights string.
	 * @param string $string
	 * @return boolean
	 */
	static function isRightsString($string) {
		$ret = false;

		$string = trim($string);
		if(empty($string)) return false;
		if(isset($string[9])) return false;

		$check = str_replace("r", "", $string);
		$check = str_replace("w", "", $check);
		$check = str_replace("x", "", $check);
		$check = str_replace("-", "", $check);

		if(empty($check)) {
			$ret = true;
		}

		return $ret;
	}

	/**
	 * creates the rights string from the given rights array
	 * check what options are set and set the missing ones to -
	 *
	 * then create the rights string
	 * IMPORTANT: keep the order otherwise the rights will be messed up
	 *
	 * @param array $rightsArr
	 * @return mixed
	 */
	static function prepareRightsString($rightsArr) {
		$rsArr = array();
		$ret = false;

		if(!empty($rightsArr)) {
			// we need a complete type list
			// since we can get an "incomplete" array
			// if the user hasnt the rights for a specific type
			if(!isset($rightsArr['user'])) {
				$rightsArr['user'] = "";
			}
			if(!isset($rightsArr['group'])) {
				$rightsArr['group'] = "";
			}
			if(!isset($rightsArr['other'])) {
				$rightsArr['other'] = "";
			}

			// create the rights information
			foreach ($rightsArr as $type=>$data) {
				if(!empty($data['read']) && $data['read'] == "1") {
					$rsArr[$type]['read'] = "r";
				}
				else {
					$rsArr[$type]['read'] = "-";
				}

				if(!empty($data['write']) && $data['write'] == "1") {
					$rsArr[$type]['write'] = "w";
				}
				else {
					$rsArr[$type]['write'] = "-";
				}

				if(!empty($data['delete']) && $data['delete'] == "1") {
					$rsArr[$type]['delete'] = "x";
				}
				else {
					$rsArr[$type]['delete'] = "-";
				}
			}

			$rString = $rsArr['user']['read'].$rsArr['user']['write'].$rsArr['user']['delete'];
			$rString .= $rsArr['group']['read'].$rsArr['group']['write'].$rsArr['group']['delete'];
			$rString .= $rsArr['other']['read'].$rsArr['other']['write'].$rsArr['other']['delete'];

			if(strlen($rString) != 9) {
				$ret = false;
				// invalid rights string !!
			}
			else {
				$ret = $rString;
			}
		}

		return $ret;
	}

	/**
	 * Creates from given rights string the rights array
	 * @param string $rightsString
	 * @return array
	 */
	static function prepareRightsArray($rightsString) {
		$ret = array();

		if(self::isRightsString($rightsString) === true) {
			$ret['user']['read'] = '-';
			$ret['user']['write'] = '-';
			$ret['user']['delete'] = '-';
			if($rightsString[0] === 'r') $ret['user']['read'] = 'r';
			if($rightsString[1] === 'w') $ret['user']['write'] = 'w';
			if($rightsString[2] === 'x') $ret['user']['delete'] = 'x';

			$ret['group']['read'] = '-';
			$ret['group']['write'] = '-';
			$ret['group']['delete'] = '-';
			if($rightsString[3] === 'r') $ret['group']['read'] = 'r';
			if($rightsString[4] === 'w') $ret['group']['write'] = 'w';
			if($rightsString[5] === 'x') $ret['group']['delete'] = 'x';

			$ret['other']['read'] = '-';
			$ret['other']['write'] = '-';
			$ret['other']['delete'] = '-';
			if($rightsString[6] === 'r') $ret['other']['read'] = 'r';
			if($rightsString[7] === 'w') $ret['other']['write'] = 'w';
			if($rightsString[8] === 'x') $ret['other']['delete'] = 'x';
		}

		return $ret;
	}

	/**
	 * get the mime type for given file
	 * uses either mime_content_type or finfo
	 * @param string $file The absolute path to the file
	 * @return mixed|string
	 */
	static function getMimeType($file) {
		$mime = 'application/octet-stream'; # default

		if(function_exists('mime_content_type') !== true) {
			$mime = mime_content_type($file);
		}
		elseif(function_exists('finfo_open') === true) {
			# provide empty magic file, system default file will be used
			$finfo = finfo_open(FILEINFO_MIME_TYPE,null);
			if($finfo) {
				$mime = finfo_file($finfo, $file);
				finfo_close($finfo);
			}

			# the mime info returns sometimes "application/x-gzip; charset=binary"
			# but wee need the part before the ;
			if(strstr($mime,';')) {
				$tmp = explode(";",$mime);
				$mime = $tmp[0];
			}
		}

		return $mime;
	}

	/**
	 * use the mimeType string to return the string to be used as an icon identifier
	 * eg. application/pdf => pdf
	 * @param string $mime
	 * @return string $ret
	 */
	static function mimeToIcon($mime) {
		$ret = 'unknown';

		if(!empty($mime) && strstr($mime,'/') !== false) {
			$tmp = explode('/', $mime);
			$ret = $tmp[1];
		}
		elseif($mime === "directory") {
			$ret = "dir";
		}

		return $ret;
	}

	/**
	 * read a dir and return the entries as an array
	 * with full path to the files
	 * @param string $directory The absolute path to the directory
	 * @param array $ignore An Array with strings to ignored
	 * @param bool $recursive If we run a recursive scan or not
	 * @return array
	 */
	static function readDir($directory,$ignore=array(),$recursive=false) {
		$files = array();

		$dh = opendir($directory);
		while(false !== ($file = readdir($dh))) {
			if($file[0] ==".") continue;
			if(!empty($ignore)) {
				foreach ($ignore as $ig)  {
					if(strstr($file,$ig)) continue 2;
				}
			}

			if(is_file($directory."/".$file)) {
				array_push($files, $directory."/".$file);
			}
			elseif($recursive === true) {
				array_push($files, $directory."/".$file);
				$files = array_merge($files, self::readDir($directory."/".$file,$ignore, $recursive));
			}
			elseif(is_dir($directory."/".$file)) {
				array_push($files, $directory."/".$file);
			}
		}
		closedir($dh);

		return $files;
	}

	/**
	 * delete and/or empty a diretory
	 *
	 * $empty = true => empty the diretory but do not delete it
	 *
	 * @param string $directory
	 * @param bool $empty
	 * @param mixed $fTime If not false remove files older then this value in sec.
	 * @return bool
	 */
	static function recursive_remove_directory($directory, $empty=false,$fTime=false) {
		// if the path has a slash at the end we remove it here
		if(substr($directory,-1) == '/') {
			$directory = substr($directory,0,-1);
		}

		// if the path is not valid or is not a directory ...
		if(!file_exists($directory) || !is_dir($directory)) {
			// ... we return false and exit the function
			return false;

		// ... if the path is not readable
		}elseif(!is_readable($directory)) {
			// ... we return false and exit the function
			return false;

		// ... else if the path is readable
		}
		else {
			// we open the directory
			$handle = opendir($directory);

			// and scan through the items inside
			while (false !== ($item = readdir($handle))) {
				// if the filepointer is not the current directory
				// or the parent directory
				//if($item != '.' && $item != '..' && $item != '.svn') {
				if($item[0] != '.') {
					// we build the new path to delete
					$path = $directory.'/'.$item;

					// if the new path is a directory
					if(is_dir($path)) {
					   // we call this function with the new path
						self::recursive_remove_directory($path);

					// if the new path is a file
					}
					else {
						// we remove the file
						if($fTime !== false && is_int($fTime)) {
							// check filemtime
							$ft = filemtime($path);
							$offset = time()-$fTime;
							if($ft <= $offset) {
								unlink($path);
							}
						}
						else {
							unlink($path);
						}
					}
				}
			}
			// close the directory
			closedir($handle);

			// if the option to empty is not set to true
			if($empty == false) {
				// try to delete the now empty directory
				if(!rmdir($directory)) {
					// return false if not possible
					return false;
				}
			}
			// return success
			return true;
		}
	}

	/**
	 * execute a curl call to the fiven $url
	 * @param string $url The request url
	 * @param integer $port
	 * @return bool|string
	 */
	static function curlCall($url,$port=80) {
		$ret = false;

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_PORT, $port);

		$do = curl_exec($ch);
		if(is_string($do) === true) {
			$ret = $do;
		}

		curl_close($ch);

		return $ret;
	}

	/**
	 * check if a string strts with a given string
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	static function startsWith($haystack, $needle) {
		$length = strlen($needle);
		return (substr($haystack, 0, $length) === $needle);
	}

	/**
	 * check if a string ends with a given string
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	static function endsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}

	/**
	 * http://de1.php.net/manual/en/function.getimagesize.php
	 * http://php.net/manual/en/function.imagecreatefromjpeg.php
	 *
	 * @param string $file The absolute path to the image file
	 * @param number $width
	 * @param number $height
	 * @return bool
	 */
	static function createThumbnail($file,$width=ADMIN_THUMBNAIL_DEFAULT_WIDTH,$height=ADMIN_THUMBNAIL_DEFAULT_HEIGHT) {
		$ret = false;

		if(!is_file($file)) return false;
		# check if this is a support file type to create a thumbnail
		$mimeType = self::getMimeType($file);
		if(!strstr(ADMIN_THUMBNAIL_SUPPORTED_FILE_TYPES, $mimeType)) return false;

		// Get new dimensions
		$imageinfo = getimagesize($file);

		if(empty($imageinfo)) return false;

		$thumbnailName = $file.ADMIN_THUMBNAIL_IDENTIFIER.".".$height."x".$width;

		$width_orig = $imageinfo[0];
		$height_orig = $imageinfo[1];
		$ratio_orig = $width_orig/$height_orig;

		if ($width/$height > $ratio_orig) {
			$width = $height*$ratio_orig;
		} else {
			$height = $width/$ratio_orig;
		}

		$im = false;

		// Resample
		$image_p = imagecreatetruecolor($width, $height);

		switch ($imageinfo[2]) {
			case IMAGETYPE_GIF:
				$im = imageCreateFromGif($file);
				imagecopyresampled($image_p, $im, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
				imagegif($image_p, $thumbnailName);
			break;

			case IMAGETYPE_JPEG:
				$im = imageCreateFromJpeg($file);
				imagecopyresampled($image_p, $im, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
				// save
				$do = imagejpeg($image_p, $thumbnailName, 70);
			break;

			case IMAGETYPE_PNG:
				$im = imageCreateFromPng($file);
				imagecopyresampled($image_p, $im, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
				imagepng($image_p, $thumbnailName, 5);
			break;
		}

		imagedestroy($im);
		imagedestroy($image_p);

		if(file_exists($thumbnailName)) {
			$ret = true;
		}

		return $ret;
	}

	/**
	 * fix the filesystem filenames. Remove whitespace and ...
	 * @param array $filenames File or folder list
	 * @return array
	 */
	static function fixAssetFilenames($filenames) {
		$ret = $filenames;

		foreach($filenames as $k=>$file) {
			if(file_exists($file)) {
				if(strstr($file, " ")) {
					# we do not allow any whitespace in a filename
					$newFilename = str_replace(" ", "-", $file);
					rename($file, $newFilename);
					$filenames[$k] = $newFilename;
				}
			}
		}

		return $filenames;
	}

	/**
	 * simulate the Null coalescing operator in php5
	 *
	 * this only works with arrays and checking if the key is there and echo/return it.
	 *
	 * http://php.net/manual/en/migration70.new-features.php#migration70.new-features.null-coalesce-op
	 *
	 * @param $array
	 * @param $key
	 * @return bool|mixed
	 */
	static function ifset($array,$key) {
		return isset($array[$key]) ? $array[$key] : false;
	}

	/**
	 * based on self::ifset check also the value
	 *
	 * @param array $array The array to use
	 * @param string $key The key to check
	 * @param string $value The value to compare
	 * @return bool
	 */
	static function ifsetValue($array,$key,$value) {
		if(self::ifset($array,$key) !== false) {
			return $array[$key] == $value;
		}
		return false;
	}

	/**
	 * Replace in $haystack the $needle with $replace only once
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @param string $replace
	 * @return string
	 */
	static function replaceOnce($haystack, $needle, $replace) {
		$newstring = $haystack;
		$pos = strpos($haystack, $needle);
		if ($pos !== false) {
			$newstring = substr_replace($haystack, $replace, $pos, strlen($needle));
		}
		return $newstring;
	}

	/**
	 * http_build_query with modify array
	 * modify will add: key AND value not empty
	 * modify will remove: only key with no value
	 *
	 * @param array $array
	 * @param array $modify
	 * @return string
	 */
	static function createFromParameterLinkQuery($array,$modify=array()) {
		$ret = '';

		if(!empty($modify)) {
			foreach($modify as $k=>$v) {
				if(empty($v)) {
					unset($array[$k]);
				}
				else {
					$array[$k] = $v;
				}
			}
		}

		if(!empty($array)) {
			$ret = http_build_query($array);
		}

		return $ret;
	}
}
