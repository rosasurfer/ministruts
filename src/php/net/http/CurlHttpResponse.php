<?
/**
 * CurlHttpResponse
 *
 * Stellt die Anwort auf einen von Curl gestellten HttpRequest dar.
 */
final class CurlHttpResponse extends HttpResponse {

   private $logDebug, $logInfo, $logNotice;  // boolean


   private /*HeaderParser*/ $headerParser;
   private /*int*/          $status;         // Status-Code
   private /*string*/       $content;        // Content

   // aktuelle Länge des gelesenen Contents in Byte
   private $currentContentLength = 0;


   /**
    * Erzeugt eine neue Instanz.
    */
   public function __construct() {
      $loglevel = Logger ::getLogLevel(__CLASS__);

      $this->logDebug  = ($loglevel <= L_DEBUG );
      $this->logInfo   = ($loglevel <= L_INFO  );
      $this->logNotice = ($loglevel <= L_NOTICE);

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
    * Gibt die empfangenen Header zurück.
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
    * Callback für CurlHttpClient, dem die empfangenen Response-Header zeilenweise übergeben werden.
    *
    * @param resource $cHandle - das CURL-Handle des aktuellen Requests
    * @param string   $line    - vollständige Headerzeile, bestehend aus dem Namen, einem Doppelpunkt und den Daten
    *
    * @return int - Anzahl der bei diesem Methodenaufruf erhaltenen Bytes
    */
   public function writeHeader($cHandle, $line) {
      $this->logDebug && Logger ::log('Header line received:  '.$line, L_DEBUG, __CLASS__);

      $this->headerParser->parseLine($line);
      return strLen($line);
   }


   /**
    * Callback für CurlHttpClient, dem der empfangene Content des HTTP-Requests chunk-weise übergeben wird.
    *
    * @param resource $cHandle - das CURL-Handle des aktuellen Requests
    * @param string   $data    - die empfangenen Daten
    *
    * @return int - Anzahl der bei diesem Methodenaufruf erhaltenen Bytes
    */
   public function writeContent($cHandle, $data) {
      $this->content .= $data;

      $obtainedLength = strLen($data);
      $this->currentContentLength += $obtainedLength;

      return $obtainedLength;
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
