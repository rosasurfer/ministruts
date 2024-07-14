<?php
namespace rosasurfer\config;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use const rosasurfer\CLI;


/**
 * An application's main configuration using Java-like property files.
 *
 * An AutoConfig instance is a configuration which typically is used as an application's main configuration. It differs from
 * a regular configuration in automatically loading a standardized set of config files (non-existing files are skipped).
 *
 * These files are in the following order (later config settings override existing earlier ones):
 *
 *  - The framework config file: "config.properties"
 *
 *  - The project config files:  "config.dist.properties"
 *                               "config.cli.properties" (if applicable)
 *
 *  - An explicitely defined user config file, e.g. "config.production.properties" or the default user config file
 *    "config.properties" if no explicite definition is given.
 */
class AutoConfig extends Config {


    /**
     * Constructor
     *
     * Create a new instance from the specified location.
     *
     * - If parameter $location is a directory the application configuration is loaded from that directory.
     *
     * - If parameter $location is a file the file is loaded and treated as the user-defined application configuration.
     *   Other needed configuration files (dist, cli) are looked-up in the same directory (the one containing the file
     *   $location).
     *
     * @param  string $location - configuration file or directory
     */
    public function __construct($location) {
        if (!is_string($location)) throw new IllegalTypeException('Illegal type of parameter $location: '.gettype($location));

        // TODO: look-up and delegate to an existing cached instance
        //       key: get_class($this).'|'.$userConfig.'|cli='.(int)CLI


        // collect the applicable config files
        $configDir = $configFile = null;

        if (is_file($location)) {
            $configFile = realpath($location);
            $configDir  = dirname($configFile);
        }
        else if (is_dir($location)) {
            $configDir = realpath($location);
        }
        else throw new InvalidArgumentException('Location not found: "'.$location.'"');

        // distributable config file
        $files[] = $configDir.'/config.dist.properties';

        // runtime environment
        if (CLI) $files[] = $configDir.'/config.cli.properties';

        // application environment: user or staging configuration
        if ($configFile)                              $files[] = $configFile;                           // explicit
        else if (!empty($_SERVER['APP_ENVIRONMENT'])) $files[] = $configDir.'/config.'.$_SERVER['APP_ENVIRONMENT'].'.properties';
        else                                          $files[] = $configDir.'/config.properties';       // default

        // load all files (do not pass a provided $baseDir but apply it manually in the next step)
        parent::__construct($files);

        // set "app.dir.config" to the directory of the most recently added file
        end($this->files);
        $file = key($this->files);
        $this->set('app.dir.config', dirname($file));


        // create FileDependency and cache the instance
        //$dependency = FileDependency::create(\array_keys($config->files));
        //$dependency->setMinValidity(60 * SECONDS);
        //$cache->set('default', $config, Cache::EXPIRES_NEVER, $dependency);
    }
}
