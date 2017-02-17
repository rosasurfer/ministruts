<?php
namespace rosasurfer\net\http;

use rosasurfer\core\StaticClass;
use rosasurfer\ministruts\Request;

use function rosasurfer\strContains;


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
      $request = Request::me();

      // TODO: Umleitung auf relative URL's funktioniert nicht: home "./" -> http://domain/content./
      if ($request->isSession()) {
         $session = $request->getSession();
         if ($session->isNew() || SID !== '') {   // bleiben wir innerhalb der Domain und Cookies sind aus, wird eine evt. Session-ID weitergegeben
            // TODO: kompletter Unfug
            $found = preg_match_all('/^https?:\/{2,}([a-z0-9-]+(\.[a-z0-9-]+)*)*.*$/', strToLower(trim($url)), $matches, PREG_SET_ORDER);

            if (!$found || $matches[0][1]==$request->getHostname())     // SID anhaengen
               $url .= (strContains($url, '?') ? '&':'?').SID;
         }
         session_write_close();
      }
      // TODO: QueryString muss kodiert werden
      header('Location: '.$url);
      exit(0);                      // Ausgabe weiteren Contents verhindern

      // TODO: HTTP/1.1 requires an absolute URI as argument to 'Location:' including the scheme, hostname and
      //       absolute path, but some clients accept relative URIs.
   }
}
