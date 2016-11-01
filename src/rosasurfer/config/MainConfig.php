<?php
namespace rosasurfer\config;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use const rosasurfer\CLI;
use const rosasurfer\MINISTRUTS_ROOT;


/**
 * An application's main configuration using property files.
 *
 * A MainConfig is a stacked configuration where multiple configurations are stacked on each other and finally merged into
 * a single configuration instance. A MainConfig consists of the framework configuration and the project configuration.
 *
 * The framework configuration is created from "config-default.properties", "config-cli.properties" (if true) and
 * "config.properties".
 *
 * The application configuration is created from "config-default.properties", "config-cli.properties" (if true) and an
 * optional user-defined configuration (e.g. "config-production.properties"). If no custom config file is specified the
 * default user configuration "config.properties" is used.
 */
class MainConfig extends Config {


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
      //       key: get_class($this)."|$configDir|$configFile"


      // define framework config files
      $files[]        = MINISTRUTS_ROOT.'/src/config-default.properties';
      CLI && $files[] = MINISTRUTS_ROOT.'/src/config-cli.properties';
      $files[]        = MINISTRUTS_ROOT.'/src/config.properties';

      // add application config files (skip if equal to framework which can happen during CLI testing)
      if ($configDir != realPath(MINISTRUTS_ROOT.'/src')) {
         $files[]        = $configDir.'/config-default.properties';
         CLI && $files[] = $configDir.'/config-cli.properties';
         $files[]        = $configFile ? $configFile : $configDir.'/config.properties';
      }

      // load the files
      parent::__construct($files);

      // create FileDependency and cache the instance
      //$dependency = FileDependency::create(array_keys($config->files));
      //if (!WINDOWS && !CLI && !LOCALHOST)                         // distinction dev/production (sense???)
      //   $dependency->setMinValidity(60 * SECONDS);
      //$cache->set('default', $config, Cache::EXPIRES_NEVER, $dependency);
   }
}
