<?
/**
 * Header Utilities
 */
final class HeaderUtils extends StaticClass {


   /**
    * Sendet einen Redirect-Header mit der angegebenen URL. Danach wird das Script beendet.
    *
    * @param string $url - URL
    */
   public static function redirect($url) {
      $request = Request ::me();

      // TODO: Umleitung auf relative URL's funktioniert nicht: home "./" -> http://domain/content./
      if ($request->isSession()) {
         $session = $request->getSession();
         if ($session->isNew() || SID !== '') {   // bleiben wir innerhalb der Domain und Cookies sind aus, wird eine evt. Session-ID weitergegeben
            $host = strToLower(!empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
            $found = preg_match_all('/^https?:\/{2,}([a-z0-9-]+(\.[a-z0-9-]+)*)*.*$/', strToLower(trim($url)), $matches, PREG_SET_ORDER);

            if (!$found || $matches[0][1]==$host) {               // SID anhängen
               $url .= (String ::contains($url, '?') ? ini_get('arg_separator.output') : '?').SID;
            }
         }
         session_write_close();
      }
      // TODO: QueryString muß kodiert werden
      header('Location: '.$url);
      exit();                       // Ausgabe weiteren Contents verhindern

      /** TODO: HTTP/1.1 requires an absolute URI as argument to 'Location:' including the scheme, hostname and
       *        absolute path, but some clients accept relative URIs. You can usually use $_SERVER['HTTP_HOST'],
       *        $_SERVER['PHP_SELF'] and dirname() to make an absolute URI from a relative one yourself.
       */
   }
}
