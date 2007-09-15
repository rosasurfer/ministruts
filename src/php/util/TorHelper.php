<?
/**
 * TorHelper
 */
class TorHelper extends Object {


   /**
    * Prüft, ob die übergebene IP-Adresse ein aktueller Exit-Node ist.
    *
    * @param string $ip - IP-Adresse
    *
    * @return boolean   - TRUE, wenn die Adresse ein aktueller Exit-Node ist,
    *                     FALSE andererseits
    */
   public static function isExitNode($ip) {
      return false;
   }
}
?>
