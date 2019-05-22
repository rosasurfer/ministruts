<?php
namespace rosasurfer\net\http;

use rosasurfer\core\CObject;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\IOException;


/**
 * Basisklasse fuer konkrete HttpClients.
 */
abstract class HttpClient extends CObject {


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
        Assert::int($timeout);
        if ($timeout < 1) throw new InvalidArgumentException('Invalid argument $timeout: '.$timeout);

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
        Assert::bool($follow);
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
        Assert::int($maxRedirects);
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
