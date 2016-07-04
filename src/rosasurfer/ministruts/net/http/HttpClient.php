<?php
use rosasurfer\ministruts\exceptions\IllegalTypeException;
use rosasurfer\ministruts\exceptions\InvalidArgumentException;
use rosasurfer\ministruts\exceptions\IOException;


/**
 * Basisklasse für konkrete HttpClients.
 */
abstract class HttpClient extends Object {


   // Default-Einstellungen
   protected /*int   */ $timeout         = 30;
   protected /*bool  */ $followRedirects = true;
   protected /*int   */ $maxRedirects    = 5;
   protected /*string*/ $userAgent       = 'Mozilla/5.0';


   /**
    * Setzt den Verbindungs-Timeout.
    *
    * @param  int $timeout - Timeout in Sekunden
    *
    * @return HttpClient
    */
   public function setTimeout($timeout) {
      if (!is_int($timeout)) throw new IllegalTypeException('Illegal type of parameter $timeout: '.getType($timeout));
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
    * @param  bool $follow
    *
    * @return HttpClient
    */
   public function setFollowRedirects($follow) {
      if (!is_bool($follow)) throw new IllegalTypeException('Illegal type of parameter $follow: '.getType($follow));

      $this->followRedirects = $follow;
      return $this;
   }


   /**
    * Gibt die aktuelle Redirect-Einstellung zurück.
    *
    * @return bool
    */
   public function isFollowRedirects() {
      return (bool) $this->followRedirects;
   }


   /**
    * Setzt die maximale Anzahl der Redirects, denen gefolgt werden soll.
    *
    * @param  int $maxRedirects
    *
    * @return HttpClient
    */
   public function setMaxRedirects($maxRedirects) {
      if (!is_int($maxRedirects)) throw new IllegalTypeException('Illegal type of parameter $maxRedirects: '.getType($maxRedirects));

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
    * @param  HttpRequest $request
    *
    * @return HttpResponse
    *
    * @throws IOException - wenn ein Fehler auftritt
    */
   abstract public function send(HttpRequest $request);
}
