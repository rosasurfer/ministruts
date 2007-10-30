<?
/**
 * HttpRequest
 *
 * Stellt einen HttpRequest dar.
 */
final class HttpRequest extends Object {


   // Http-Methode des Requests (default: GET)
   private $method = 'GET';

   // URL
   private $url;


   /**
    * Erzeugt eine neue Instanz.
    *
    * @return HttpRequest
    */
   public static function create() {
      return new self;
   }


   /**
    * Setzt die HTTP-Methode dieses Requests.
    *
    * @param string $method - Methode, zur Zeit werden nur GET und POST unterstützt
    *
    * @return HttpRequest
    */
   public function setMethod($method) {
      if (!is_string($method))                 throw new IllegalTypeException('Illegal type of argument $method: '.getType($method));
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
    * @param string $url - URL
    *
    * @return HttpRequest
    */
   public function setUrl($url) {
      $this->url = $url;
      return $this;
   }


   /**
    * Gibt due URL dieses Requests zurück.
    *
    * @return string $url
    */
   public function getUrl() {
      return $this->url;
   }
}
?>
