<?php
namespace rosasurfer\config\auto;

use rosasurfer\Application;
use rosasurfer\config\Config;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\monitor\FileDependency;

use const rosasurfer\CLI;


/**
 * An {@link Application}'s default configuration using Java-like property files.
 *
 * A variant of the standard {@link Config}. It automatically loads and monitores a standard set of configuration files from
 * the directory "{app.dir.config}". These files are in the following order (later config settings with the same key override
 * existing ones):
 *
 *  - The framework config file: "config.properties"
 *
 *  - The project config files:  "config.dist.properties"
 *                               "config.cli.properties" (if applicable)
 *
 *  - An explicitely defined user config file, e.g. "config.production.properties" or the default user config file
 *    "config.properties" if no explicite definition is given.
 */
class DefaultConfig extends Config {


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
        list($file, $isFile) = each($this->files);
        $this->set('app.dir.config', dirname($file));


        // create FileDependency and cache the instance
        //$dependency = FileDependency::create(\array_keys($config->files));
        //$dependency->setMinValidity(60 * SECONDS);
        //$cache->set('default', $config, Cache::EXPIRES_NEVER, $dependency);
    }
}
