<?
/**
 * Allgemeines Interface für HttpClient-Implementierungen.
 */
interface IHttpClient {


   /**
    * @param int $timeout - Time-out in Sekunden
    */
   public function setTimeout($timeout);
   public function getTimeout();


   /**
    * @param boolean $follow - ob einem Redirect-Header gefolgt werden soll oder nicht
    */
   public function setFollowRedirects($follow);
   public function isFollowRedirects();


   /**
    * @param int $redirects - maximale Anzahl der zu folgenden Redirect-Header
    */
   public function setMaxRedirects($redirects);
   public function getMaxRedirects();


   /**
    * Führt den übergebenen Request aus und gibt die empfangene Antwort zurück.
    *
    * @param HttpRequest $request
    *
    * @return HttpResponse
    */
   public function send(HttpRequest $request);
}
?>
