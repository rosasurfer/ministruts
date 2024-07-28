<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\util;

use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\assert\Assert;


/**
 * Validator
 */
class Validator extends StaticClass {


    /**
     * Validates a string representing a date/time value and converts it to a Unix timestamp.
     *
     * @param  string          $datetime          - string to validate
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
    public static function isDateTime($datetime, $format='Y-m-d') {
        Assert::string($datetime, '$datetime');

        if (is_array($format)) {
            foreach ($format as $value) {
                $time = self::{__FUNCTION__}($datetime, $value);
                if (is_int($time))
                    return $time;
            }
            return false;
        }
        Assert::string($format, '$format');

        // strptime() can't be used, as it doesn't strictly follow the specified format
        $year = $month = $day = $hour = $minute = $second = $m = null;

        if ($format == 'Y-m-d') {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $datetime, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'Y-m-d H:i') {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})$/', $datetime, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = 0;
        }
        elseif ($format == 'Y-m-d H:i:s') {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $datetime, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = (int)$m[6];
        }
        elseif ($format == 'Y.m.d') {
            if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2})$/', $datetime, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'Y.m.d H:i') {
            if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2}) ([0-9]{2}):([0-9]{2})$/', $datetime, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = 0;
        }
        elseif ($format == 'Y.m.d H:i:s') {
            if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $datetime, $m)) return false;
            $year   = (int)$m[1];
            $month  = (int)$m[2];
            $day    = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = (int)$m[6];
        }
        elseif ($format == 'd.m.Y') {
            if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})$/', $datetime, $m)) return false;
            $year   = (int)$m[3];
            $month  = (int)$m[2];
            $day    = (int)$m[1];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'd.m.Y H:i') {
            if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4}) ([0-9]{2}):([0-9]{2})$/', $datetime, $m)) return false;
            $day    = (int)$m[1];
            $month  = (int)$m[2];
            $year   = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = 0;
        }
        elseif ($format == 'd.m.Y H:i:s') {
            if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $datetime, $m)) return false;
            $day    = (int)$m[1];
            $month  = (int)$m[2];
            $year   = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = (int)$m[6];
        }
        elseif ($format == 'd/m/Y') {
            if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $datetime, $m)) return false;
            $year   = (int)$m[3];
            $month  = (int)$m[2];
            $day    = (int)$m[1];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'd/m/Y H:i') {
            if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}):([0-9]{2})$/', $datetime, $m)) return false;
            $day    = (int)$m[1];
            $month  = (int)$m[2];
            $year   = (int)$m[3];
            $hour   = (int)$m[4];
            $minute = (int)$m[5];
            $second = 0;
        }
        elseif ($format == 'd/m/Y H:i:s') {
            if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $datetime, $m)) return false;
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
