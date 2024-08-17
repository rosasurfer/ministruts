<?php
namespace rosasurfer\net\http;

use rosasurfer\core\CObject;


/**
 * HeaderParser
 */
class HeaderParser extends CObject {


    /** @var array<string, string[]> */
    protected $headers = [];

    /** @var ?string */
    protected $lastName = null;


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
     * @param  string $line - a single header from the header section
     *
     * @return $this
     */
    public function parseLine($line) {
        $line = trim($line, "\r\n");

        $matches = null;
        if (preg_match('/^([\w-]+):\s+(.+)/', $line, $matches)) {           // "header-name: header-value"
            $name = strtolower($matches[1]);
            $value = $matches[2];
            $this->headers[$name][] = $value;
            $this->lastName = $name;
        }
        else {
            // an indented line marks the line-wrapped continuation of the previous header value
            if (preg_match('/^\s+(.+)$/', $line, $matches) && $this->lastName !== null) {
                $lastKey = sizeof($this->headers[$this->lastName]) - 1;
                $this->headers[$this->lastName][$lastKey] .= $matches[1];   // remove line wrap and append to previous value
            }
        }
        return $this;
    }


    /**
     * Return all received headers.
     *
     * @return array<string, string[]> - associative array of all received headers
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
        return isset($this->headers[$name]) ? $this->headers[$name] : null;
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
