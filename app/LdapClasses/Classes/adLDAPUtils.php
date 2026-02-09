<?php
/**
 * PHP LDAP CLASS FOR MANIPULATING ACTIVE DIRECTORY 
 * Version 4.0.4
 * 
 * PHP Version 5 with SSL and LDAP support
 * 
 * Written by Scott Barnett, Richard Hyland
 *   email: scott@wiggumworld.com, adldap@richardhyland.com
 *   http://adldap.sourceforge.net/
 * 
 * Copyright (c) 2006-2012 Scott Barnett, Richard Hyland
 * 
 * We'd appreciate any improvements or additions to be submitted back
 * to benefit the entire community :)
 * 
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License.
 * 
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 * 
 * @category ToolsAndUtilities
 * @package adLDAP
 * @subpackage Utils
 * @author Scott Barnett, Richard Hyland
 * @copyright (c) 2006-2012 Scott Barnett, Richard Hyland
 * @license http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPLv2.1
 * @revision $Revision: 97 $
 * @version 4.0.4
 * @link http://adldap.sourceforge.net/
 */

namespace App\LdapClasses\Classes;

use App\LdapClasses\adLDAP;


/**
* UTILITY FUNCTIONS
*/
class adLDAPUtils {
    const ADLDAP_VERSION = '4.0.4';
    
    /**
    * The current adLDAP connection via dependency injection
    * 
    * @var adLDAP
    */
    protected $adldap;
    
    public function __construct(adLDAP $adldap) {
        $this->adldap = $adldap;
    }
    
    
    /**
    * Take an LDAP query and return the nice names, without all the LDAP prefixes (eg. CN, DN)
    *
    * @param array $groups
    * @return array
    */
    public function niceNames($groups)
    {

        $groupArray = array();
        for ($i=0; $i<$groups["count"]; $i++){ // For each group
            $line = $groups[$i];
            
            if (strlen($line)>0) { 
                // More presumptions, they're all prefixed with CN=
                // so we ditch the first three characters and the group
                // name goes up to the first comma
                $bits=explode(",", $line);
                $groupArray[] = substr($bits[0], 3, (strlen($bits[0])-3));
            }
        }
        return $groupArray;    
    }
    
    /**
    * Escape characters for use in an ldap_create function
    * 
    * @param string $str
    * @return string
    */
    public function escapeCharacters($str) {
        $str = str_replace(",", "\,", $str);
        return $str;
    }
    
    /**
     * Escape strings for the use in LDAP filters
     *
     * DEVELOPERS SHOULD BE DOING PROPER FILTERING IF THEY'RE ACCEPTING USER INPUT
     * Ported from Perl's Net::LDAP::Util escape_filter_value
     * PHP 7.0+ removed preg_replace /e modifier; uses preg_replace_callback.
     *
     * @param string $str The string to parse
     * @return string
     */
    public function ldapSlashes($str){
        return preg_replace_callback('/([\x00-\x1F\*\(\)\\\\])/',
            function ($matches) {
                return '\\' . str_pad(dechex(ord($matches[1])), 2, '0', STR_PAD_LEFT);
            },
            $str);
    }
    
    /**
    * Converts a string GUID to a hexdecimal value so it can be queried
    * 
    * @param string $strGUID A string representation of a GUID
    * @return string
    */
    public function strGuidToHex($strGUID) 
    {
        $strGUID = str_replace('-', '', $strGUID);

        $octetStr = '\\' . substr($strGUID, 6, 2);
        $octetStr .= '\\' . substr($strGUID, 4, 2);
        $octetStr .= '\\' . substr($strGUID, 2, 2);
        $octetStr .= '\\' . substr($strGUID, 0, 2);
        $octetStr .= '\\' . substr($strGUID, 10, 2);
        $octetStr .= '\\' . substr($strGUID, 8, 2);
        $octetStr .= '\\' . substr($strGUID, 14, 2);
        $octetStr .= '\\' . substr($strGUID, 12, 2);
        //$octetStr .= '\\' . substr($strGUID, 16, strlen($strGUID));
        for ($i=16; $i<=(strlen($strGUID)-2); $i++) {
            if (($i % 2) == 0) {
                $octetStr .= '\\' . substr($strGUID, $i, 2);
            }
        }
        
        return $octetStr;
    }
    
    /**
    * Convert a binary SID to a text SID
    * 
    * @param string $binsid A Binary SID
    * @return string
    */
     public function getTextSID($binsid) {
        $hexSid = bin2hex($binsid);
        $rev = hexdec(substr($hexSid, 0, 2));
        $subcount = hexdec(substr($hexSid, 2, 2));
        $auth = hexdec(substr($hexSid, 4, 12));
        $result = "$rev-$auth";

        for ($x=0;$x < $subcount; $x++) {
            $subauth[$x] =
                hexdec($this->littleEndian(substr($hexSid, 16 + ($x * 8), 8)));
                $result .= "-" . $subauth[$x];
        }

        // Cheat by tacking on the S-
        return 'S-' . $result;
     }
     
    /**
    * Converts a little-endian hex number to one that hexdec() can convert
    * 
    * @param string $hex A hex code
    * @return string
    */
     public function littleEndian($hex) 
     {
        $result = '';
        for ($x = strlen($hex) - 2; $x >= 0; $x = $x - 2) {
            $result .= substr($hex, $x, 2);
        }
        return $result;
     }
     
     /**
    * Converts a binary attribute to a string
    * 
    * @param string $bin A binary LDAP attribute
    * @return string
    */
    public function binaryToText($bin) 
    {
        $hexGuid = bin2hex($bin); 
        $hexGuidToGuidStr = ''; 
        for($k = 1; $k <= 4; ++$k) { 
            $hexGuidToGuidStr .= substr($hexGuid, 8 - 2 * $k, 2); 
        } 
        $hexGuidToGuidStr .= '-'; 
        for($k = 1; $k <= 2; ++$k) { 
            $hexGuidToGuidStr .= substr($hexGuid, 12 - 2 * $k, 2); 
        } 
        $hexGuidToGuidStr .= '-'; 
        for($k = 1; $k <= 2; ++$k) { 
            $hexGuidToGuidStr .= substr($hexGuid, 16 - 2 * $k, 2); 
        } 
        $hexGuidToGuidStr .= '-' . substr($hexGuid, 16, 4); 
        $hexGuidToGuidStr .= '-' . substr($hexGuid, 20); 
        return strtoupper($hexGuidToGuidStr);   
    }
    
    /**
    * Converts a binary GUID to a string GUID
    * 
    * @param string $binaryGuid The binary GUID attribute to convert
    * @return string
    */
    public function decodeGuid($binaryGuid) 
    {
        if ($binaryGuid === null){ return "Missing compulsory field [binaryGuid]"; }
        
        $strGUID = $this->binaryToText($binaryGuid);          
        return $strGUID; 
    }
    
    /**
    * Convert a boolean value to a string
    * You should never need to call this yourself
    *
    * @param bool $bool Boolean value
    * @return string
    */
    public function boolToStr($bool) 
    {
        return ($bool) ? 'TRUE' : 'FALSE';
    }
    
    /**
    * Convert 8bit characters e.g. accented characters to UTF8 encoded characters
    */
    public function encode8Bit(&$item, $key) {
        $encode = false;
        if (is_string($item)) {
            for ($i=0; $i<strlen($item); $i++) {
                if (ord($item[$i]) >> 7) {
                    $encode = true;
                }
            }
        }
        if ($encode === true && $key != 'password') {
            $item = mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1');
        }
    }  
    
    /**
    * Get the current class version number
    * 
    * @return string
    */
    public function getVersion() {
        return self::ADLDAP_VERSION;
    }
    
    /**
    * Round a Windows timestamp down to seconds and remove the seconds between 1601-01-01 and 1970-01-01
    * 
    * @param long $windowsTime
    * @return long $unixTime
    */
    public static function convertWindowsTimeToUnixTime($windowsTime) {
      $unixTime = round($windowsTime / 10000000) - 11644477200; 
      return $unixTime; 
    }
}

?>