<?php
namespace rosasurfer\struts\url;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\struts\Request;

use const rosasurfer\struts\MODULE_KEY;


/**
 * URL generation helper
 */
class Url extends Object {


   /** @var string - URI as passed */
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
    * @param  string $uri - URI part of the URL to generate. If the URI starts with a slash "/" it is interpreted as relative
    *                       to the application's base URI. If the URI doesn't start with a slash "/" it is interpreted as
    *                       relative to the current application <tt>Module</tt>'s base URI (the module the current HTTP
    *                       request belongs to).
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
         $prefix  = $request->getAttribute(MODULE_KEY)->getPrefix();
         $prefix  = trim($prefix, '/');
         if (strLen($prefix))
            $prefix = '/'.$prefix;                                   // TODO: What a mess this prefix formatting is!
         $this->appRelativeUri = $prefix.$uri;
      }
   }


   /**
    * Return a text presentation of this Url. This is the absolute URI reference to include in a HTML page to link to the
    * resource.
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
