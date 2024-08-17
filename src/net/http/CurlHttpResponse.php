<?php
namespace rosasurfer\net\http;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidArgumentException;


/**
 * CurlHttpResponse
 *
 * Represents the HTTP response to an HTTP request made by cURL.
 */
class CurlHttpResponse extends HttpResponse {


    /** @var HeaderParser */
    protected $headerParser;

    /** @var int - HTTP status code */
    protected $status;

    /** @var string - content */
    protected $content;

    /** @var int - length of the currently read content in bytes */
    protected $currentContentLength = 0;


    /**
     * Constructor
     */
    public function __construct() {
        $this->headerParser = new HeaderParser();
    }


    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }


    /**
     * Set the HTTP status code.
     *
     * @param  int $status - status code
     *
     * @return $this
     */
    public function setStatus($status) {
        Assert::int($status);
        if ($status < 1) throw new InvalidArgumentException('Invalid argument $status: '.$status);

        $this->status = $status;
        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @return array<string, string[]> - associative array of all received headers
     */
    public function getHeaders() {
        return $this->headerParser->getHeaders();
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $name - header name
     *
     * @return string|string[]|null - single header, array of multi-headers or NULL if no such header was received
     */
    public function getHeader($name) {
        return $this->headerParser->getHeader($name);
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $name - header name
     *
     * @return bool
     */
    public function isHeader($name) {
        return $this->headerParser->isHeader($name);
    }


    /**
     * Callback for CurlHttpClient, called with the received HTTP response headers (line by line).
     *
     * @param  resource $hCurl - curl handle of the processed HTTP request
     * @param  string   $line  - a single full header line consisting of header name, colon and header value
     *
     * @return int - number of bytes read (the length of the received header line)
     */
    public function writeHeader($hCurl, $line) {
        $this->headerParser->parseLine($line);
        return strlen($line);
    }


    /**
     * Callback for CurlHttpClient, called with the received HTTP content (in chunks).
     *
     * @param  resource $hCurl - curl handle of the processed HTTP request
     * @param  string   $data  - chunk of received content data
     *
     * @return int - number of bytes read (the length of the received content chunk)
     */
    public function writeContent($hCurl, $data) {
        $this->content .= $data;

        $obtainedLength = strlen($data);
        $this->currentContentLength += $obtainedLength;

        return $obtainedLength;
    }


    /**
     * {@inheritdoc}
     *
     * @return string - content
     */
    public function getContent() {
        return (string)$this->content;
    }
}
