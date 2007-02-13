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
    * Ob der angegebene String eine syntaktisch gültige E-Mail-Adresse ist. Handelt es sich um eine AOL-Adresse,
    * wird auch die AOL-Syntax überprüft (Format: http://postmaster.info.aol.com/faq/mailerfaq.html#syntax)
    *
    * @param string $string - der zu überprüfende String
    *
    * @return boolean
    */
   public static function isEmailAddress($string) {
      static $emailAddressPattern = '/^([a-z0-9]+|[a-z0-9]+[a-z0-9_.-]+[a-z0-9]+)@((([a-z0-9]+|[a-z0-9]+[a-z0-9-]+[a-z0-9]+)\.)*)([a-z0-9][a-z0-9-]*[a-z0-9])\.([a-z]{2,4})$/';

      $result = is_string($string) && !empty($string) && (boolean) preg_match($emailAddressPattern, strToLower($string), $matches);

      if ($result) {
         $domain = $matches[5];
         $tld    = $matches[6];

         if ($domain == 'aol') {
            if ($tld != 'com') {
               $result = false;                    // es gibt nur aol.com-Adressen
            }
            else {
               static $aolUsernamePattern = '/^[a-z][a-z0-9]{2,15}$/';
               $username = $matches[1];
               $result = (boolean) preg_match($aolUsernamePattern, $username);
            }
         }
      }

      return $result;
   }
}
?>
