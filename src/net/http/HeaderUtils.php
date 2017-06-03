<?php
namespace rosasurfer\net\http;

use rosasurfer\core\StaticClass;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\ministruts\Request;

use function rosasurfer\strContains;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeft;
use function rosasurfer\strLeftTo;
use function rosasurfer\strStartsWith;


/**
 * Header utilities
 */
class HeaderUtils extends StaticClass {


    /**
     * Send a "Location" header (redirect) pointing to the specified URI. Afterwards the script is terminated.
     *
     * @param  string $uri - absolute or relative URI
     */
    public static function redirect($uri) {
        $request    = Request::me();
        $currentUrl = $request->getUrl();

        // HTTP/1.1 requires an absolute 'Location' value
        $url = self::relativeToAbsoluteUrl($uri, $currentUrl);

        // append session id if a session is active and URL rewriting is enabled (strongly discouraged)
        if (defined('SID') && strLen(SID)) {                // TODO: check if session_destroy() resets SID
            $cookie       = session_get_cookie_params();
            $cookieDomain = strToLower(empty($cookie['domain']) ? $request->getHostname() : $cookie['domain']);
            $cookiePath   =            empty($cookie['path'  ]) ? '/'                     : $cookie['path'  ];
            $cookieSecure =                  $cookie['secure'];

            $target       = parse_url($url);
            $targetDomain = strToLower($target['host'  ]);
            $targetPath   =      empty($target['path'  ]) ? '/' : $target['path'];
            $targetSecure = strToLower($target['scheme']) == 'https';

            $subdomains = false;
            if ($cookieDomain[0] == '.') {                  // TODO: check if the leading dot is still required
                $subdomains = true;                         //       (@see http://bayou.io/draft/cookie.domain.html)
                $cookieDomain = subStr($cookieDomain, 1);
            }

            if ($domainMatch = preg_match('/\b'.$cookieDomain.'$/', $targetDomain) && ($cookieDomain==$targetDomain || $subdomains)) {
                if ($pathMatch = strStartsWith($targetPath, $cookiePath)) {
                    if ($secureMatch = !$cookieSecure || $targetSecure) {
                        if (isSet($target['fragment']))  $url  = strLeft($url, strLen($hash = '#'.$target['fragment']));
                        else if (strEndsWith($url, '#')) $url  = strLeft($url, strLen($hash = '#'));
                        else                             $hash = '';

                        $separator = !strContains($url, '?') ? '?' : '&';
                        $url .= $separator.SID.$hash;
                    }
                }
            }
        }

        // set the header
        header('Location: '.$url);

        // terminate the script
        exit(0);
    }


    /**
     * Transform a relative path to an absolute URL.
     *
     * @param  string $rel  - relative value (path, URI or URL)
     * @param  string $base - base URL
     *
     * @return string - absolute URL
     */
    public static function relativeToAbsoluteUrl($rel, $base) {
        if (!$relParts  = parse_url($rel))  throw new InvalidArgumentException('Invalid argument $rel: '.$rel);
        if (!$baseParts = parse_url($base)) throw new InvalidArgumentException('Invalid argument $base: '.$base);

        try {
            // if $rel is empty return $base
            if (!strLen($rel)) return $base;

            // if already an absolute URL return $rel
            if (isSet($relParts['scheme'])) return $rel;
            $scheme = $baseParts['scheme'];

            // if $rel contains a host only expand the scheme
            if (isSet($relParts['host'])) return $scheme.':'.$rel;

            // a query only w/o anchor
            if ($rel[0] == '?') {
                $query = '?'.$relParts['query'];
                if      (isSet($relParts ['fragment'])) $fragment = '#'.$relParts['fragment'];
                else if (isSet($baseParts['fragment'])) $fragment = '#'.$baseParts['fragment'];
                else                                    $fragment = '';
                return strLeftTo($base, '?').$query.$fragment;
            }

            // an anchor only
            if ($rel[0] == '#') return strLeftTo($base, '#').$rel;

            // $rel is an absolute or relative path with its own parameters
            if ($rel[0] == '/') $path = '';
            else                $path = strLeftTo($baseParts['path'], '/', -1, true, '/');

            $host  = $baseParts['host'];
            $port  = isSet($baseParts['port']) ? ':'.$baseParts['port'] : '';
            $user  = isSet($baseParts['user']) ?     $baseParts['user'] : '';
            $pass  = isSet($baseParts['pass']) ? ':'.$baseParts['pass'] : '';
            $at    = strLen($user) ? '@' : '';
            $path .= $rel;                              // includes $rel query and/or fragment

            // resulting absolute URL
            return $scheme.'://'.$user.$pass.$at.$host.$port.$path;
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('Illegal parameter $base: "'.$base.'"');
        }
    }
}
