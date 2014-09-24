<?php
/**
 * CommonValidator
 */
class CommonValidator extends StaticClass {


   /**
    * Ob der übergebene String eine syntaktisch gültige IP-Adresse ist.
    *
    * @param string $string      - der zu überprüfende String
    * @param bool   $returnBytes - Typ des Rückgabewertes
    *                              FALSE: Boolean (default)
    *                              TRUE:  Array mit den Adressbytes oder FALSE, wenn der String keine gültige IP-Adresse darstellt
    * @return bool|array
    */
   public static function isIPAddress($string, $returnBytes = false) {
      static $pattern = '/^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$/';

      $result = is_string($string) && strLen($string) && preg_match($pattern, $string, $bytes);

      if ($result) {
         array_shift($bytes);

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
    * Ob der übergebene String eine syntaktisch gültige IP-Adresse eines lokalen Netzwerks ist.
    *
    * @param string $string - der zu überprüfende String
    *
    * @return bool
    */
   public static function isIPLanAddress($string) {
      $bytes = self ::isIPAddress($string, true);

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
    * Ob der übergebene String eine syntaktisch gültige IP-Adresse eines externen Netzwerks ist.
    *
    * @param string $string - der zu überprüfende String
    *
    * @return bool
    */
   public static function isIPWanAddress($string) {
      $bytes = self ::isIPAddress($string, true);

      // Die Logik entspricht dem Gegenteil von self:: isIPLanAdress() + zusätzlicher Tests.
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
    * Ob der übergebene String eine syntaktisch gültige E-Mail-Adresse ist.
    *
    * @param string $string - der zu überprüfende String
    *
    * @return bool
    */
   public static function isEmailAddress($string) {

      // TODO: Adressen in IP-Notation korrekt validieren -> root@[127.0.0.1]

      static $pattern = '/^[a-z0-9+-]+[a-z0-9_.+-]*@(([a-z0-9]+|[a-z0-9]+[a-z0-9-]+[a-z0-9]+)\.)*([a-z0-9][a-z0-9-]*[a-z0-9])\.([a-z]{2,4})$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }


   /**
    * Ob der übergebene String ein gültiges E-Mail-Adressmuster ist. Wildcards sind ? und *.
    *
    * @param string $string - der zu überprüfende String
    *
    * @return bool
    */
   public static function isEmailAddressPattern($string) {

      // TODO: Adressen in IP-Notation korrekt validieren -> root@[127.0.0.1]

      static $pattern = '/^[a-z0-9?*+-]+[a-z0-9?*_.+-]*@(([a-z0-9?*]+|[a-z0-9?*]+[a-z0-9?*-]+[a-z0-9?*]+)\.)*(\*|[a-z0-9?*][a-z0-9?*-]*[a-z0-9?*])\.(\*|[a-z?*]{2,4})$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }


   /**
    * Ob der übergebene String einen gültigen Date/DateTime-Wert darstellt.
    *
    * @param string $string - der zu überprüfende String
    * @param string $format - das Format, dem der String entsprechen soll
    *
    * @return bool
    *
    * TODO: isDate() überprüft bis jetzt nur 'Y-m-d' und 'd.m.Y', nicht 'd/m/Y'
    */
   public static function isDate($date, $format='Y-m-d') {
      if (!is_string($date))
         return false;

      /*
      // wenn nicht unter Windows ... !WINDOWS
      if (false) { // !!! strPTime() hält sich nicht 100% an das angegebene Format (versucht intelligent zu sein)
         if     ($format == 'Y-m-d'      ) $data = strPTime($date, '%Y-%m-%d');
         elseif ($format == 'Y-m-d H:i'  ) $data = strPTime($date, '%Y-%m-%d %H:%M');
         elseif ($format == 'Y-m-d H:i:s') $data = strPTime($date, '%Y-%m-%d %H:%M:%S');
         elseif ($format == 'd.m.Y'      ) $data = strPTime($date, '%d.%m.%Y');
         elseif ($format == 'd.m.Y H:i'  ) $data = strPTime($date, '%d.%m.%Y %H:%M');
         elseif ($format == 'd.m.Y H:i:s') $data = strPTime($date, '%d.%m.%Y %H:%M:%S');
         else                              $data = strPTime($date, $format);
         return ($data !== false && checkDate($data['tm_mon']+1, $data['tm_mday'], $data['tm_year']+1900) && $data['tm_sec'] <= 59 && $data['unparsed']=='');
      }
      */

      if ($format == 'Y-m-d') {
         if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $date, $m))
            return false;

         $year  = $m[1];
         $month = $m[2];
         $day   = $m[3];
         return checkDate($month, $day, $year);
      }

      elseif ($format == 'Y-m-d H:i') {
         if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2})$/', $date, $m))
            return false;

         $year   = $m[1];
         $month  = $m[2];
         $day    = $m[3];
         $hour   = $m[4];
         $minute = $m[5];
         return (checkDate($month, $day, $year) && $hour < 24 && $minute < 60);
      }

      elseif ($format == 'Y-m-d H:i:s') {
         if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date, $m))
            return false;

         $year   = $m[1];
         $month  = $m[2];
         $day    = $m[3];
         $hour   = $m[4];
         $minute = $m[5];
         $second = $m[6];
         return (checkDate($month, $day, $year) && $hour < 24 && $minute < 60 && $second < 60);
      }

      elseif ($format == 'd.m.Y') {
         if (!preg_match('/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})$/', $date, $m))
            return false;

         $year  = $m[3];
         $month = $m[2];
         $day   = $m[1];
         return checkDate($month, $day, $year);
      }

      elseif ($format == 'd.m.Y H:i') {
         if (!preg_match('/^([0-9]{2})-([0-9]{2})-([0-9]{4}) ([0-9]{2}):([0-9]{2})$/', $date, $m))
            return false;

         $day    = $m[1];
         $month  = $m[2];
         $year   = $m[3];
         $hour   = $m[4];
         $minute = $m[5];
         return (checkDate($month, $day, $year) && $hour < 24 && $minute < 60);
      }

      elseif ($format == 'd.m.Y H:i:s') {
         if (!preg_match('/^([0-9]{2})-([0-9]{2})-([0-9]{4}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $date, $m))
            return false;

         $day    = $m[1];
         $month  = $m[2];
         $year   = $m[3];
         $hour   = $m[4];
         $minute = $m[5];
         $second = $m[6];
         return (checkDate($month, $day, $year) && $hour < 24 && $minute < 60 && $second < 60);
      }

      return false;
   }


   /**
    * Ob der übergebene String ein gültiger Geschlechtsbezeichner ist.
    *
    * @param string $string - der zu prüfende String
    *
    * @return bool
    */
   public static function isGender($string) {
      return ($string==='female' || $string==='male');
   }


   /**
    * Ob der übergebene String eine gültige Festnetztelefunnummer ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return bool
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
    * @return bool
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
    * @return bool
    */
   public static function isAddress($string) {
      static $pattern = '/^([a-zäöüÄÖÜ](-?[a-zäöüßÄÖÜé])+\.? *)+[a-z0-9\/.-]*$/';

      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }


   /**
    * Ob der übergebene String ein gültiger Vorname ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return bool
    */
   public static function isFirstName($string) {
      static $pattern = '/^([a-zäöüÄÖÜ]([\'-]?[a-zäöüßéÄÖÜ])+ *)+$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }


   /**
    * Ob der übergebene String ein gültiger Nachname ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return bool
    */
   public static function isLastName($string) {
      return self:: isFirstName($string);
   }


   /**
    * Ob der übergebene String ein gültiger Ortsname ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return bool
    */
   public static function isPlaceName($string) {
      static $pattern = '/^([a-zäöüÄÖÜ](-?[a-zäöüßÄÖÜ])+\.? *)+$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }


   /**
    * Ob der übergebene String ein gültiger Straßenname ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return bool
    */
   public static function isStreetName($string) {
      static $pattern = '/^[a-zäöüßÄÖÜ. -]{3,}$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }


   /**
    * Ob der übergebene String eine gültige Hausnummer ist.
    *
    * @param  string $string - der zu prüfende String
    *
    * @return bool
    */
   public static function isStreetNumber($string) {
      static $pattern = '/^[0-9A-Za-z-\/]+$/';
      return is_string($string) && strLen($string) && preg_match($pattern, strToLower($string));
   }
}
?>
