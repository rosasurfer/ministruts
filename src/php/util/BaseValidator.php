<?
/**
 * BaseValidator
 */
class BaseValidator extends Object {


   /**
    * Ob der übergebene String eine syntaktisch richtige IP-Adresse ist.
    *
    * @param string $string - der zu überprüfende String
    *
    * @return boolean
    */
   public static function isIPAddress($string) {
      static $pattern = '/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/';

      $result = is_string($string) && strLen($string) && preg_match($pattern, $string, $matches);

      if ($result) {
         array_shift($matches);
         foreach ($matches as $part) {
            if ((strPos($part, '0') === 0 && $part!=='0') || (int) $part > 255) {
               $result = false;
               break;
            }
         }
      }

      return $result;
   }


   /**
    * Ob der übergebene String eine syntaktisch gültige E-Mail-Adresse ist. Handelt es sich um eine AOL-Adresse,
    * wird auch die AOL-Syntax überprüft (Format: http://postmaster.info.aol.com/faq/mailerfaq.html#syntax)
    *
    * @param string $string - der zu überprüfende String
    *
    * @return boolean
    */
   public static function isEmailAddress($string) {
      static $emailAddressPattern = '/^([a-z0-9]+|[a-z0-9]+[a-z0-9_.-]+)@((([a-z0-9]+|[a-z0-9]+[a-z0-9-]+[a-z0-9]+)\.)*)([a-z0-9][a-z0-9-]*[a-z0-9])\.([a-z]{2,4})$/';
      static $aolUsernamePattern  = '/^[a-z][a-z0-9]{2,15}$/';

      $result = is_string($string) && !empty($string) && (boolean) preg_match($emailAddressPattern, strToLower($string), $matches);

      if ($result) {
         $domain = $matches[5];
         $tld    = $matches[6];

         if ($domain == 'aol') {
            if ($tld != 'com') {
               $result = false;                    // es gibt nur aol.com-Adressen
            }
            else {
               $username = $matches[1];
               $result = (boolean) preg_match($aolUsernamePattern, $username);
            }
         }
      }

      return $result;
   }


   /**
    * Ob der übergebene String einen gültigen Date/DateTime-Wertdarstellt.
    *
    * @param string $string - der zu überprüfende String
    * @param string $format - das Format, dem der String genügen soll
    *
    * @return boolean
    */
   public static function isDate($date, $format = 'Y-m-d') {
      // wenn nicht unter Windows ...
      if (!WINDOWS) {
         if     ($format == 'Y-m-d'      ) $data = strPTime($date, '%Y-%m-%d');
         elseif ($format == 'Y-m-d H:i'  ) $data = strPTime($date, '%Y-%m-%d %H:%M');
         elseif ($format == 'Y-m-d H:i:s') $data = strPTime($date, '%Y-%m-%d %H:%M:%S');
         elseif ($format == 'd.m.Y'      ) $data = strPTime($date, '%d.%m.%Y');
         elseif ($format == 'd.m.Y H:i'  ) $data = strPTime($date, '%d.%m.%Y %H:%M');
         elseif ($format == 'd.m.Y H:i:s') $data = strPTime($date, '%d.%m.%Y %H:%M:%S');
         else                              $data = strPTime($date, $format);
         return ($data !== false && checkDate($data['tm_mon']+1, $data['tm_mday'], $data['tm_year']+1900) && $data['tm_sec'] <= 59 && $data['unparsed']=='');
      }

      // wenn unter Windows (dort ist strPTime() nicht implementiert) ...
      else {
         if ($format == 'Y-m-d') {
            static $YmdPattern = '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/';

            if (!preg_match($YmdPattern, $date, $matches))
               return false;

            $year  = $matches[1];
            $month = $matches[2];
            $day   = $matches[3];
            return checkDate((int) $month, (int) $day, (int) $year);
         }
         elseif ($format == 'Y-m-d H:i') {
            static $YmdHiPattern = '/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})$/';

            if (!preg_match($YmdHiPattern, $date, $matches))
               return false;

            $year   = $matches[1];
            $month  = $matches[2];
            $day    = $matches[3];
            $hour   = $matches[4];
            $minute = $matches[5];
            return (checkDate((int) $month, (int) $day, (int) $year) && (int) $hour < 24 && (int) $minute < 60);
         }
         elseif ($format == 'Y-m-d H:i:s') {
            static $YmdHisPattern = '/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/';

            if (!preg_match($YmdHisPattern, $date, $matches))
               return false;

            $year   = $matches[1];
            $month  = $matches[2];
            $day    = $matches[3];
            $hour   = $matches[4];
            $minute = $matches[5];
            $second = $matches[6];
            return (checkDate((int) $month, (int) $day, (int) $year) && (int) $hour < 24 && (int) $minute < 60 && (int) $second < 60);
         }
         elseif ($format == 'd.m.Y') {
            static $dmYPattern = '/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})$/';

            if (!preg_match($dmYPattern, $date, $matches))
               return false;

            $year  = $matches[3];
            $month = $matches[2];
            $day   = $matches[1];
            return checkDate((int) $month, (int) $day, (int) $year);
         }
         elseif ($format == 'd.m.Y H:i') {
            static $dmYHiPattern = '/^([0-9]{2})-([0-9]{2})-([0-9]{4}) ([0-9]{2}):([0-9]{2})$/';

            if (!preg_match($dmYHiPattern, $date, $matches))
               return false;

            $day    = $matches[1];
            $month  = $matches[2];
            $year   = $matches[3];
            $hour   = $matches[4];
            $minute = $matches[5];
            return (checkDate((int) $month, (int) $day, (int) $year) && (int) $hour < 24 && (int) $minute < 60);
         }
         elseif ($format == 'd.m.Y H:i:s') {
            static $dmYHisPattern = '/^([0-9]{2})-([0-9]{2})-([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/';

            if (!preg_match($dmYHisPattern, $date, $matches))
               return false;

            $day    = $matches[1];
            $month  = $matches[2];
            $year   = $matches[3];
            $hour   = $matches[4];
            $minute = $matches[5];
            $second = $matches[6];
            return (checkDate((int) $month, (int) $day, (int) $year) && (int) $hour < 24 && (int) $minute < 60 && (int) $second < 60);
         }
      }
      return false;
   }


   /**
    * Ob der übergebene String eine gültige Festnetztelefunnummer ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return boolean
    */
   public static function isFixedPhoneNo($string) {                        // !!! To-do: implementieren und Länderflag übergeben
      static $pattern = '/^\+?[0-9]{7,}$/';
      return is_string($string) && strLen($string) && preg_match($pattern, $string);
   }


   /**
    * Ob der übergebene String eine gültige Mobilfunknummer ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return boolean
    */
   public static function isCellPhoneNo($string) {                         // !!! To-do: implementieren und Länderflag übergeben
      static $pattern = '/^\+?[0-9]{7,}$/';
      return is_string($string) && strLen($string) && preg_match($pattern, $string);
   }


   /**
    * Ob der übergebene String ein vollständiger Straßenname ist (Landstr. [23]).
    *
    * @param  string $string - der zu prüfende String
    *
    * @return boolean
    */
   public static function isAddress($string) {
      static $pattern = '/^([a-zäöü][a-zäöüß-]*[a-zäöü.] *)+[a-z0-9\/.-]*$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }


   /**
    * Ob der übergebene String ein gültiger Vorname ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return boolean
    */
   public static function isFirstName($string) {
      static $pattern = '/^[a-zäöü]([\'-]?[a-zäöüß])+$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }


   /**
    * Ob der übergebene String ein gültiger Nachname ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return boolean
    */
   public static function isLastName($string) {
      return BaseValidator::isFirstName($string);
   }


   /**
    * Ob der übergebene String ein gültiger Ortsname ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return boolean
    */
   public static function isPlaceName($string) {
      static $pattern = '/^[a-zäöüß.-]{3,}$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }


   /**
    * Ob der übergebene String ein gültiger Straßenname ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return boolean
    */
   public static function isStreetName($string) {
      static $pattern = '/^[a-zäöüß. -]{3,}$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }


   /**
    * Ob der übergebene String eine gültige Hausnummer ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return boolean
    */
   public static function isStreetNumber($string) {
      static $pattern = '/^[0-9A-Za-z-\/]+$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }
}
?>
