<?
/**
 * BaseValidator
 */
class BaseValidator extends Object {


   // Regex patterns.
   private static $emailAddressPattern = '/^[a-zA-Z0-9][a-zA-Z0-9_.-]*[a-zA-Z0-9]@([a-zA-Z0-9-]+\.){0,2}[a-zA-Z0-9][a-zA-Z0-9-]+[a-zA-Z0-9]\.[a-zA-Z]{2,4}$/';


   /**
    * Constructor
    */
   private function __construct() {
      throw new Exception('Do not instantiate this class.');
   }


   /**
    * Ob der angegebene String eine syntaktisch gültige E-Mail-Adresse ist.
    *
    * @param string address - der zu überprüfende String
    *
    * @return boolean
    */
   public static function isEmailAddress($address) {
      return is_string($address) && !empty($address) && (boolean) preg_match(self::$emailAddressPattern, $address);

      //return (strPos(strRev(strToLower($string)), 'ed.loa@')!==0 && preg_match(self::$emailAddressPattern, $string));
      // !!! aol.de Adressen werden nicht akzeptiert
      // !!! AOL-Format überprüfen
   }
}
?>
