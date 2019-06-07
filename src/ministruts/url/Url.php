<?php
namespace rosasurfer\ministruts\url;

use rosasurfer\core\CObject;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\ErrorHandler;
use rosasurfer\ministruts\Request;

use const rosasurfer\CLI;


/**
 * URL generation helper
 */
class Url extends CObject {


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
     *                       relative to the application's base URI (the main Module). If the URI does not start with a
     *                       slash it is interpreted as relative to the application's current Module.
     */
    public function __construct($uri) {
        Assert::string($uri);
        $this->uri = $uri;

        // TODO: If called from a non-MiniStruts context (i.e. CLI) this method will fail.
        // TODO: If a full URL is passed (http://...) this method will fail.

        if (strpos($uri, '/') === 0) {
            // the resulting URI is relative to the application base URI
            $this->appRelativeUri = substr($uri, 1);
        }
        else {
            // the resulting URI is relative to the application's current module (which may be the main module)
            $request = Request::me();
            $prefix  = $request->getModule()->getPrefix();      // root: "";  submodule: "path/"
            $this->appRelativeUri = $prefix.$uri;
        }
    }


    /**
     * Return a text presentation of this instance&#46;  This is the absolute URI reference to include in a HTML page to link
     * to the resource.
     *
     * @return string
     */
    public function __toString() {
        try {
            $uri = $this->appRelativeUri;
            if ($this->parameters) {
                if (strpos($uri, '?') === false) $uri .= '?';
                else                             $uri .= '&';
                $uri .= http_build_query($this->parameters, null, '&');
            }
            $request = Request::me();
            $uri = $request->getApplicationBaseUri().$uri;

            Assert::string($uri);                               // Ensure the method returns a string as otherwise...
            return $uri;                                        // PHP will trigger a non-catchable fatal error.
        }
        catch (\Throwable $ex) { ErrorHandler::handleToStringException($ex); }
        catch (\Exception $ex) { ErrorHandler::handleToStringException($ex); }
    }
}
