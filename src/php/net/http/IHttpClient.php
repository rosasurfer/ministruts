<?
/**
 * Allgemeines Interface für HttpClient-Implementierungen.
 */
interface IHttpClient {


   /**
    * @param int $timeout - Time-out in Sekunden
    */
   function setTimeout($timeout);
   function getTimeout();


   /**
    * @param boolean $follow - ob einem Redirect-Header gefolgt werden soll oder nicht
    */
   function setFollowRedirects($follow);
   function isFollowRedirects();


   /**
    * @param int $redirects - maximale Anzahl der zu folgenden Redirect-Header
    */
   function setMaxRedirects($redirects);
   function getMaxRedirects();


   /**
    * Führt den übergebenen Request aus und gibt die empfangene Antwort zurück.
    *
    * @param HttpRequest $request
    *
    * @return HttpResponse
    */
   function send(HttpRequest $request);
}
?>
