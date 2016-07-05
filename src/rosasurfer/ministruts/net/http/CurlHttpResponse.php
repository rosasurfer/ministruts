<?php
use rosasurfer\ministruts\exceptions\IllegalTypeException;
use rosasurfer\ministruts\exceptions\InvalidArgumentException;

use const rosasurfer\L_DEBUG;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;


/**
 * CurlHttpResponse
 *
 * Stellt die Anwort auf einen von Curl gestellten HttpRequest dar.
 */
final class CurlHttpResponse extends HttpResponse {


   private static /*bool*/ $logDebug, $logInfo, $logNotice;


   private /*HeaderParser*/ $headerParser;
   private /*int*/          $status;         // HTTP-Statuscode
   private /*string*/       $content;        // Content

   // aktuelle Länge des gelesenen Contents in Byte
   private /*int*/ $currentContentLength = 0;


   /**
    * Erzeugt eine neue Instanz.
    */
   public function __construct() {
      $loglevel        = Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      $this->headerParser = HeaderParser ::create();
   }


   /**
    * Erzeugt eine neue Instanz.
    *
    * @return CurlHttpResponse
    */
   public static function create() {
      return new self();
   }


   /**
    * Setzt den HTTP-Status.
    *
    * @param  int $status - HTTP-Statuscode
    *
    * @return CurlHttpResponse
    */
   public function setStatus($status) {
      if (!is_int($status)) throw new IllegalTypeException('Illegal type of parameter $status: '.getType($status));
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
    * @param  string $name - Name des Headers
    *
    * @return bool
    */
   public function isHeader($name) {
      return $this->headerParser->isHeader($name);
   }


   /**
    * Gibt den Header mit dem angegebenen Namen zurück.
    *
    * @param  string $name - Name des Headers
    *
    * @return string
    */
   public function getHeader($name) {
      return $this->headerParser->getHeader($name);
   }


   /**
    * Callback für CurlHttpClient, dem die empfangenen Response-Header zeilenweise übergeben werden.
    *
    * @param  resource $hCurl - das CURL-Handle des aktuellen Requests
    * @param  string   $line  - vollständige Headerzeile, bestehend aus dem Namen, einem Doppelpunkt und den Daten
    *
    * @return int - Anzahl der bei diesem Methodenaufruf erhaltenen Bytes
    */
   public function writeHeader($hCurl, $line) {
      self::$logDebug && Logger ::log('Header line received:  '.$line, L_DEBUG, __CLASS__);

      $this->headerParser->parseLine($line);
      return strLen($line);
   }


   /**
    * Callback für CurlHttpClient, dem der empfangene Content des HTTP-Requests chunk-weise übergeben wird.
    *
    * @param  resource $hCurl - das CURL-Handle des aktuellen Requests
    * @param  string   $data  - die empfangenen Daten
    *
    * @return int - Anzahl der bei diesem Methodenaufruf erhaltenen Bytes
    */
   public function writeContent($hCurl, $data) {
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
