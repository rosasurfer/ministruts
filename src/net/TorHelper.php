<?php
namespace rosasurfer\net;

use rosasurfer\cache\Cache;
use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\IOException;
use rosasurfer\log\Logger;
use rosasurfer\net\http\CurlHttpClient;
use rosasurfer\net\http\HttpRequest;
use rosasurfer\net\http\HttpResponse;

use function rosasurfer\normalizeEOL;
use function rosasurfer\synchronized;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_ERROR;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\MINUTES;
use const rosasurfer\NL;


/**
 * TorHelper
 */
class TorHelper extends StaticClass {


    /** @var bool */
    private static $logDebug;

    /** @var bool */
    private static $logInfo;

    /** @var bool */
    private static $logNotice;

    /**
     * @todo Serverliste bei Fehlern dynamisch anpassen
     *
     * @var string[] */
    private static $torMirrors = [
        'torstatus.blutmagie.de'   ,
        'torstatus.cyberphunk.org' ,
        'tns.hermetix.org'         ,
        'arachne.doesntexist.org'  ,
        'torstatus.all.de'         ,
        'torstatus.kgprog.com'     ,
        'torstatus.amorphis.eu'    ,
        'torstat.kleine-eismaus.de',
      //'kradense.whsites.net/tns' ,
    ];


    /**
     * Initialisiert die Klasse.
     */
    private static function init() {
        if (self::$logDebug === null) {
            $loglevel        = Logger::getLogLevel(__CLASS__);
            self::$logDebug  = ($loglevel <= L_DEBUG );
            self::$logInfo   = ($loglevel <= L_INFO  );
            self::$logNotice = ($loglevel <= L_NOTICE);
        }
    }


    /**
     * Prueft, ob die uebergebene IP-Adresse ein aktueller Tor-Exit-Node ist.
     *
     * @param  string $ip - IP-Adresse
     *
     * @return bool
     */
    public static function isExitNode($ip) {
        self::init();
        Assert::string($ip);

        // TODO: mit Filter-Extension lokale Netze abfangen
        if ($ip == '127.0.0.1')
            return false;

        $nodes = self::getExitNodes();
        return isset($nodes[$ip]);
    }


    /**
     * Gibt die aktuellen Exit-Nodes zurueck.
     *
     * @return array - assoziatives Array mit den IP-Adressen aller Exit-Nodes
     */
    private static function getExitNodes() {
        $cache = Cache::me(__CLASS__);
        $nodes = $cache->get($key='tor_exit_nodes');

        if ($nodes == null) {

            // Einlesen der Nodes synchronisieren
            synchronized(function() use ($cache, $key, &$nodes) {
                $nodes = $cache->get($key);

                if ($nodes == null) {
                    $content = '';
                    $size = sizeof(self::$torMirrors);

                    for ($i=0; $i < $size; ++$i) {
                        $request = new HttpRequest('http://'.self::$torMirrors[$i].'/ip_list_exit.php/Tor_ip_list_EXIT.csv');
                        try {
                            // TODO: Warnung ausgeben und Reihenfolge aendern, wenn ein Server nicht antwortet
                            $response = (new CurlHttpClient())->send($request);
                            $status   = $response->getStatus();

                            if ($status != 200) {
                                self::$logNotice && Logger::log('Could not get TOR exit nodes from '.self::$torMirrors[$i].', HTTP status '.$status.' ('.HttpResponse::$sc[$status]."),\n URL: ".$request->getUrl(), L_NOTICE);
                                continue;
                            }
                        }
                        catch (IOException $ex) {
                            self::$logNotice && Logger::log('Could not get TOR exit nodes from '.self::$torMirrors[$i], L_NOTICE, ['exception'=>$ex]);
                            continue;
                        }

                        $content = trim($response->getContent());
                        break;
                    }

                    $nodes = strlen($content) ? \array_flip(explode(NL, normalizeEOL($content))) : [];

                    if (!$nodes) Logger::log('Could not get TOR exit nodes from any server', L_ERROR);

                    $cache->set($key, $nodes, 30 * MINUTES);
                }
            });
        }
        return $nodes;
    }
}

