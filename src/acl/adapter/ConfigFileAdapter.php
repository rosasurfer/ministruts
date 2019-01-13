<?php
namespace rosasurfer\acl\adapter;

use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\UnimplementedFeatureException;


/**
 * ACL adapter reading ACLs from a configuration file.
 */
class ConfigFileAdapter extends Object implements AdapterInterface {


    /**
     * Constructor
     *
     * @param  string $file [optional] - name of the config file to read ACL settings from
     *                                   (default: the application configuration)
     */
    public function __construct($file = null) {
        if (func_num_args()) {
            if (!is_string($file)) throw new IllegalTypeException('Illegal type of parameter $file: '.getType($file));
            throw new UnimplementedFeatureException('Support for custom config files not yet implemented');
        }
        $config = $this->di()['config']->get('acl.config', null);
    }
}
