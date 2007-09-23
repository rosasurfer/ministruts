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
      $nodes =& self:: getExitNodes();
      return isSet($nodes[$ip]);
   }


   /**
    * Gibt alle aktuellen Exit-Nodes zurück.
    *
    * @return array - assoziatives Array mit den IP-Adressen aller Exit-Nodes
    */
   private static function &getExitNodes() {
      static $nodes = null;

      if ($nodes === null) {
         //$handle = curl_init('http://torstatus.kgprog.com/ip_list_exit.php/Tor_ip_list_EXIT.csv');
         $handle = curl_init('http://torstatus.torproxy.net/ip_list_exit.php/Tor_ip_list_EXIT.csv');
         curl_setOpt($handle, CURLOPT_RETURNTRANSFER, true);
         curl_setOpt($handle, CURLOPT_BINARYTRANSFER, true);

         $response = curl_exec($handle);
         if ($response === false)
            throw new InfrastructureException('CURL error: '.CURL ::getError($handle));
         curl_close($handle);

         $nodes = array_flip(explode("\n", str_replace("\r\n", "\n", trim($response))));
      }

      return $nodes;
   }
}
