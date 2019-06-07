<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;


/**
 * Validator
 */
class Validator extends StaticClass {


    /**
     * Ob der uebergebene String eine syntaktisch gueltige IP-Adresse ist.
     *
     * @param  string $string                 - der zu ueberpruefende String
     * @param  bool   $returnBytes [optional] - Typ des Rueckgabewertes
     *                                          FALSE: Boolean (default)
     *                                          TRUE:  Array mit den Adressbytes oder FALSE, wenn der String keine gueltige IP-Adresse darstellt
     * @return bool|array
     */
    public static function isIPAddress($string, $returnBytes = false) {
        static $pattern = '/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/';

        $result = is_string($string) && strlen($string) && preg_match($pattern, $string, $bytes);

        if ($result) {
            \array_shift($bytes);

            foreach ($bytes as $i => $byte) {
                $b = (int) $byte;
                if (!is_string($byte) || $b > 255)
                    return false;
                $bytes[$i] = $b;
            }

            if ($bytes[0] == 0)
                return false;

            return $returnBytes ? $bytes : true;
        }
        return false;
    }


    /**
     * Ob der uebergebene String eine syntaktisch gueltige IP-Adresse eines lokalen Netzwerks ist.
     *
     * @param  string $string - der zu ueberpruefende String
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
     * Ob der uebergebene String eine syntaktisch gueltige IP-Adresse eines externen Netzwerks ist.
     *
     * @param  string $string - der zu ueberpruefende String
     *
     * @return bool
     */
    public static function isIPWanAddress($string) {
        $bytes = self::isIPAddress($string, true);

        // Die Logik entspricht dem Gegenteil von self:: isIPLanAdress() + zusaetzlicher Tests.
        if ($bytes) {
            if ($bytes[0] == 10)                   // 10.0.0.0 - 10.255.255.255
                return false;

            if ($bytes[0] == 127)                  // 127.0.0.0 - 127.255.255.255
                return false;

            if ($bytes[0]==169)                    // 169.0.0.0 - 169.255.255.255 !!! wem zugewiesen? niemandem?
                return false;

            if ($bytes[0] == 172)                  // 172.16.0.0 - 172.31.255.255
                return !(15 < $bytes[1] && $bytes[1] < 32);

            if ($bytes[0]==192)                    // 192.168.0.0 - 192.168.255.255
                return ($bytes[1]!=168);
        }

        // dieses TRUE ist eher spekulativ
        return true;
    }


    /**
     * Ob der uebergebene String eine syntaktisch gueltige E-Mail-Adresse ist.
     *
     * @param  string $string - der zu ueberpruefende String
     *
     * @return bool
     */
    public static function isEmailAddress($string) {

        // TODO: Adressen in IP-Notation korrekt validieren -> root@[127.0.0.1]

        return filter_var($string, FILTER_VALIDATE_EMAIL);
    }


    /**
     * Ob der uebergebene String ein gueltiges E-Mail-Adressmuster ist&#46;  Wildcards sind ? und *.
     *
     * @param  string $string - der zu ueberpruefende String
     *
     * @return bool
     */
    public static function isEmailAddressPattern($string) {

        // TODO: Adressen in IP-Notation korrekt validieren -> root@[127.0.0.1]

        static $pattern = '/^[a-z0-9?*+-]+[a-z0-9?*_.+-]*@(([a-z0-9?*]+|[a-z0-9?*]+[a-z0-9?*-]+[a-z0-9?*]+)\.)*(\*|[a-z0-9?*][a-z0-9?*-]*[a-z0-9?*])\.(\*|[a-z?*]{2,4})$/';
        return is_string($string) && strlen($string) && preg_match($pattern, strtolower($string));
    }


    /**
     * Ob der uebergebene String einen gueltigen Date/DateTime-Wert darstellt.
     *
     * @param  string          $date              - der zu ueberpruefende String
     * @param  string|string[] $format [optional] - Format, dem der String entsprechen soll. Sind mehrere angegeben, muss der String
     *                                              mindestens einem davon entsprechen.
     *
     * @return int|bool - Timestamp oder FALSE, wenn der uebergebene Wert ungueltig ist
     *
     * Unterstuetzte Formate: 'Y-m-d [H:i[:s]]'
     *                       'Y.m.d [H:i[:s]]'
     *                       'd.m.Y [H:i[:s]]'
     *                       'd/m/Y [H:i[:s]]'
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

        /*
        // !!! deaktiviert!!!: strPTime() haelt sich nicht 100% an das angegebene Format sondern versucht, intelligent zu sein
        if (!WINDOWS) {
            if     ($format == 'Y-m-d'      ) $data = strPTime($date, '%Y-%m-%d');
            elseif ($format == 'Y-m-d H:i'  ) $data = strPTime($date, '%Y-%m-%d %H:%M');
            elseif ($format == 'Y-m-d H:i:s') $data = strPTime($date, '%Y-%m-%d %H:%M:%S');
            elseif ($format == 'd.m.Y'      ) $data = strPTime($date, '%d.%m.%Y');
            elseif ($format == 'd.m.Y H:i'  ) $data = strPTime($date, '%d.%m.%Y %H:%M');
            elseif ($format == 'd.m.Y H:i:s') $data = strPTime($date, '%d.%m.%Y %H:%M:%S');
            else                              $data = strPTime($date, $format);
            return ($data !== false && checkdate($data['tm_mon']+1, $data['tm_mday'], $data['tm_year']+1900) && $data['tm_sec'] <= 59 && $data['unparsed']=='');
        }
      */
        $year = $month = $day = $hour = $minute = $second = null;

        if ($format == 'Y-m-d') {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $date, $m)) return false;
            $year   = $m[1];
            $month  = $m[2];
            $day    = $m[3];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'Y-m-d H:i') {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $year   = $m[1];
            $month  = $m[2];
            $day    = $m[3];
            $hour   = $m[4];
            $minute = $m[5];
            $second = 0;
        }
        elseif ($format == 'Y-m-d H:i:s') {
            if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $year   = $m[1];
            $month  = $m[2];
            $day    = $m[3];
            $hour   = $m[4];
            $minute = $m[5];
            $second = $m[6];
        }
        elseif ($format == 'Y.m.d') {
            if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2})$/', $date, $m)) return false;
            $year   = $m[1];
            $month  = $m[2];
            $day    = $m[3];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'Y.m.d H:i') {
            if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2}) ([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $year   = $m[1];
            $month  = $m[2];
            $day    = $m[3];
            $hour   = $m[4];
            $minute = $m[5];
            $second = 0;
        }
        elseif ($format == 'Y.m.d H:i:s') {
            if (!preg_match('/^([0-9]{4})\.([0-9]{2})\.([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $year   = $m[1];
            $month  = $m[2];
            $day    = $m[3];
            $hour   = $m[4];
            $minute = $m[5];
            $second = $m[6];
        }
        elseif ($format == 'd.m.Y') {
            if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})$/', $date, $m)) return false;
            $year  = $m[3];
            $month = $m[2];
            $day   = $m[1];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'd.m.Y H:i') {
            if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4}) ([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $day    = $m[1];
            $month  = $m[2];
            $year   = $m[3];
            $hour   = $m[4];
            $minute = $m[5];
            $second = 0;
        }
        elseif ($format == 'd.m.Y H:i:s') {
            if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $day    = $m[1];
            $month  = $m[2];
            $year   = $m[3];
            $hour   = $m[4];
            $minute = $m[5];
            $second = $m[6];
        }
        elseif ($format == 'd/m/Y') {
            if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $date, $m)) return false;
            $year   = $m[3];
            $month  = $m[2];
            $day    = $m[1];
            $hour   = 0;
            $minute = 0;
            $second = 0;
        }
        elseif ($format == 'd/m/Y H:i') {
            if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $day    = $m[1];
            $month  = $m[2];
            $year   = $m[3];
            $hour   = $m[4];
            $minute = $m[5];
            $second = 0;
        }
        elseif ($format == 'd/m/Y H:i:s') {
            if (!preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date, $m)) return false;
            $day    = $m[1];
            $month  = $m[2];
            $year   = $m[3];
            $hour   = $m[4];
            $minute = $m[5];
            $second = $m[6];
        }
        else {
            return false;
        }
        return (checkdate($month, $day, $year) && $hour< 24 && $minute< 60 && $second< 60) ? mktime($hour, $minute, $second, (int)$month, (int)$day, (int)$year) : false;
    }


    /**
     * Ob der uebergebene String ein gueltiger Geschlechtsbezeichner ist.
     *
     * @param  string $string - der zu pruefende String
     *
     * @return bool
     */
    public static function isGender($string) {
        return ($string==='female' || $string==='male');
    }


    /**
     * Whether the passed string represents a valid phone number.
     *
     * @param  string $value
     *
     * @return bool
     */
    public static function isPhoneNumber($value) {
        if (!is_string($value)) return false;

        // handle empty value
        $value = trim($value);
        if (!strlen($value)) return false;

        // remove spaces
        $value = str_replace(' ', '', $value);

        // match numbers from BE, ES, BG, CH, A, UK, DE
        return (bool) preg_match('/^(00|\+)(32|34|359|41|43|44|49)-?[1-9](-?[0-9]){2,}$/', $value);

        // TODO: implement via https://github.com/giggsey/libphonenumber-for-php
    }


    /**
     * Ob der uebergebene String ein vollstaendiger Strassenname ist (Landstr&#46; [23]).
     *
     * @param  string $string - der zu pruefende String
     *
     * @return bool
     */
    public static function isAddress($string) {
        static $pattern = '/^([a-zäöüÄÖÜ](-?[a-zäöüßÄÖÜé])+\.? *)+[a-z0-9\/.-]*$/';

        return is_string($string) && strlen($string) && preg_match($pattern, strtolower($string));
    }


    /**
     * Ob der uebergebene String ein gueltiger Vorname ist.
     *
     * @param  string $string - der zu pruefende String
     *
     * @return bool
     */
    public static function isFirstName($string) {
        static $pattern = '/^([a-zäöüÄÖÜ]([\'-]?[a-zäöüßéÄÖÜ])+ *)+$/';
        return is_string($string) && strlen($string) && preg_match($pattern, strtolower($string));
    }


    /**
     * Ob der uebergebene String ein gueltiger Nachname ist.
     *
     * @param  string $string - der zu pruefende String
     *
     * @return bool
     */
    public static function isLastName($string) {
        return self::isFirstName($string);
    }


    /**
     * Ob der uebergebene String ein gueltiger Ortsname ist.
     *
     * @param  string $string - der zu pruefende String
     *
     * @return bool
     */
    public static function isPlaceName($string) {
        static $pattern = '/^([a-zäöüÄÖÜ](-?[a-zäöüßÄÖÜ])+\.? *)+$/';
        return is_string($string) && strlen($string) && preg_match($pattern, strtolower($string));
    }


    /**
     * Ob der uebergebene String ein gueltiger Strassenname ist.
     *
     * @param  string $string - der zu pruefende String
     *
     * @return bool
     */
    public static function isStreetName($string) {
        static $pattern = '/^[a-zäöüßÄÖÜ. -]{3,}$/';
        return is_string($string) && strlen($string) && preg_match($pattern, strtolower($string));
    }


    /**
     * Ob der uebergebene String eine gueltige Hausnummer ist.
     *
     * @param  string $string - der zu pruefende String
     *
     * @return bool
     */
    public static function isStreetNumber($string) {
        static $pattern = '/^[0-9A-Za-z-\/]+$/';
        return is_string($string) && strlen($string) && preg_match($pattern, strtolower($string));
    }
}
