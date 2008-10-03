<?
/**
 * TorHelper
 */
class TorHelper extends StaticClass {

   private $logDebug, $logInfo, $logNotice;  // boolean


   // TODO: Serverliste bei Fehlern dynamisch anpassen
   private static $torMirrors = array(
                                      'torstatus.kgprog.com'     ,
                                      'torstat.kleine-eismaus.de',
                                      'torstatus.amorphis.eu'    ,
                                      'torstatus.blutmagie.de'   ,
                                      'torstatus.cyberphunk.org' ,
                                      'tns.hermetix.org'         ,
                                      'arachne.doesntexist.org'  ,
                                      'torstatus.all.de'         ,
                           // https://'kradense.whsites.net/tns' ,
                                     );


   /**
    * Prüft, ob die übergebene IP-Adresse ein aktueller Tor-Exit-Node ist.
    *
    * @param string $ip - IP-Adresse
    *
    * @return boolean
    */
   public static function isExitNode($ip) {
      if (!is_string($ip)) throw new IllegalTypeException('Illegal type of parameter $ip: '.getType($ip));

      // TODO: mit Filter-Extension lokale Netze abfangen
      if ($ip == '127.0.0.1')
         return false;

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
               // TODO: Warnung ausgeben und Reihenfolge ändern, wenn ein Server nicht antwortet
               $response = CurlHttpClient ::create()->followRedirects(true)->send($request);
               $status = $response->getStatus();

               if ($status != 200) {
                  $this->logNotice && Logger ::log('Could not get Tor exit nodes from '.self::$torMirrors[$i].', HTTP status '.$status.' ('.HttpResponse ::$sc[$status]."),\n url: ".$request->getUrl(), L_NOTICE, __CLASS__);
                  continue;
               }
            }
            catch (IOException $ex) {
               $this->logNotice && Logger ::log('Could not get Tor exit nodes from '.self::$torMirrors[$i], $ex, L_NOTICE, __CLASS__);
               continue;
            }

            $content = trim($response->getContent());
            break;
         }

         $nodes = strLen($content) ? array_flip(explode("\n", str_replace("\r\n", "\n", $content))) : array();

         if (sizeOf($nodes) == 0) {
            Logger ::log('Could not get Tor exit nodes from any server', L_ERROR, __CLASS__);
         }

         Cache ::set($key, $nodes, 30 * MINUTE, null, __FILE__);
      }

      return $nodes;
   }
}
