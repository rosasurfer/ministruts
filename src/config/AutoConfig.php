<?php
namespace rosasurfer\config;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;

use rosasurfer\monitor\FileDependency;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
use const rosasurfer\MINISTRUTS_ROOT;
use const rosasurfer\WINDOWS;


/**
 * An application's main configuration using property files.
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
     *  - If parameter $location is a file the file is loaded as the user-defined application configuration. The remaining
     *    application config files are loaded from the same directory (the directory containing $location).
     *  - If $location is a directory, the application config files are loaded from that directory.
     *  - If $location is empty, the application config files are loaded from the current directory.
     *
     * @param  string $location - custom configuration file or configuration directory
     */
    public function __construct($location = '.') {
        if (!is_string($location)) throw new IllegalTypeException('Illegal type of parameter $location: '.getType($location));

        $configDir = $configFile = null;

        if (is_file($location)) {
            $configFile = realPath($location);
            $configDir  = dirName($configFile);
        }
        else if (is_dir($location)) {
            $configDir = realPath($location);
        }
        else throw new InvalidArgumentException('Location not found: "'.$location.'"');

        // TODO: look-up and delegate to an existing cached instance
        //       key: get_class($this).'|'.$userConfig.'|cli='.(int)CLI

        // add framework configuration (if any at all)
        $files = [];
        $files[] = MINISTRUTS_ROOT.'/config.properties';

        // project config files (skip if equal to framework which can happen during CLI testing)
        if ($configDir != realPath(MINISTRUTS_ROOT.'/src')) {
            // add the regular distributable configuration
                     $files[] = $configDir.'/config.dist.properties';
            if (CLI) $files[] = $configDir.'/config.cli.properties';

            // add user or environment configuration
            if ($configFile)                                     $files[] = $configFile;                        // explicit
            else if (($env=getEnv('APP_ENVIRONMENT')) !== false) $files[] = $configDir.'/config.'.$env.'.properties';
            else                                                 $files[] = $configDir.'/config.properties';    // default
        }

        // load all configuration files
        parent::__construct($files);

        // add project directory layout
        if (is_file($file = $configDir.'/dirs.php'))
            $dirs = include($file);
        $dirs['config'] = $this->lastDirectory;
        if (!isSet($dirs['root'])) throw new InvalidArgumentException('Missing config value "root" in project directory layout file: "'.$file.'"');

        foreach ($dirs as $name => &$dir) {
            $relativePath = WINDOWS ? !preg_match('/^[a-z]:/i', $dir) : ($dir[0]!='/');
            if ($relativePath) {
                if ($name == 'root') throw new InvalidArgumentException('Invalid config value "root" in project directory layout file: "'.$file.'" (not an absolute path)');
                $dir = $dirs['root'].'/'.$dir;
            }
            if (is_dir($dir)) $dir = realPath($dir);
        }; unset($dir);

        $this->set('app.dir', $dirs);


        // create FileDependency and cache the instance
        //$dependency = FileDependency::create(array_keys($config->files));
        //if (!WINDOWS && !CLI && !LOCALHOST)                         // distinction dev/production (sense???)
        //   $dependency->setMinValidity(60 * SECONDS);
        //$cache->set('default', $config, Cache::EXPIRES_NEVER, $dependency);
    }


    /**
     * {@inheritDoc}
     */
    public function info() {
        return __METHOD__.'()  not yet implemented';
    }
}
