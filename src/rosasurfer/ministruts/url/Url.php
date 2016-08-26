<?php
namespace rosasurfer\ministruts\url;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;


/**
 * URL generation helper
 */
class Url extends Object {


   /** @var string */
   protected $argSeparator;

   /** @var string */
   protected $uri;

   /** @var string[] */
   protected $parameters = [];


   /**
    * Constructor
    *
    * Create a new Url instance.
    *
    * @param  string $uri - URI part of the URL to generate
    */
   public function __construct($uri) {
      if (!is_string($uri)) throw new IllegalTypeException('Illegal type of parameter $uri: '.getType($uri));

      $this->uri          = $uri;
      $this->argSeparator = ini_get('arg_separator.output');
   }


   /**
    * Return a text presentation of this Url.
    *
    * @return string
    */
   public function __toString() {
      $url = $this->uri;
      if ($this->parameters)
         $url .= '?'.http_build_query($this->parameters);
      return $url;
   }
}
