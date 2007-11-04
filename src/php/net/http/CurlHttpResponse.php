<?
/**
 * CurlHttpResponse
 *
 * Stellt die Anwort auf einen von Curl gestellten HttpRequest dar.
 */
final class CurlHttpResponse implements HttpResponse {


   // HeaderParser
   private /*HeaderParser*/ $headerParser;

   // Status-Code
   private $status;        // int

   // Content
   private $content;       // string

   // aktuelle Länge des gelesenen Contents in Byte
   private $currentContentLength = 0;


   /**
    * Erzeugt eine neue Instanz.
    *
    * @return CurlHttpResponse
    */
   public function __construct() {
      $this->headerParser = HeaderParser ::create();
   }


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


   /**
    * Gibt die übertragenen Header zurück.
    *
    * @return array - Array mit Headern
    */
   public function getHeaders() {
      return $this->headerParser->getHeaders();
   }


   /**
    * Ob ein Header mit dem angegebenen Namen existiert.
    *
    * @param $name - Name des Headers
    *
    * @return boolean
    */
   public function isHeader($name) {
      return $this->headerParser->isHeader($name);
   }


   /**
    * Gibt den Header mit dem angegebenen Namen zurück.
    *
    * @param $name - Name des Headers
    *
    * @return string
    */
   public function getHeader($name) {
      return $this->headerParser->getHeader($name);
   }


   /**
    * Nur für internen Gebrauch:  Callback für CurlHttpClient, der der Content des HTTP-Requests chunk-weise
    * --------------------------  übergeben wird.
    *
    * @param resource $handle - das aktuelle CURL-Handle
    * @param string   $data   - die empfangenen Daten
    *
    * @return int - Anzahl der bei diesem Methodenaufruf erhaltenen Bytes
    */
   public function writeContent($resource, $data) {
      $this->content .= $data;
      $obtained = strLen($data);

      $this->currentContentLength += $obtained;
      return $obtained;
   }


   /**
    * Nur für internen Gebrauch:  Callback für CurlHttpClient, der die Response-Header zeilenweise übergeben werden.
    * --------------------------
    *
    * @param resource $handle - das aktuelle CURL-Handle
    * @param string   $line   - vollständige Headerzeile, bestehend aus dem Namen, einem Doppelpunkt und den Daten
    *
    * @return int - Anzahl der bei diesem Methodenaufruf erhaltenen Bytes
    */
   public function writeHeader($resource, $line) {
      $this->headerParser->parseLine($line);
      return strLen($line);
   }


   /**
    * Gibt den Content des HttpResponse zurück.
    *
    * @return string - Content
    */
   public function getContent() {
      return $this->content;
   }
}
?>
