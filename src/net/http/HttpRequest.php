<?php
namespace rosasurfer\net\http;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;


/**
 * HttpRequest
 *
 * Stellt einen HttpRequest dar.
 */
class HttpRequest extends Object {


   private /*string*/ $url;
   private /*string*/ $method  = 'GET';      // HTTP-Methode (default: GET)
   private /*array*/  $headers = array();    // zusätzliche benutzerdefinierte HTTP-Header


   /**
    * Erzeugt eine neue Instanz.
    *
    * @return self
    */
   public static function create() {
      return new static();
   }


   /**
    * Setzt die HTTP-Methode dieses Requests.
    *
    * @param  string $method - Methode, zur Zeit werden nur GET und POST unterstützt
    *
    * @return self
    */
   public function setMethod($method) {
      if (!is_string($method))                 throw new IllegalTypeException('Illegal type of parameter $method: '.getType($method));
      if ($method!=='GET' && $method!=='POST') throw new InvalidArgumentException('Invalid argument $method: '.$method);

      $this->method = $method;
      return $this;
   }


   /**
    * Gibt die HTTP-Methode dieses Requests zurück.
    *
    * @return string
    */
   public function getMethod() {
      return $this->method;
   }


   /**
    * Setzt die URL dieses Requests.
    *
    * @param  string $url - URL
    *
    * @return self
    */
   public function setUrl($url) {
      if (!is_string($url)) throw new IllegalTypeException('Illegal type of parameter $url: '.getType($url));

      // TODO: URL genauer validieren

      if (strPos($url, ' ') !== false)
         throw new InvalidArgumentException('Invalid argument $url: '.$url);

      $this->url = $url;
      return $this;
   }


   /**
    * Gibt die URL dieses Requests zurück.
    *
    * @return string $url
    */
   public function getUrl() {
      return $this->url;
   }


   /**
    * Setzt einen HTTP-Header. Ein bereits vorhandener Header desselben Namens wird überschrieben.
    *
    * @param  string $header - Name des Headers
    * @param  string $value  - Wert des Headers, NULL oder ein Leerstring löschen den entsprechenden Header
    *
    * @return self
    */
   public function setHeader($name, $value) {
      if (!is_string($name))                   throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
      if (!strLen($name))                      throw new InvalidArgumentException('Invalid argument $name: '.$name);

      if ($value!==null && !is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));
      if (!strLen($value))
         $value = null;

      $name  = trim($name);
      $value = trim($value);

      // alle vorhandenen Header dieses Namens suchen und löschen (unabhängig von Groß-/Kleinschreibung)
      $intersect = array_intersect_ukey($this->headers, array($name => 1), 'strCaseCmp');
      foreach ($intersect as $key => $vv) {
         unset($this->headers[$key]);
      }

      // ggf. neuen Header setzen
      if ($value !== null)
         $this->headers[$name] = $value;

      return $this;
   }


   /**
    * Fügt einen HTTP-Header zu den Headern dieses Requests hinzu. Bereit vorhandene gleichnamige Header werden nicht überschrieben,
    * sondern gemäß RFC zu einem gemeinsamen Header kombiniert.
    *
    * @param  string $header - Name des Headers
    * @param  string $value  - Wert des Headers
    *
    * @return self
    *
    * @see http://stackoverflow.com/questions/3241326/set-more-than-one-http-header-with-the-same-name
    */
   public function addHeader($name, $value) {
      if (!is_string($name))  throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
      if (!strLen($name))     throw new InvalidArgumentException('Invalid argument $name: '.$name);

      if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));
      if (!strLen($value))    throw new InvalidArgumentException('Invalid argument $value: '.$value);

      $name  = trim($name);
      $value = trim($value);

      // vorhandene Header dieses Namens suchen und löschen (unabhängig von Groß-/Kleinschreibung)
      $intersect = array_intersect_ukey($this->headers, array($name => 1), 'strCaseCmp');
      foreach ($intersect as $key => $vv) {
         unset($this->headers[$key]);
      }

      // vorherige Header-Werte mit dem zusätzlichen Wert kombinieren und einen gemeinsamen Header setzen (@see RFC)
      $intersect[] = $value;
      $this->headers[$name] = implode(', ', $intersect);

      return $this;
   }


   /**
    * Gibt die angegebenen Header dieses HttpRequests als Array von Name-Wert-Paaren zurück.
    *
    * @param  string|array $names - ein oder mehrere Namen; ohne Angabe werden alle Header zurückgegeben
    *
    * @return array - Name-Wert-Paare
    */
   public function getHeaders($names = null) {
      if     ($names === null)   $names = array();
      elseif (is_string($names)) $names = array($names);
      elseif (is_array($names)) {
         foreach ($names as $name) {
            if (!is_string($name)) throw new IllegalTypeException('Illegal parameter type in argument $names: '.getType($name));
         }
      }
      else                         throw new IllegalTypeException('Illegal type of parameter $names: '.getType($names));

      // alle oder nur die gewünschten Header zurückgeben
      if (!$names)
         return $this->headers;
      return array_intersect_ukey($this->headers, array_flip($names), 'strCaseCmp');
   }


   /**
    * Gibt den angegebenen Header dieses HttpRequest zurück.
    *
    * @param  string $name - Name des Headers (Groß-/Kleinschreibweise wird ignoriert)
    *
    * @return string - Wert des Headers oder NULL, wenn kein Header dieses Namens konfiguriert wurde
    */
   public function getHeader($name) {
      if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.getType($name));
      if (!strLen($name))    throw new InvalidArgumentException('Invalid argument $name: '.$name);

      $headers = $this->getHeaders($name);
      if ($headers)
         return join(', ', $headers);
      return null;
   }
}