<?php
namespace rosasurfer\config;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\monitor\FileDependency;

use function rosasurfer\isRelativePath;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
use const rosasurfer\MINISTRUTS_ROOT;
use const rosasurfer\WINDOWS;


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
     *  - If parameter $configLocation is a file the file is loaded as the user-defined application configuration. The
     *    remaining application config files are loaded from the same directory (the directory containing $configLocation).
     *  - If $configLocation is a directory, the application config files are loaded from that directory.
     *
     * @param  string $appRootDir     - application root directory
     * @param  string $configLocation - configuration file or directory
     */
    public function __construct($appRootDir, $configLocation) {
        if (!is_string($appRootDir))     throw new IllegalTypeException('Illegal type of parameter $appRootDir: '.getType($appRootDir));
        if (!is_string($configLocation)) throw new IllegalTypeException('Illegal type of parameter $configLocation: '.getType($configLocation));

        // (1) load all relevant config files
        $configDir = $configFile = null;

        if (is_file($configLocation)) {
            $configFile = realPath($configLocation);
            $configDir  = dirName($configFile);
        }
        else if (is_dir($configLocation)) {
            $configDir = realPath($configLocation);
        }
        else throw new InvalidArgumentException('Location not found: "'.$configLocation.'"');

        // TODO: look-up and delegate to an existing cached instance
        //       key: get_class($this).'|'.$userConfig.'|cli='.(int)CLI

        // start with framework configuration file (if any)
        $files = [];
        $files[] = MINISTRUTS_ROOT.'/config.properties';

        // add distributable configuration file
                 $files[] = $configDir.'/config.dist.properties';
        if (CLI) $files[] = $configDir.'/config.cli.properties';

        // add user or environment configuration files
        if ($configFile)                           $files[] = $configFile;                              // explicit file
        else if (($env=getEnv('APP_ENVIRONMENT'))) $files[] = $configDir.'/config.'.$env.'.properties';
        else                                       $files[] = $configDir.'/config.properties';          // default file

        // load all files and set "app.dir.config"
        parent::__construct($files);
        $this->set('app.dir.config', $this->lastDirectory);


        // (2) check "app.dir.root" and expand relative "app.dir.*" values
        $dirs = $this->get('app.dir', []);
        if (!isSet($dirs['root']))
            $dirs['root'] = $appRootDir;

        foreach ($dirs as $name => &$dir) {
            if (isRelativePath($dir)) {
                if ($name == 'root') throw new RuntimeException('Invalid config value "app.dir.root"="'.$dir.'" (not an absolute path)');
                $dir = $dirs['root'].'/'.$dir;
            }
            if (is_dir($dir)) $dir = realPath($dir);
        }; unset($dir);
        $this->set('app.dir', $dirs);


        // (3) create FileDependency and cache the instance
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
