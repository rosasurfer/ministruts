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
 *
 * @todo  https://www.irongeek.com/i.php?page=security/detect-tor-exit-node-in-php
 * @todo  https://stackoverflow.com/questions/37965753/what-is-the-modern-way-to-check-if-user-is-requesting-site-using-tor-php
 * @todo  https://github.com/Gratusfr/PHP-script-to-detect-Tor
 */
class TorHelper extends StaticClass {


    /** @var bool */
    private static $logDebug;

    /** @var bool */
    private static $logNotice;

    /**
     * @todo on error dynamically reduce the server list
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
     * Initialization
     *
     * @return void
     */
    private static function init() {
        if (self::$logDebug === null) {
            $loglevel        = Logger::getLogLevel(__CLASS__);
            self::$logDebug  = ($loglevel <= L_DEBUG );
            self::$logNotice = ($loglevel <= L_NOTICE);
        }
    }


    /**
     * Whether the specified IP address is a known Tor exit node.
     *
     * @param  string $ip - IP address
     *
     * @return bool
     */
    public static function isExitNode($ip) {
        self::init();
        Assert::string($ip);

        // TODO: filter local subnets
        if ($ip == '127.0.0.1')
            return false;

        $nodes = self::getExitNodes();
        return isset($nodes[$ip]);
    }


    /**
     * Return all currently known Tor exit nodes.
     *
     * @return array - associative array of IP addresses
     */
    private static function getExitNodes() {
        $cache = Cache::me(__CLASS__);
        $nodes = $cache->get($key='tor_exit_nodes');

        if ($nodes == null) {

            // synchronize reading of the nodes
            synchronized(function() use ($cache, $key, &$nodes) {
                $nodes = $cache->get($key);

                if ($nodes == null) {
                    $content = '';
                    $size = sizeof(self::$torMirrors);

                    for ($i=0; $i < $size; ++$i) {
                        $request = new HttpRequest('http://'.self::$torMirrors[$i].'/ip_list_exit.php/Tor_ip_list_EXIT.csv');
                        try {
                            // TODO: warn/update server list if a server doesn't respond
                            $response = (new CurlHttpClient())->send($request);
                            $status   = $response->getStatus();

                            if ($status != 200) {
                                $description = isset(HttpResponse::$statusCodes[$status]) ? HttpResponse::$statusCodes[$status] : '?';
                                self::$logNotice && Logger::log('Could not get TOR exit nodes from '.self::$torMirrors[$i].', HTTP status '.$status.' ('.$description."),\n URL: ".$request->getUrl(), L_NOTICE);
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
