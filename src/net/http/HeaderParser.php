<?php
namespace rosasurfer\net\http;

use rosasurfer\core\CObject;


/**
 * HeaderParser
 */
class HeaderParser extends CObject {


    /** @var array */
    private $headers = [];

    /** @var string */
    private $currentHeader;


    /**
     * Parst einen uebergebenen Headerblock.
     *
     * @param  string $data - rohe Headerdaten
     *
     * @return $this
     */
    public function parseLines($data) {
        $lines = explode("\n", $data);

        foreach ($lines as $line)
            $this->parseLine($line);

        return $this;
    }


    /**
     * Parst eine einzelne Headerzeile.
     *
     * @param  string $line - Headerzeile
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
     * Gibt alle empfangenen Header zurueck.
     *
     * @return array - assoziatives Array mit Headern
     */
    public function getHeaders() {
        return $this->headers;
    }


    /**
     * Ob ein Header mit dem angegebenen Namen existiert.
     *
     * @param  string $name - Name des Headers
     *
     * @return bool
     */
    public function isHeader($name) {
        return isset($this->headers[strtolower($name)]);
    }


    /**
     * Gibt den Header mit dem angegebenen Namen zurueck.
     *
     * @param  string $name - Name des Headers
     *
     * @return string|string[]
     */
    public function getHeader($name) {
        return $this->headers[strtolower($name)];
    }
}
