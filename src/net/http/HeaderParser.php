<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\http;

use rosasurfer\ministruts\core\CObject;

use function rosasurfer\ministruts\preg_match;

/**
 * HeaderParser
 */
class HeaderParser extends CObject {

    /** @var array<string, string[]> */
    protected array $headers = [];

    /** @var ?string */
    protected ?string $lastName = null;


    /**
     * Parse the passed header section.
     *
     * @param  string $data - raw header data
     *
     * @return $this
     */
    public function parseHeaderSection(string $data): self {
        foreach (explode("\n", $data) as $line) {
            $this->parseHeaderLine($line);
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
    public function parseHeaderLine(string $line): self {
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
    public function getHeaders(): array {
        return $this->headers;
    }


    /**
     * Return the received header values with the specified name.
     *
     * @param  string $name - header name
     *
     * @return string[] - array of received header values, or an empty array if no such header was received
     */
    public function getHeaderValues(string $name): array {
        $name = strtolower($name);
        return $this->headers[$name] ?? [];
    }


    /**
     * Whether a header with the specified name was received.
     *
     * @param  string $name - header name
     *
     * @return bool
     */
    public function isHeader(string $name): bool {
        return isset($this->headers[strtolower($name)]);
    }
}
