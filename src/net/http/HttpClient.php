<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\http;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\IOException;


/**
 * Base class for concrete HTTP clients.
 */
abstract class HttpClient extends CObject {


    // default settings

    /** @var int */
    protected $timeout = 30;

    /** @var bool */
    protected $followRedirects = true;

    /** @var int */
    protected $maxRedirects = 5;

    /** @var string */
    protected $userAgent = 'Mozilla/5.0';


    /**
     * Get the current connection timeout.
     *
     * @return int - timeout in seconds
     */
    public function getTimeout() {
        return $this->timeout;
    }


    /**
     * Set the connection timeout.
     *
     * @param  int $timeout - timeout in seconds
     *
     * @return $this
     */
    public function setTimeout($timeout) {
        Assert::int($timeout);
        if ($timeout < 1) throw new InvalidValueException('Invalid parameter $timeout: '.$timeout);

        $this->timeout = $timeout;
        return $this;
    }


    /**
     * Whether the instance currently follows received redirect headers.
     *
     * @return bool
     */
    public function isFollowRedirects() {
        return $this->followRedirects;
    }


    /**
     * Whether to follow received redirect headers.
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
     * Return the number of redirects the instance will follow.
     *
     * @return int
     */
    public function getMaxRedirects() {
        return $this->maxRedirects;
    }


    /**
     * Set the number of redirects the instance will follow.
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
     * Execute the passed {@link HttpRequest} and return the received {@link HttpResponse}.
     *
     * @param  HttpRequest $request
     *
     * @return HttpResponse
     *
     * @throws IOException in case of errors
     */
    abstract public function send(HttpRequest $request);
}
