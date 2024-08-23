<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\http;

use CurlHandle;
use rosasurfer\ministruts\core\exception\InvalidValueException;


/**
 * CurlHttpResponse
 *
 * Represents the HTTP response to an HTTP request made by cURL.
 */
class CurlHttpResponse extends HttpResponse {


    /** @var HeaderParser */
    protected HeaderParser $headerParser;

    /** @var int - HTTP status code */
    protected int $status;

    /** @var string - content */
    protected string $content;

    /** @var int - length of the currently read content in bytes */
    protected int $currentContentLength = 0;


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
    public function getStatus(): int {
        return $this->status;
    }


    /**
     * Set the HTTP status code.
     *
     * @param  int $status - status code
     *
     * @return $this
     */
    public function setStatus(int $status): self {
        if ($status < 1) throw new InvalidValueException('Invalid parameter $status: '.$status);
        $this->status = $status;
        return $this;
    }


    /**
     * {@inheritdoc}
     *
     * @return array<string, string[]> - associative array of all received headers
     */
    public function getHeaders(): array {
        return $this->headerParser->getHeaders();
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $name - header name
     *
     * @return string[] - array of received header values; empty if no such header was received
     */
    public function getHeaderValues(string $name): array {
        return $this->headerParser->getHeaderValues($name);
    }


    /**
     * {@inheritdoc}
     *
     * @param  string $name - header name
     *
     * @return bool
     */
    public function isHeader(string $name): bool {
        return $this->headerParser->isHeader($name);
    }


    /**
     * Callback for CurlHttpClient, called with the received HTTP response headers (line by line).
     *
     * @param  resource|CurlHandle $hCurl - cURL handle of the processed HTTP request
     * @phpstan-param  CurlHandleId $hCurl
     * @param  string   $line             - a single line from the received header section
     *
     * @return int - number of bytes read (the length of the received line)
     *
     */
    public function writeHeader($hCurl, string $line): int {
        $this->headerParser->parseHeaderLine($line);
        return strlen($line);
    }


    /**
     * Callback for CurlHttpClient, called with the received HTTP content (in chunks).
     *
     * @param  resource|CurlHandle $hCurl - cURL handle of the processed HTTP request
     * @phpstan-param  CurlHandleId $hCurl
     * @param  string   $data  - chunk of received content data
     *
     * @return int - number of bytes read (the length of the received content chunk)
     */
    public function writeContent($hCurl, string $data): int {
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
    public function getContent(): string {
        return (string)$this->content;
    }
}
