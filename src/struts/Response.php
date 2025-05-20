<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts;

use rosasurfer\ministruts\core\Singleton;
use rosasurfer\ministruts\core\di\proxy\Request as RequestProxy;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\net\http\HttpResponse;

use function rosasurfer\ministruts\strLeft;
use function rosasurfer\ministruts\strLeftTo;
use function rosasurfer\ministruts\strRightFrom;

use const rosasurfer\ministruts\CLI;

/**
 * Response
 *
 * An object representing the HTTP response to the current HTTP {@link Request}.
 * Provides helper methods and an additional variables context with the life-time of the request.
 */
class Response extends Singleton {

    /** @var int - HTTP status code */
    protected int $status = 0;

    /** @var mixed[] - additional variables context */
    protected array $attributes = [];


    /**
     * Return the {@link Singleton} instance of this class.
     *
     * @return static
     */
    public static function me(): self {
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
    public function setStatus(int $status): self {
        if ($status < 1) throw new InvalidValueException('Invalid parameter $status: '.$status);
        $this->status = $status;
        return $this;
    }


    /**
     * Return the HTTP response status.
     *
     * @return int
     */
    public function getStatus(): int {
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
    public function setAttribute(string $name, $value): self {
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
    public function getAttribute(string $name) {
        if (\key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        return null;
    }


    /**
     * Send a "Location" header (redirect) pointing to the specified URI. Afterwards the script is terminated.
     *
     * @param  string $uri  - absolute or relative URI
     * @param  int    $type - redirect type (SC_MOVED_TEMPORARILY | SC_MOVED_PERMANENTLY)
     *
     * @return never
     */
    public function redirect(string $uri, int $type = HttpResponse::SC_MOVED_TEMPORARILY): void {
        $currentUrl = RequestProxy::getUrl();

        $url = self::relativeToAbsoluteUrl($uri, $currentUrl);  // HTTP/1.1 requires an absolute 'Location' value
        header('Location: '.$url, true, $type);                 // set the header
        exit(0);                                                // terminate the script
    }


    /**
     * Transform a relative path to an absolute URL.
     *
     * @param  string $rel  - relative value (path, URI or URL)
     * @param  string $base - base URL
     *
     * @return string - absolute URL
     */
    public static function relativeToAbsoluteUrl(string $rel, string $base): string {
        // TODO: rewrite because parse_url() fails at query parameters with colons, e.g. "/beanstalk-console?server=vm-centos:11300"
        // TODO: the whole logic is nonsense
        if (strlen($relFragment = strRightFrom($rel, '#'))) {
            $rel = strLeft($rel, -strlen($relFragment)-1);
        }
        if (strlen($relQuery = strRightFrom($rel, '?'))) {
            $rel = strLeft($rel, -strlen($relQuery)-1);
        }

        if (($relParts =parse_url($rel )) === false) throw new InvalidValueException("Invalid parameter \$rel: $rel");
        if (($baseParts=parse_url($base)) === false) throw new InvalidValueException("Invalid parameter \$base: $base");

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
            $scheme = $baseParts['scheme'] ?? '';

            // if $rel contains a host only expand the scheme
            if (isset($relParts['host'])) return $scheme.':'.$rel;

            // a query only w/o anchor
            if ($rel[0] == '?') {
                $query    = '?'.($relParts['query'] ?? '');
                $fragment = '#'.($relParts['fragment'] ?? $baseParts['fragment'] ?? '');
                if ($fragment == '#') $fragment = '';
                return strLeftTo($base, '?').$query.$fragment;
            }

            // an anchor only
            if ($rel[0] == '#') return strLeftTo($base, '#').$rel;

            // $rel is an absolute or relative path with its own parameters
            if ($rel[0] == '/') $path = '';
            else                $path = strLeftTo($baseParts['path'] ?? '', '/', -1, true, '/');

            $host  = $baseParts['host'] ?? '';
            $port  = isset($baseParts['port']) ? ":$baseParts[port]" : '';
            $user  = $baseParts['user'] ?? '';
            $pass  = isset($baseParts['pass']) ? ":$baseParts[pass]" : '';
            $at    = strlen($user) ? '@' : '';
            $path .= $rel;                              // includes $rel query and/or fragment

            // resulting absolute URL
            return $scheme.'://'.$user.$pass.$at.$host.$port.$path;
        }
        catch (IRosasurferException $ex) {
            throw $ex->appendMessage('Illegal parameter $base: "'.$base.'"');
        }
    }
}
