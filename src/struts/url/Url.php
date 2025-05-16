<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\struts\url;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\di\proxy\Request;
use rosasurfer\ministruts\core\exception\RuntimeException;

use const rosasurfer\ministruts\CLI;


/**
 * URL generation helper
 */
class Url extends CObject {


    /** @var string - URI as passed to the constructor */
    protected string $uri;

    /** @var string - application relative URI */
    protected string $appRelativeUri;

    /** @var string[] */
    protected array $parameters = [];


    /**
     * Constructor
     *
     * @param  string $uri - URI part of the URL to generate. If the URI starts with a slash "/" it is interpreted as
     *                       relative to the application's base URI (the main module). If the URI does not start with a
     *                       slash it is interpreted as relative to the application's current module.
     */
    public function __construct(string $uri) {
        $this->uri = $uri;

        // TODO: If called from a non-MiniStruts context (i.e. CLI) this method will fail.
        // TODO: If a full URL is passed (http://...) this method will fail.

        if (strpos($uri, '/') === 0) {
            // the resulting URI is relative to the application base URI
            $this->appRelativeUri = substr($uri, 1);
        }
        else {
            // the resulting URI is relative to the application's current module (which may be the main module)
            $module = Request::getModule();
            if (!$module) throw new RuntimeException("Request module not found");

            $prefix = $module->getPrefix();             // main module: "";  submodule: "path/"
            $this->appRelativeUri = $prefix.$uri;
        }
    }


    /**
     * Return a text presentation of this instance.  This is the absolute URI reference to include
     * in a HTML page to link to the resource.
     *
     * @return string
     */
    public function __toString(): string {
        $uri = $this->appRelativeUri;
        if ($this->parameters) {
            if (strpos($uri, '?') === false) $uri .= '?';
            else                             $uri .= '&';
            $uri .= http_build_query($this->parameters, '', '&');
        }
        $uri = Request::getApplicationBaseUri().$uri;
        return $uri;
    }
}
