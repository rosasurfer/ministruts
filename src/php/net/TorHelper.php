<?
/**
 * TorHelper
 */
class TorHelper extends StaticFactory {


   private static $mirrors = array('torstatus.kgprog.com',
                                   'torstatus.blutmagie.de',
                                   'torstatus.torproxy.net',
                                   'torstat.kleine-eismaus.de',
                                   'tns.hermetix.org',
                                   );


   /**
    * Prüft, ob die übergebene IP-Adresse ein aktueller Tor-Exit-Node ist.
    *
    * @param string $ip - IP-Adresse
    *
    * @return boolean
    */
   public static function isExitNode($ip) {
      $nodes =& self:: getExitNodes();
      return isSet($nodes[$ip]);
   }


   /**
    * Gibt die aktuellen Exit-Nodes zurück.
    *
    * @return array - assoziatives Array mit den IP-Adressen aller Exit-Nodes
    */
   private static function &getExitNodes() {
      $nodes = Cache ::get($key = __CLASS__.'_tor_exit_nodes');

      if ($nodes == null) {
         $url = $handle = $response = $error = null;
         $size = sizeOf(self::$mirrors);

         for ($i=0; $i < $size && !$response; ++$i) {
            $url = 'http://'.self::$mirrors[$i].'/ip_list_exit.php/Tor_ip_list_EXIT.csv';
            if ($handle !== null)
               curl_close($handle);
            $handle = curl_init($url);
            curl_setOpt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setOpt($handle, CURLOPT_BINARYTRANSFER, true);
            $response = curl_exec($handle);
            if (!$response) {
               $error = CURL ::getError($handle);
               //Logger ::log('CURL error: '.$error.', url: '.$url, L_NOTICE, __CLASS__);
            }
         }
         curl_close($handle);
         if (!$response)
            throw new InfrastructureException('Could not retrieve Tor exit nodes, CURL error: '.$error.', url: '.$url);

         $nodes = array_flip(explode("\n", str_replace("\r\n", "\n", trim($response))));

         Cache ::set($key, $nodes, 10 * MINUTE);
      }
      return $nodes;
   }
}
