<?
/**
 * BaseValidator
 */
class BaseValidator extends Object {


   /**
    * Constructor
    */
   private function __construct() {
      throw new Exception('Do not instantiate this class.');
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
      static $emailAddressPattern = '/^([a-z0-9]+|[a-z0-9]+[a-z0-9_.-]+[a-z0-9]+)@((([a-z0-9]+|[a-z0-9]+[a-z0-9-]+[a-z0-9]+)\.)*)([a-z0-9][a-z0-9-]*[a-z0-9])\.([a-z]{2,4})$/';
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
    * Ob der übergebene String ein gültiges Datum darstellt (Format: yyyy-mm-dd).
    *
    * @param string $string - der zu überprüfende String
    * @param string $format - das Datumsformat, dem der String genügen soll
    *
    * @return boolean
    */
   public static function isDate($date, $format = 'Y-m-d') {
      if ($format == 'Y-m-d') {
         static $YmdPattern = '/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/';

         $matches = array();
         if (!is_string($date) || !preg_match_all($YmdPattern, $date, $matches, PREG_SET_ORDER))
            return false;

         $year  = $matches[0][1];
         $month = $matches[0][2];
         $day   = $matches[0][3];
         return checkDate((int) $month, (int) $day, (int) $year);
      }
      elseif ($format == 'd.m.Y') {
         static $dmYPattern = '/^([0-9]{2})\.([0-9]{2})\.([0-9]{4})$/';

         $matches = array();
         if (!is_string($date) || !preg_match_all($dmYPattern, $date, $matches, PREG_SET_ORDER))
            return false;

         $year  = $matches[0][3];
         $month = $matches[0][2];
         $day   = $matches[0][1];
         return checkDate((int) $month, (int) $day, (int) $year);
      }

      return false;
   }
}
?>
