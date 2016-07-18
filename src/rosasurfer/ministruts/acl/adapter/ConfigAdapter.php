<?php
namespace rosasurfer\ministruts\acl\adapter;

use rosasurfer\ministruts\core\Object;
use rosasurfer\ministruts\exception\IllegalTypeException;
use rosasurfer\ministruts\exception\UnimplementedFeatureException;


/**
 * ACL adapter reading ACLs from a configuration file.
 */
class ConfigAdapter extends Object implements AdapterInterface {


   /**
    * Constructor
    *
    * @param  string $file - name of the config file to read ACL settings from
    *                        (default: the application configuration)
    */
   public function __construct($file=null) {
      if (func_num_args()) {
         if (!is_string($file)) throw new IllegalTypeException('Illegal type of parameter $file: '.getType($file));
         throw new UnimplementedFeatureException('Support for custom config files not yet implemented');
      }

      $acl = \Config::me()->get('acl', null);
      echoPre($acl);
   }
}
