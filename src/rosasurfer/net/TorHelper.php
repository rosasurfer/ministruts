<?php
use rosasurfer\cache\Cache;

use rosasurfer\core\StaticClass;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\IOException;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_ERROR;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\MINUTES;


/**
 * TorHelper
 */
class TorHelper extends StaticClass {


   private static /*bool*/ $logDebug, $logInfo, $logNotice;


   // TODO: Serverliste bei Fehlern dynamisch anpassen
   private static $torMirrors = array('torstatus.blutmagie.de'   ,
                                      'torstatus.cyberphunk.org' ,
                                      'tns.hermetix.org'         ,
                                      'arachne.doesntexist.org'  ,
                                      'torstatus.all.de'         ,
                                      'torstatus.kgprog.com'     ,
                                      'torstatus.amorphis.eu'    ,
                                      'torstat.kleine-eismaus.de',
                           // https://'kradense.whsites.net/tns' ,
                                     );

   /**
    * Initialisiert die Klasse.
    */
   private static function init() {
      if (self::$logDebug === null) {
         $loglevel        = Logger ::getLogLevel(__CLASS__);
         self::$logDebug  = ($loglevel <= L_DEBUG );
         self::$logInfo   = ($loglevel <= L_INFO  );
         self::$logNotice = ($loglevel <= L_NOTICE);
      }
   }


   /**
    * Prüft, ob die übergebene IP-Adresse ein aktueller Tor-Exit-Node ist.
    *
    * @param  string $ip - IP-Adresse
    *
    * @return bool
    */
   public static function isExitNode($ip) {
      self:: init();

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
      $cache = Cache::me(__CLASS__);
      $nodes = $cache->get($key='tor_exit_nodes');

      if ($nodes == null) {
         $lock = new Lock();              // Einlesen der Nodes synchronisieren
            $nodes = $cache->get($key);

            if ($nodes == null) {
               $content = null;
               $size = sizeOf(self::$torMirrors);

               for ($i=0; $i < $size; ++$i) {
                  $request = HttpRequest ::create()->setUrl('http://'.self::$torMirrors[$i].'/ip_list_exit.php/Tor_ip_list_EXIT.csv');
                  try {
                     // TODO: Warnung ausgeben und Reihenfolge ändern, wenn ein Server nicht antwortet
                     $response = CurlHttpClient ::create()
                                                ->send($request);
                     $status = $response->getStatus();

                     if ($status != 200) {
                        self::$logNotice && Logger::log('Could not get TOR exit nodes from '.self::$torMirrors[$i].', HTTP status '.$status.' ('.HttpResponse ::$sc[$status]."),\n url: ".$request->getUrl(), null, L_NOTICE, __CLASS__);
                        continue;
                     }
                  }
                  catch (IOException $ex) {
                     self::$logNotice && Logger::log('Could not get TOR exit nodes from '.self::$torMirrors[$i], $ex, L_NOTICE, __CLASS__);
                     continue;
                  }

                  $content = trim($response->getContent());
                  break;
               }

               $nodes = strLen($content) ? array_flip(explode("\n", str_replace("\r\n", "\n", $content))) : array();

               if (!$nodes)
                  Logger::log('Could not get TOR exit nodes from any server', null, L_ERROR, __CLASS__);

               $cache->set($key, $nodes, 30 * MINUTES);
            }

         $lock->release();
      }
      return $nodes;
   }
}
