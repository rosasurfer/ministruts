<?php
namespace rosasurfer\ministruts\url;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\ministruts\Request;

use const rosasurfer\CLI;


/**
 * URL generation helper
 */
class Url extends Object {


    /** @var string - URI as passed to the constructor */
    protected $uri;

    /** @var string - application relative URI */
    protected $appRelativeUri;

    /** @var string[] */
    protected $parameters = [];


    /**
     * Constructor
     *
     * Create a new Url instance.
     *
     * @param  string $uri - URI part of the URL to generate. If the URI starts with a slash "/" it is interpreted as
     *                       relative to the application's base URI. If the URI does not start with a slash it is interpreted
     *                       as relative to the current <tt>Module</tt>'s base URI (the module of the current HTTP request).
     */
    public function __construct($uri) {
        if (!is_string($uri)) throw new IllegalTypeException('Illegal type of parameter $uri: '.getType($uri));
        $this->uri = $uri;

        // TODO: If called from a non-MiniStruts context (i.e. CLI) this method will fail.
        // TODO: If a full URL is passed (http://...) this method will fail.

        if (strPos($uri, '/') === 0) {
            // prefix the application base URI
            $this->appRelativeUri = subStr($uri, 1);
        }
        else {
            // prefix the module URI
            $request = Request::me();
            $prefix  = $request->getModule()->getPrefix();
            $prefix  = trim($prefix, '/');
            if (strLen($prefix))
                $prefix = '/'.$prefix;                                   // TODO: What a mess this prefix formatting is!
            $this->appRelativeUri = $prefix.$uri;
        }
    }


    /**
     * Return a text presentation of this instance. This is the absolute URI reference to include in a HTML page to link to
     * the resource.
     *
     * @return string
     */
    public function __toString() {
        $uri = $this->appRelativeUri;
        if ($this->parameters) {
            if (strPos($uri, '?') === false) $uri .= '?';
            else                             $uri .= '&';
            $uri .= http_build_query($this->parameters, null, '&');
        }
        $request = Request::me();
        return $request->getApplicationBaseUri().$uri;
    }
}
