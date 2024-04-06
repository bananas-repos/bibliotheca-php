<?php
/**
 * Bibliotheca
 *
 * Copyright 2018-2024 Johannes Keßler
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

class I18n {
    /**
     * @var string The iso 3 lang code
     */
    private string $_iso3 = 'eng';

    /**
     * @var string The iso 2 lang code
     */
    private string $_iso2 = 'en';

    /**
     * @var array The loaded lang information from the file
     */
    private array $_langData = array();

    /**
     * Translation constructor.
     */
    public function __construct() {
        if(defined('FRONTEND_LANGUAGE')) {
            $this->_iso3 = FRONTEND_LANGUAGE['iso3'];
            $this->_iso2 = FRONTEND_LANGUAGE['iso2'];
            $_langFile = PATH_ABSOLUTE.'/i18n/'.$this->_iso3.'.ini';
            if(file_exists($_langFile)) {
                $_langData = parse_ini_file($_langFile);
                if($_langData !== false) {
                    $this->_langData = $_langData;
                }
            }
        }
        else {
            $_langFile = PATH_ABSOLUTE.'/i18n/'.$this->_iso3.'.ini';
            $_langData = parse_ini_file($_langFile);
            if($_langData !== false) {
                $this->_langData = $_langData;
            }
        }
    }

    public function twoCharLang(): string {
        return $this->_iso2;
    }

    /**
     * Return text for given key for currently loaded lang
     * uses vsprintf or sprintf for placeholders in key
     *
     * @param string $key
     * @param mixed $replace
     * @return string
     */
    public function t(string $key, mixed $replace=''): string {
        $ret = $key;
        if(isset($this->_langData[$key])) {
            $ret = $this->_langData[$key];
            // the value is another key
            if(!str_contains($ret, '"') && str_contains($ret, ".") && isset($this->_langData[$ret])) {
                $ret = $this->_langData[$ret];
            }

            if(!empty($replace)) {
                if(is_array($replace)) {
                    $ret = vsprintf($ret, $replace);
                } else {
                    $ret = sprintf($ret, $replace);
                }
            }
        }
        return $ret;
    }
}
