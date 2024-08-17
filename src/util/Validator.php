<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;


/**
 * Validator
 */
class Validator extends StaticClass {


    /**
     * Whether the passed string represents a valid IP address.
     *
     * @param  string $string                  - string to validate
     * @param  bool   $returnOctets [optional] - return type:
     *                                           FALSE: boolean (default)
     *                                           TRUE:  array with IP octets or FALSE if the string doesn't represent a valid IP address
     * @return string[]|bool
     *
     * @phpstan-return ($returnOctets is true ? string[]|bool : bool)
     */
    public static function isIPAddress($string, $returnOctets = false) {
        static $pattern = '/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/';
        $octets = null;

        if (is_string($string) && preg_match($pattern, $string, $octets)) {
            \array_shift($octets);

            foreach ($octets as $i => $byte) {
                $b = (int) $byte;
                if (!is_string($byte) || $b > 255) {
                    return false;
                }
                $octets[$i] = $b;
            }
            if ($octets[0] == 0) {
                return false;
            }
            return $returnOctets ? $octets : true;
        }
        return false;
    }


    /**
     * Whether the passed string represents a valid LAN IP address.
     *
     * @param  string $string
     *
     * @return bool
     */
    public static function isIPLanAddress($string) {
        $bytes = self::isIPAddress($string, true);

        if ($bytes) {
            if ($bytes[0] == 10)                   // 10.0.0.0 - 10.255.255.255
                return true;

            if ($bytes[0] == 172)                  // 172.16.0.0 - 172.31.255.255
                return (15 < $bytes[1] && $bytes[1] < 32);

            if ($bytes[0]==192 && $bytes[1]==168)  // 192.168.0.0 - 192.168.255.255
                return true;
        }
        return false;
    }


    /**
     * Whether the passed string represents a valid WAN IP address.
     *
     * @param  string $string
     *
     * @return bool
     */
    public static function isIPWanAddress($string) {
        $bytes = self::isIPAddress($string, true);

        // logic: opposite of isIPLanAddress() + additional tests
        if ($bytes) {
            if ($bytes[0] == 10)                   // 10.0.0.0 - 10.255.255.255
                return false;

            if ($bytes[0] == 127)                  // 127.0.0.0 - 127.255.255.255
                return false;

            if ($bytes[0]==169)                    // 169.0.0.0 - 169.255.255.255
                return false;

            if ($bytes[0] == 172)                  // 172.16.0.0 - 172.31.255.255
                return !(15 < $bytes[1] && $bytes[1] < 32);

            if ($bytes[0]==192)                    // 192.168.0.0 - 192.168.255.255
                return ($bytes[1]!=168);
        }

        // a more speculative TRUE
        return true;
    }


    /**
     * Whether the passed string represents a valid email address pattern (supports wildcards "?" and "*").
     *
     * @param  string $string
     *
     * @return bool
     */
    public static function isEmailAddressPattern($string) {

        // TODO: validate addresses in IP notation, e.g. "root@[127.0.0.1]"

        static $pattern = '/^[a-z0-9?*+-]+[a-z0-9?*_.+-]*@(([a-z0-9?*]+|[a-z0-9?*]+[a-z0-9?*-]+[a-z0-9?*]+)\.)*(\*|[a-z0-9?*][a-z0-9?*-]*[a-z0-9?*])\.(\*|[a-z?*]{2,4})$/';
        return is_string($string) && strlen($string) && preg_match($pattern, strtolower($string));
    }


    /**
     * Validates a string representing a date/time value and converts it to a Unix timestamp.
     *
     * @param  string          $date              - string to validate
     * @param  string|string[] $format [optional] - string format required to match; if an array the string must match at least
     *                                              one of the provided formats
     *
     * @return int|bool - Unix timestamp or FALSE if the string doesn't match the specified format
     *
     * Supported date/time formats: "Y-m-d [H:i[:s]]"
     *                              "Y.m.d [H:i[:s]]"
     *                              "d.m.Y [H:i[:s]]"
     *                              "d/m/Y [H:i[:s]]"
     */
    public static function isDateTime($date, $format='Y-m-d') {
        Assert::string($date, '$date');

        if (is_array($format)) {
            foreach ($format as $value) {
                $time = self::{__FUNCTION__}($date, $value);
                if (is_int($time))
                    return $time;
            }
            return false;
        }
        Assert::string($format, '$format');

        // strptime() can't be used, as it doesn't strictly follow the specified format
        $year = $month = $day = $hour = $minute = $second = $m = null;

        if ($format == 'Y-m-d') {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $date, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'Y-m-d H:i') {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = 0;
        }
        elseif ($format == 'Y-m-d H:i:s') {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = (int)$m[6];
        }
        elseif ($format == 'Y.m.d') {
            if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2})$/', $date, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'Y.m.d H:i') {
            if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2}) ([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = 0;
        }
        elseif ($format == 'Y.m.d H:i:s') {
            if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = (int)$m[6];
        }
        elseif ($format == 'd.m.Y') {
            if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})$/', $date, $m)) return false;
            $year   = (int)$m[3];
            $month  = (int)$m[2];
            $day    = (int)$m[1];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'd.m.Y H:i') {
            if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4}) ([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $day    = (int)$m[1];
            $month  = (int)$m[2];
            $year   = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = 0;
        }
        elseif ($format == 'd.m.Y H:i:s') {
            if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $day    = (int)$m[1];
            $month  = (int)$m[2];
            $year   = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = (int)$m[6];
        }
        elseif ($format == 'd/m/Y') {
            if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $date, $m)) return false;
            $year   = (int)$m[3];
            $month  = (int)$m[2];
            $day    = (int)$m[1];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'd/m/Y H:i') {
            if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $day    = (int)$m[1];
            $month  = (int)$m[2];
            $year   = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = 0;
        }
        elseif ($format == 'd/m/Y H:i:s') {
            if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $day    = (int)$m[1];
            $month  = (int)$m[2];
            $year   = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = (int)$m[6];
        }
        else {
            return false;
        }
        return (checkdate($month, $day, $year) && $hour < 24 && $minute < 60 && $second < 60) ? mktime($hour, $minute, $second, $month, $day, $year) : false;
    }
}
