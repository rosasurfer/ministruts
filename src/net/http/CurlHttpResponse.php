<?php
namespace rosasurfer\net\http;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;


/**
 * CurlHttpResponse
 *
 * Stellt die Anwort auf einen von Curl gestellten HttpRequest dar.
 */
class CurlHttpResponse extends HttpResponse {


    /** @var HeaderParser */
    private $headerParser;

    /** @var int - HTTP-Statuscode */
    private $status;

    /** @var string - Content */
    private $content;

    /** @var int - aktuelle Laenge des gelesenen Contents in Byte */
    private $currentContentLength = 0;


    /**
     * Erzeugt eine neue Instanz.
     */
    public function __construct() {
        $this->headerParser = new HeaderParser();
    }


    /**
     * Setzt den HTTP-Status.
     *
     * @param  int $status - HTTP-Statuscode
     *
     * @return $this
     */
    public function setStatus($status) {
        if (!is_int($status)) throw new IllegalTypeException('Illegal type of parameter $status: '.getType($status));
        if ($status < 1)      throw new InvalidArgumentException('Invalid argument $status: '.$status);

        $this->status = $status;
        return $this;
    }


    /**
     * Gibt den HTTP-Status zurueck.
     *
     * @return int - Statuscode
     */
    public function getStatus() {
        return $this->status;
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
     * Gibt den Header mit dem angegebenen Namen zurueck.
     *
     * @param  string $name - Name des Headers
     *
     * @return string
     */
    public function getHeader($name) {
        return $this->headerParser->getHeader($name);
    }


    /**
     * Gibt alle empfangenen Header zurueck.
     *
     * @return array - Array mit Headern
     */
    public function getHeaders() {
        return $this->headerParser->getHeaders();
    }


    /**
     * Callback fuer CurlHttpClient, dem die empfangenen Response-Header zeilenweise uebergeben werden.
     *
     * @param  resource $hCurl - das CURL-Handle des aktuellen Requests
     * @param  string   $line  - vollstaendige Headerzeile, bestehend aus dem Namen, einem Doppelpunkt und den Daten
     *
     * @return int - Anzahl der bei diesem Methodenaufruf erhaltenen Bytes
     */
    public function writeHeader($hCurl, $line) {
        $this->headerParser->parseLine($line);
        return strLen($line);
    }


    /**
     * Callback fuer CurlHttpClient, dem der empfangene Content des HTTP-Requests chunk-weise uebergeben wird.
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
     * Gibt den Content des HttpResponse zurueck.
     *
     * @return string - Content
     */
    public function getContent() {
        return $this->content;
    }
}
