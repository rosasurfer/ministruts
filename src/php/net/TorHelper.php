<?
/**
 * TorHelper
 */
class TorHelper extends StaticClass {


   private static $torMirrors = array('torstatus.kgprog.com'    ,
                                      'torstatus.all.de'        ,
                                      'torstatus.blutmagie.de'  ,
                                      'torstatus.torproxy.net'  ,
                                      'tns.hermetix.org'        ,
                                      'arachne.doesntexist.org' ,
                                      'kradense.whsites.net/tns',
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
      $nodes = Cache ::get($key='exit_nodes', __FILE__);

      if ($nodes == null) {
         $content = null;
         $size = sizeOf(self::$torMirrors);

         for ($i=0; $i < $size; ++$i) {
            $request = HttpRequest ::create()->setUrl('http://'.self::$torMirrors[$i].'/ip_list_exit.php/Tor_ip_list_EXIT.csv');
            try {
               $response = CurlHttpClient ::create()
                                          ->followRedirects(true)
                                          ->send($request);
               $status = $response->getStatus();
               if ($status != 200) {
                  Logger ::log('Could not get Tor exit nodes, got HTTP status '.$status.' ('.HttpResponse ::$sc[$status].') for url: '.$request->getUrl(), L_NOTICE, __CLASS__);
                  continue;
               }
            }
            catch (IOException $ex) {
               Logger ::log('Could not get Tor exit nodes, got '.$ex.' for url: '.$request->getUrl(), L_NOTICE, __CLASS__);
               continue;
            }

            $content = trim($response->getContent());
            break;
         }

         $nodes = strLen($content) ? array_flip(explode("\n", str_replace("\r\n", "\n", $content))) : array();

         if (sizeOf($nodes) == 0) {
            Logger ::log('Could not get Tor exit nodes from any mirror', L_ERROR, __CLASS__);
         }

         Cache ::set($key, $nodes, 10 * MINUTE, null, __FILE__);
      }

      return $nodes;
   }
}
