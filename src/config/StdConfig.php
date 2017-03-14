<?php
namespace rosasurfer\config;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
use const rosasurfer\MINISTRUTS_ROOT;
use const rosasurfer\WINDOWS;


/**
 * An application's main configuration using property files.
 *
 * An AutoConfig is a configuration which typically is used as an application's main configuration. It differs from a regular
 * configuration in automatically loading and merging a standardized set of config files (non-existing files are skipped).
 *
 * These files are in the following order (later config settings override existing earlier settings):
 *
 *  - The framework config files: "config.dist.properties"
 *                                "config.cli.properties" (if applicable)
 *                                "config.properties"
 *
 *  - The project config files:   "config.dist.properties"
 *                                "config.cli.properties" (if applicable)
 *
 *  - An explicitely defined user config file, e.g. "config.production.properties" or the default user config file
 *    "config.properties" if no explicite user config file is defined.
 */
class StdConfig extends Config {


    /**
     * Constructor
     *
     * Create a new instance from the specified location.
     *
     *  - If parameter $location is a file the file is loaded as the user-defined application configuration. The remaining
     *    application config files are loaded from the same directory (the directory containing $location).
     *  - If $location is a directory, the application config files are loaded from that directory.
     *  - If $location is empty, the application config files are loaded from the current directory.
     *
     * @param  string $location - custom configuration file or configuration directory
     */
    public function __construct($location = '.') {
        if (!is_string($location)) throw new IllegalTypeException('Illegal type of parameter $location: '.getType($location));

        $configDir = $configFile = $files = null;

        if (is_file($location)) {
            $configFile = realPath($location);
            $configDir  = dirName($configFile);
        }
        else if (is_dir($location)) {
            $configDir = realPath($location);
        }
        else throw new InvalidArgumentException('Location not found: "'.$location.'"');


        // TODO: look-up and delegate to an existing cached instance
        //       key: get_class($this).'|'.$configDir.'|'.$configFile


        // define framework config files
               $files[] = MINISTRUTS_ROOT.'/src/config.dist.properties';
        CLI && $files[] = MINISTRUTS_ROOT.'/src/config.cli.properties';
               $files[] = MINISTRUTS_ROOT.'/src/config.properties';

        // add application config files (skip if equal to framework which can happen during CLI testing)
        if ($configDir != realPath(MINISTRUTS_ROOT.'/src')) {
                   $files[] =                $configDir.'/config.dist.properties';
            CLI && $files[] =                $configDir.'/config.cli.properties';
                   $files[] = $configFile ?: $configDir.'/config.properties';
        }

        // load the files
        parent::__construct($files);

        // create FileDependency and cache the instance
        //$dependency = FileDependency::create(array_keys($config->files));
        //if (!WINDOWS && !CLI && !LOCALHOST)                         // distinction dev/production (sense???)
        //   $dependency->setMinValidity(60 * SECONDS);
        //$cache->set('default', $config, Cache::EXPIRES_NEVER, $dependency);
    }


    /**
     * {@inheritdoc}
     */
    public function info() {
        return __METHOD__.'()  not yet implemented';
    }
}
