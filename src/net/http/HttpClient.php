<?php
namespace rosasurfer\net\http;

use rosasurfer\core\Object;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\IOException;


/**
 * Basisklasse fuer konkrete HttpClients.
 */
abstract class HttpClient extends Object {


    // Default-Einstellungen

    /** @var int */
    protected $timeout = 30;

    /** @var bool */
    protected $followRedirects = true;

    /** @var int */
    protected $maxRedirects = 5;

    /** @var string */
    protected $userAgent = 'Mozilla/5.0';


    /**
     * Setzt den Verbindungs-Timeout.
     *
     * @param  int $timeout - Timeout in Sekunden
     *
     * @return $this
     */
    public function setTimeout($timeout) {
        if (!is_int($timeout)) throw new IllegalTypeException('Illegal type of parameter $timeout: '.getType($timeout));
        if ($timeout < 1)      throw new InvalidArgumentException('Invalid argument $timeout: '.$timeout);

        $this->timeout = $timeout;
        return $this;
    }


    /**
     * Gibt den eingestellten Verbindungs-Timeout zurueck.
     *
     * @return int - Timeout in Sekunden
     */
    public function getTimeout() {
        return $this->timeout;
    }


    /**
     * Ob Redirect-Headern gefolgt werden soll oder nicht.
     *
     * @param  bool $follow
     *
     * @return $this
     */
    public function setFollowRedirects($follow) {
        if (!is_bool($follow)) throw new IllegalTypeException('Illegal type of parameter $follow: '.getType($follow));

        $this->followRedirects = $follow;
        return $this;
    }


    /**
     * Gibt die aktuelle Redirect-Einstellung zurueck.
     *
     * @return bool
     */
    public function isFollowRedirects() {
        return (bool) $this->followRedirects;
    }


    /**
     * Setzt die maximale Anzahl der Redirects, denen gefolgt werden soll.
     *
     * @param  int $maxRedirects
     *
     * @return $this
     */
    public function setMaxRedirects($maxRedirects) {
        if (!is_int($maxRedirects)) throw new IllegalTypeException('Illegal type of parameter $maxRedirects: '.getType($maxRedirects));

        $this->maxRedirects = $maxRedirects;
        return $this;
    }


    /**
     * Gibt die Anzahl der Redirects zurueck, denen gefolgt wird.
     *
     * @return int
     */
    public function getMaxRedirects() {
        return $this->maxRedirects;
    }


    /**
     * Fuehrt den uebergebenen Request aus und gibt die empfangene Antwort zurueck.
     *
     * @param  HttpRequest $request
     *
     * @return HttpResponse
     *
     * @throws IOException wenn ein Fehler auftritt
     */
    abstract public function send(HttpRequest $request);
}
