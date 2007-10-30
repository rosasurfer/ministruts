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
   public function setFollowLocation($follow);
   public function isFollowLocation();


   /**
    * @param int $redirects - maximale Anzahl der zu folgenden Redirect-Header
    */
   public function setMaxRedirects($redirects);
   public function getMaxRedirects();


   /**
    * Führt den Request aus und gibt die empfangene Antwort zurück.
    *
    * @return HttpResponse
    */
   public function send(HttpRequest $request);
}
?>
