<?php
use rosasurfer\core\StaticClass;
use rosasurfer\ministruts\Request;

use const rosasurfer\strContains;


/**
 * Header Utilities
 */
class HeaderUtils extends StaticClass {


   /**
    * Sendet einen Redirect-Header mit der angegebenen URL. Danach wird das Script beendet.
    *
    * @param  string $url - URL
    */
   public static function redirect($url) {
      $request = Request ::me();

      // TODO: Umleitung auf relative URL's funktioniert nicht: home "./" -> http://domain/content./
      if ($request->isSession()) {
         $session = $request->getSession();
         if ($session->isNew() || SID !== '') {   // bleiben wir innerhalb der Domain und Cookies sind aus, wird eine evt. Session-ID weitergegeben
            // TODO: kompletter Unfug
            $found = preg_match_all('/^https?:\/{2,}([a-z0-9-]+(\.[a-z0-9-]+)*)*.*$/', strToLower(trim($url)), $matches, PREG_SET_ORDER);

            if (!$found || $matches[0][1]==$request->getHostname())     // SID anhängen
               $url .= (strContains($url, '?') ? '&':'?').SID;
         }
         session_write_close();
      }
      // TODO: QueryString muß kodiert werden
      header('Location: '.$url);
      exit(0);                      // Ausgabe weiteren Contents verhindern

      /** TODO: HTTP/1.1 requires an absolute URI as argument to 'Location:' including the scheme, hostname and
       *        absolute path, but some clients accept relative URIs. You can usually use $_SERVER['HTTP_HOST'],
       *        $_SERVER['PHP_SELF'] and dirname() to make an absolute URI from a relative one yourself.
       */
   }
}
/*
# Author: Eric O
# Date: July 13, 2006
# Creating Automatic Self-Redirect To Secure Version
# of Website as Seen on Paypal and other secure sites
# Changes HTTP to HTTPS

#gets the URI of the script
$url =  $_SERVER['SCRIPT_URI']; // nur mit mod_rewrite on
#chops URI into bits BORK BORK BORK
$chopped = parse_url($url);
#HOST and PATH portions of your final destination
$destination = $chopped[host].$chopped[path];
#if you are not HTTPS, then do something about it
if($chopped[scheme] != "https"){
   #forwards to HTTP version of URI with secure certificate
   header("Location: https://$destination");
}
*/
