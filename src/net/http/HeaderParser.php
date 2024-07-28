<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\http;

use rosasurfer\ministruts\core\CObject;


/**
 * HeaderParser
 */
class HeaderParser extends CObject {


    /** @var array */
    protected $headers = [];

    /** @var string */
    protected $currentHeader;


    /**
     * Parse the passed header block.
     *
     * @param  string $data - raw header data
     *
     * @return $this
     */
    public function parseLines($data) {
        foreach (explode("\n", $data) as $line) {
            $this->parseLine($line);
        }
        return $this;
    }


    /**
     * Parse a single header line.
     *
     * @param  string $line - full header line consisting of headername, colon and header value
     *
     * @return $this
     */
    public function parseLine($line) {
        $line = trim($line, "\r\n");

        $matches = null;
        if (preg_match('/^([\w-]+):\s+(.+)/', $line, $matches)) {
            $name = strtolower($matches[1]);
            $value = $matches[2];
            $this->currentHeader = $name;

            if (isset($this->headers[$name])) {
                if (!is_array($this->headers[$name])) {
                    $this->headers[$name] = [$this->headers[$name]];
                }
                $this->headers[$name][] = $value;
            }
            else {
                $this->headers[$name] = $value;
            }
        }
        elseif (preg_match('/^\s+(.+)$/', $line, $matches) && $this->currentHeader !== null) {
            if (is_array($this->headers[$this->currentHeader])) {
                $lastKey = sizeof($this->headers[$this->currentHeader]) - 1;
                $this->headers[$this->currentHeader][$lastKey] .= $matches[1];
            }
            else {
                $this->headers[$this->currentHeader] .= $matches[1];
            }
        }

        return $this;
    }


    /**
     * Return all received headers.
     *
     * @return array - associative array of headers
     */
    public function getHeaders() {
        return $this->headers;
    }


    /**
     * Return the header with the specified name.
     *
     * @param  string $name - header name
     *
     * @return string|string[]|null
     */
    public function getHeader($name) {
        $name = strtolower($name);
        return $this->headers[$name] ?? null;
    }


    /**
     * Whether a header with the specified name was received.
     *
     * @param  string $name - header name
     *
     * @return bool
     */
    public function isHeader($name) {
        return isset($this->headers[strtolower($name)]);
    }
}
