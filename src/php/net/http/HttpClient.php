<?
/**
 * Basisklasse für konkrete HttpClients.
 */
abstract class HttpClient extends Object {


   protected $timeout         = 60;              // int
   protected $followRedirects = false;           // boolean
   protected $maxRedirects    = 10;              // int
   protected $userAgent       = 'Mozilla/5.0';   // string


   /**
    * Setzt den Verbindungs-Timeout.
    *
    * @param int $timeout - Timeout in Sekunden
    *
    * @return HttpClient
    */
   public function setTimeout($timeout) {
      if (!is_int($timeout)) throw new IllegalTypeException('Illegal type of argument $timeout: '.getType($timeout));
      if ($timeout < 1)      throw new InvalidArgumentException('Invalid argument $timeout: '.$timeout);

      $this->timeout = $timeout;
      return $this;
   }


   /**
    * Gibt den eingestellten Verbindungs-Timeout zurück.
    *
    * @return int - Timeout in Sekunden
    */
   public function getTimeout() {
      return $this->timeout;
   }


   /**
    * Ob Redirect-Headern gefolgt werden soll oder nicht.
    *
    * @param boolean $follow
    *
    * @return HttpClient
    */
   public function setFollowRedirects($follow) {
      if ($follow!==true && $follow!==false) throw new IllegalTypeException('Illegal type of argument $follow: '.getType($follow));

      $this->followRedirects = $follow;
      return $this;
   }


   /**
    * Gibt die aktuelle Redirect-Einstellung zurück.
    *
    * @return boolean
    */
   public function isFollowRedirects() {
      return $this->followRedirects;
   }


   /**
    * Setzt die maximale Anzahl der Redirects, denen gefolgt werden soll.
    *
    * @param int $maxRedirects
    *
    * @return HttpClient
    */
   public function setMaxRedirects($maxRedirects) {
      $this->maxRedirects = $maxRedirects;
      return $this;
   }


   /**
    * Gibt die Anzahl der Redirects zurück, denen gefolgt wird.
    *
    * @return int
    */
   public function getMaxRedirects() {
      return $this->maxRedirects;
   }


   /**
    * Führt den übergebenen Request aus und gibt die empfangene Antwort zurück.  Diese Methode muß
    * von jedem Client implementiert werden.
    *
    * @param HttpRequest $request
    *
    * @return HttpResponse
    *
    * @throws IOException - wenn ein Fehler auftritt
    */
   abstract public function send(HttpRequest $request);
}
?>
