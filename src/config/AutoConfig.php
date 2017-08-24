<?php
namespace rosasurfer\config;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\monitor\FileDependency;

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
     * If parameter $location is a file the file is loaded as the user-defined application configuration. The remaining
     * application config files are loaded from the same directory (the directory containing $configLocation).
     *
     * If $location is a directory the application config files are loaded from that directory.
     *
     * @param  string $location - configuration file or directory
     */
    public function __construct($location) {
        if (!is_string($location)) throw new IllegalTypeException('Illegal type of parameter $location: '.getType($location));

        // TODO: look-up and delegate to an existing cached instance
        //       key: get_class($this).'|'.$userConfig.'|cli='.(int)CLI


        // collect the applicable config files
        $configDir = $configFile = null;

        if (is_file($location)) {
            $configFile = realPath($location);
            $configDir  = dirName($configFile);
        }
        else if (is_dir($location)) {
            $configDir = realPath($location);
        }
        else throw new InvalidArgumentException('Location not found: "'.$location.'"');

        // distributable config file
        $files[] = $configDir.'/config.dist.properties';

        // runtime environment
        if (CLI) $files[] = $configDir.'/config.cli.properties';

        // application environment: user or staging configuration
        if ($configFile)                           $files[] = $configFile;                              // explicite
        else if (($env=getEnv('APP_ENVIRONMENT'))) $files[] = $configDir.'/config.'.$env.'.properties';
        else                                       $files[] = $configDir.'/config.properties';          // default

        // load all files (do not pass a provided $baseDir but apply it manually in the next step)
        parent::__construct($files);

        // set "app.dir.config" to the directory of the most recently added file
        end($this->files);
        list($file, $isFile) = each($this->files);
        $this->set('app.dir.config', dirName($file));


        // create FileDependency and cache the instance
        //$dependency = FileDependency::create(array_keys($config->files));
        //$dependency->setMinValidity(60 * SECONDS);
        //$cache->set('default', $config, Cache::EXPIRES_NEVER, $dependency);
    }
}
