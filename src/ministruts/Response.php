<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Singleton;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\InvalidArgumentException;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\di\proxy\Request as RequestProxy;
use rosasurfer\net\http\HttpResponse;

use function rosasurfer\ini_get_bool;
use function rosasurfer\strContains;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;


/**
 * Response
 *
 * An object representing the HTTP response to the current HTTP {@link Request}.
 * Provides helper methods and an additional variables context with the life-time of the request.
 */
class Response extends Singleton {


    /** @var int - HTTP status code */
    protected $status = 0;

    /** @var array - additional variables context */
    protected $attributes = [];


    /**
     * Return the {@link \rosasurfer\core\Singleton} instance of this class.
     *
     * @return static
     *
     * @throws RuntimeException if not called from the web interface
     */
    public static function me() {
        if (CLI) throw new RuntimeException('Cannot create a '.static::class.' instance in a non-web context.');

        /** @var static $instance */
        $instance = self::getInstance(static::class);
        return $instance;
    }


    /**
     * Set the response status code.
     *
     * @param  int $status - HTTP response status
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
     * Return the HTTP response status.
     *
     * @return int
     */
    public function getStatus() {
        return $this->status;
    }


    /**
     * Store a value in the local variables context.
     *
     * @param  string $name  - name under which the value is stored
     * @param  mixed  $value - value to store
     *
     * @return $this
     */
    public function setAttribute($name, $value) {
        $this->attributes[$name] = $value;
        return $this;
    }


    /**
     * Return a value stored in the local variables context under the specified name.
     *
     * @param  string $name - attribute name
     *
     * @return mixed - attribute value or NULL if no value is stored under the specified name
     */
    public function getAttribute($name) {
        if (\key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        return null;
    }


    /**
     * Send a "Location" header (redirect) pointing to the specified URI.  Afterwards the script is terminated.
     *
     * @param  string $uri  - absolute or relative URI
     * @param  int    $type - redirect type: 301=SC_MOVED_PERMANENTLY or 302=SC_MOVED_TEMPORARILY
     *
     * @return never
     */
    public function redirect($uri, $type=HttpResponse::SC_MOVED_TEMPORARILY) {
        $currentUrl = RequestProxy::getUrl();

        // HTTP/1.1 requires an absolute 'Location' value
        $url = self::relativeToAbsoluteUrl($uri, $currentUrl);

        // append session id if a session is active and URL rewriting is not disabled (strongly discouraged)
        if (defined('SID') && strlen(SID)) {                        // @phpstan-ignore-line     empty string if the session id was submitted in a cookie
            if (!ini_get_bool('session.use_only_cookies')) {        //                          TODO: check if session_destroy() resets SID
                $cookie       = session_get_cookie_params();
                $cookieDomain = strtolower(empty($cookie['domain']) ? RequestProxy::getHostname() : $cookie['domain']);
                $cookiePath   =                  $cookie['path'  ];
                $cookieSecure =                  $cookie['secure'];

                $target       = parse_url($url);
                $targetDomain = strtolower($target['host'  ]);
                $targetPath   =      empty($target['path'  ]) ? '/' : $target['path'];
                $targetSecure = strtolower($target['scheme']) == 'https';

                $subdomains = false;
                if ($cookieDomain[0] == '.') {                      // TODO: check if the leading dot is still required
                    $subdomains = true;                             //       (@see http://bayou.io/draft/cookie.domain.html)
                    $cookieDomain = substr($cookieDomain, 1);
                }

                if (preg_match('/\b'.$cookieDomain.'$/', $targetDomain) && ($cookieDomain==$targetDomain || $subdomains)) { // domain match
                    if (strStartsWith($targetPath, $cookiePath)) {                                                          // path match
                        if (!$cookieSecure || $targetSecure) {                                                              // secure match
                            if (isset($target['fragment']))  $url  = strLeft($url, strlen($hash = '#'.$target['fragment']));
                            else if (strEndsWith($url, '#')) $url  = strLeft($url, strlen($hash = '#'));
                            else                             $hash = '';

                            $separator = !strContains($url, '?') ? '?' : '&';
                            $url .= $separator.SID.$hash;
                        }
                    }
                }
            }
        }

        header('Location: '.$url, true, $type);
        exit(0);
    }


    /**
     * Transform a relative path to an absolute URL.
     *
     * @param  string $rel  - relative value (path, URI or URL)
     * @param  string $base - base URL
     *
     * @return string - absolute URL
     *
     *
     * @todo   rewrite because parse_url() fails at query parameters with colons, e.g. "/beanstalk-console?server=vm-centos:11300"
     */
    public static function relativeToAbsoluteUrl($rel, $base) {
        $relFragment = strRightFrom($rel, '#');
        strlen($relFragment) && $rel = strLeft($rel, -strlen($relFragment)-1);

        $relQuery = strRightFrom($rel, '?');
        strlen($relQuery) && $rel = strLeft($rel, -strlen($relQuery)-1);

        if (($relParts =parse_url($rel )) === false) throw new InvalidArgumentException('Invalid argument $rel: '.$rel);
        if (($baseParts=parse_url($base)) === false) throw new InvalidArgumentException('Invalid argument $base: '.$base);

        if (strlen($relQuery)) {
            $relParts['query'] = $relQuery;
            $rel .= '?'.$relQuery;
        }
        if (strlen($relFragment)) {
            $relParts['fragment'] = $relFragment;
            $rel .= '#'.$relFragment;
        }

        try {
            // if $rel is empty return $base
            if (!strlen($rel)) return $base;

            // if already an absolute URL return $rel
            if (isset($relParts['scheme'])) return $rel;
            $scheme = $baseParts['scheme'];

            // if $rel contains a host only expand the scheme
            if (isset($relParts['host'])) return $scheme.':'.$rel;

            // a query only w/o anchor
            if ($rel[0] == '?') {
                $query = '?'.$relParts['query'];
                if      (isset($relParts ['fragment'])) $fragment = '#'.$relParts['fragment'];
                else if (isset($baseParts['fragment'])) $fragment = '#'.$baseParts['fragment'];
                else                                    $fragment = '';
                return strLeftTo($base, '?').$query.$fragment;
            }

            // an anchor only
            if ($rel[0] == '#') return strLeftTo($base, '#').$rel;

            // $rel is an absolute or relative path with its own parameters
            if ($rel[0] == '/') $path = '';
            else                $path = strLeftTo($baseParts['path'], '/', -1, true, '/');

            $host  = $baseParts['host'];
            $port  = isset($baseParts['port']) ? ':'.$baseParts['port'] : '';
            $user  = isset($baseParts['user']) ?     $baseParts['user'] : '';
            $pass  = isset($baseParts['pass']) ? ':'.$baseParts['pass'] : '';
            $at    = strlen($user) ? '@' : '';
            $path .= $rel;                              // includes $rel query and/or fragment

            // resulting absolute URL
            return $scheme.'://'.$user.$pass.$at.$host.$port.$path;
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('Illegal parameter $base: "'.$base.'"');
        }
    }
}
