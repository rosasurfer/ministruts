<?
/**
 * CurlHttpResponse
 *
 * Stellt die Anwort auf einen von Curl gestellten HttpRequest dar.
 */
final class CurlHttpResponse implements HttpResponse {


   // Status-Code
   private $status;


   /**
    * Erzeugt eine neue Instanz.
    *
    * @return CurlHttpResponse
    */
   public static function create() {
      return new self;
   }


   /**
    * Setzt den HTTP-Status.
    *
    * @param int $status - HTTP-Statuscode
    *
    * @return CurlHttpResponse
    */
   public function setStatus($status) {
      if (!is_int($status)) throw new IllegalTypeException('Illegal type of argument $status: '.getType($status));
      if ($status < 1)      throw new InvalidArgumentException('Invalid argument $status: '.$status);

      $this->status = $status;
      return $this;
   }


   /**
    * Gibt den HTTP-Status zurück.
    *
    * @return int - Statuscode
    */
   public function getStatus() {
      return $this->status;
   }
}
?>
