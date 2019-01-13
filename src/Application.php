<?php
namespace rosasurfer;

use rosasurfer\config\ConfigInterface;
use rosasurfer\config\auto\DefaultConfig;
use rosasurfer\core\Object;
use rosasurfer\debug\ErrorHandler;
use rosasurfer\di\Di;
use rosasurfer\di\DiInterface;
use rosasurfer\di\auto\DefaultCliDi;
use rosasurfer\di\auto\DefaultDi;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\log\Logger;
use rosasurfer\ministruts\FrontController;
use rosasurfer\ministruts\Response;
use rosasurfer\util\PHP;


/**
 * A wrapper object representing a running application.
 */
class Application extends Object {


    /** @var ConfigInterface - the application's current default configuration */
    protected static $defaultConfig;

    /** @var DiInterface - the application's current default DI container */
    protected static $defaultDi;


    /** @var int - error handling mode in which regular PHP errors are only logged */
    const LOG_ERRORS       = ErrorHandler::LOG_ERRORS;

    /** @var int - error handling mode in which regular PHP errors are converted to exceptions and thrown back */
    const THROW_EXCEPTIONS = ErrorHandler::THROW_EXCEPTIONS;


    /**
     * Create and initialize a new MiniStruts application.
     *
     * @param  array $options [optional] - Expects an array with any of the following options:
     *
     *        "app.dir.config"        - string:  The project's configuration location as a directory or a file.<br>
     *                                           (default: directory returned by the instance passed in "app.config" or the<br>
     *                                           current directory)<br>
     *
     *        "app.dir.root"          - string:  The project's root directory.<br>
     *                                           (default: the current directory)<br>
     *
     *        "app.globals"           - bool:    If set to TRUE definitions in "src/helper.php"<br> are mapped to the global
     *                                           PHP namespace.<br>
     *                                           (default: FALSE)<br>
     *
     *        "app.handle-errors"     - string:  How to handle regular PHP errors. If set to "strict" errors are converted to<br>
     *                                           PHP ErrorExceptions and thrown back. If set to "weak" errors are only logged<br>
     *                                           and execution continues. If set to "ignore" you have to setup your own error<br>
     *                                           handling mechanism.<br>
     *                                           (default: "strict")<br>
     *
     *        "app.handle-exceptions" - bool:    If set to TRUE exceptions are handled by the built-in exception handler.<br>
     *                                           If set to FALSE you have to setup your own exception handling mechanism.<br>
     *                                           (default: TRUE)<br>
     *
     * All further options are added to the application's current default configuration as regular config values.
     */
    public function __construct(array $options = []) {
        // set default values
        if (!isSet($options['app.handle-errors'    ])) $options['app.handle-errors'    ] = 'strict';
        if (!isSet($options['app.handle-exceptions'])) $options['app.handle-exceptions'] = true;
        if (!isSet($options['app.globals'          ])) $options['app.globals'          ] = isSet($options['app.global-helpers']) ? $options['app.global-helpers'] : false;

        // setup the configuration
        $this->setupErrorHandling    ($options['app.handle-errors'    ]);
        $this->setupExceptionHandling($options['app.handle-exceptions']);
        $this->loadGlobals           ($options['app.globals'          ]);

        /** @var DefaultConfig $config */
        $config = $this->initDefaultConfiguration($options);
        $this->setDefaultConfig($config);

        /** @var DiInterface $di */
        $di = $this->initDefaultDi($config['app.dir.config']);
        $di->set('app', $this)
           ->set('config', $config);
        $this->setDefaultDi($di);

        // check "app.id"
        $appId = $config->get('app.id', null);
        if (!$appId) $config->set('app.id', subStr(md5($config['app.dir.root']), 0, 16));

        // check for PHP admin tasks if the remote IP has allowance
        // __phpinfo__             : show PHP config at start of script
        // __config__ + __phpinfo__: show PHP config after loading of the application configuration
        // __cache__               : show cache admin interface
        $phpInfoTask = $phpInfoAfterConfigTask = $configInfoTask = $cacheInfoTask = false;

        if (isSet($_GET['__phpinfo__']) || isSet($_GET['__config__']) || isSet($_GET['__cache__'])) {
            if (self::isAdminIP()) {
                foreach ($_GET as $param => $value) {
                    if ($param == '__phpinfo__') {
                        if ($configInfoTask) {
                            $phpInfoTask            = false;
                            $phpInfoAfterConfigTask = true;
                        }
                        else {
                            $phpInfoTask = true;
                        }
                        break;                                    // stop parsing after "__phpinfo__"
                    }
                    else if ($param == '__config__') {
                        $configInfoTask = true;
                        if ($phpInfoTask) {
                            $phpInfoTask            = false;
                            $phpInfoAfterConfigTask = true;
                        }
                    }
                    else if ($param == '__cache__') {
                        $cacheInfoTask = true;
                        break;                                    // stop parsing after "__cache__"
                    }
                }
            }
        }

        // load further php.ini settings from the configuration
        $this->configurePhp();

        // execute "config-info" task if enabled
        if ($configInfoTask) {
            $configFiles = $config->getMonitoredFiles();
            $files = [];
            foreach ($configFiles as $file => $exists) {
                $files[] = ($exists ? 'OK':'? ').'   '.$file;
            }
            ?>
            <div align="left" style="display:initial; visibility:initial; clear:both;
                                     position:relative; z-index:4294967295; top:initial; left:initial;
                                     width:initial; height:initial;
                                     margin:0; padding:4px;
                                     font:normal normal 12px/normal arial,helvetica,sans-serif;
                                     color:black; background-color:white">
                <pre style="margin-bottom:24px"><?=
                    'Application configuration files:'.NL
                   .'--------------------------------'.NL
                   .join(NL, $files)                  .NL
                                                      .NL
                                                      .NL
                   .'Application configuration:'      .NL
                   .'--------------------------'      .NL
                   . print_r($config->dump(['sort'=>SORT_ASC]), true)
                   ?>
                </pre>
            </div>
            <?php
            if (!$phpInfoTask && !$phpInfoAfterConfigTask)
                exit(0);
        }

        // execute "phpinfo" after-config task if enabled
        if ($phpInfoTask || $phpInfoAfterConfigTask) {
            PHP::phpInfo();
            exit(0);
        }

        // execute "cache-info" task if enabled
        if ($cacheInfoTask) {
            //include(MINISTRUTS_ROOT.'/src/debug/apc.php'); // TODO: not yet implemented
        }

        // enforce mission-critical PHP requirements (after processing of any admin tasks)
        !php_ini_loaded_file()                   && exit(1|echoPre('application error (see error log'.(self::isAdminIP() ? ': '.(strLen($errorLog=ini_get('error_log')) ? $errorLog : (CLI ? 'STDERR':'web server')):'').')')|error_log('Error: No "php.ini" configuration file was loaded.'));
        !CLI && !ini_get_bool('short_open_tag')  && exit(1|echoPre('application error (see error log'.(self::isAdminIP() ? ': '.(strLen($errorLog=ini_get('error_log')) ? $errorLog : (CLI ? 'STDERR':'web server')):'').')')|error_log('Error: The PHP configuration value "short_open_tag" must be enabled (security).'));
        !CLI && ini_get('request_order') != 'GP' && exit(1|echoPre('application error (see error log'.(self::isAdminIP() ? ': '.(strLen($errorLog=ini_get('error_log')) ? $errorLog : (CLI ? 'STDERR':'web server')):'').')')|error_log('Error: The PHP configuration value "request_order" must be "GP" (current value "'.ini_get('request_order').'").'));
    }


    /**
     * Run the application and return the {@link Response} if a web application.
     *
     * @param  array $options [optional] - runtime options
     *
     * @return Response|null - the response if a web application or NULL if a command line application
     */
    public function run(array $options = []) {
        if (CLI) return null;
        return FrontController::processRequest($options);
    }


    /**
     * Update the PHP configuration with user defined settings.
     */
    protected function configurePhp() {
        $memoryWarnLimit = php_byte_value($this->defaultConfig->get('log.warn.memory_limit', 0));
        if ($memoryWarnLimit > 0) {
            register_shutdown_function(function() use ($memoryWarnLimit) {
                $usedBytes = memory_get_peak_usage($real=true);
                if ($usedBytes > $memoryWarnLimit) {
                    Logger::log('Memory consumption exceeded '.prettyBytes($memoryWarnLimit).' (peak usage: '.prettyBytes($usedBytes).')', L_WARN, ['class' => __CLASS__]);
                }
            });
        }
        /*
        ini_set('arg_separator.output'    , '&amp;'                );
        ini_set('auto_detect_line_endings',  1                     );
        ini_set('default_mimetype'        , 'text/html'            );
        ini_set('default_charset'         , 'UTF-8'                );
        ini_set('ignore_repeated_errors'  ,  0                     );
        ini_set('ignore_repeated_source'  ,  0                     );
        ini_set('ignore_user_abort'       ,  1                     );
        ini_set('display_errors'          , (int)(CLI || LOCALHOST));
        ini_set('display_startup_errors'  , (int)(CLI || LOCALHOST));
        ini_set('log_errors'              ,  1                     );
        ini_set('log_errors_max_len'      ,  0                     );
        ini_set('track_errors'            ,  1                     );
        ini_set('html_errors'             ,  0                     );
        ini_set('session.use_cookies'     ,  1                     );
        ini_set('session.use_trans_sid'   ,  0                     );
        ini_set('session.cookie_httponly' ,  1                     );
        ini_set('session.referer_check'   , ''                     );
        ini_set('zend.detect_unicode'     ,  1                     );     // BOM header recognition
        */
    }


    /**
     * Load and initialize a {@link DefaultConfig}.
     *
     * @param  array $options - configuration options as passed to the framework loader
     *
     * @return DefaultConfig
     */
    protected function initDefaultConfiguration(array $options) {
        $configLocation = isSet($options['app.dir.config']) ? $options['app.dir.config'] : getCwd();
        $config = new DefaultConfig($configLocation);
        unset($options['app.dir.config']);

        $rootDir = isSet($options['app.dir.root']) ? $options['app.dir.root'] : getCwd();
        unset($options['app.dir.root']);

        // copy remaining config options to the DefaultConfig
        foreach ($options as $name => $value) {
            $config->set($name, $value);
        }

        // set root directory and expand relative app directories
        $config->set('app.dir.root', $rootDir);
        $this->expandAppDirs($config, $rootDir);

        return $config;
    }


    /**
     * Expand relative "app.dir.*" values by the specified root directory.
     *
     * @param  ConfigInterface $config  - application configuration
     * @param  string          $rootDir - application root directory
     */
    protected function expandAppDirs(ConfigInterface $config, $rootDir) {
        if (!is_string($rootDir))                          throw new IllegalTypeException('Illegal type of config option "app.dir.root": '.getType($rootDir));
        if (!strLen($rootDir) || isRelativePath($rootDir)) throw new InvalidArgumentException('Invalid config option "app.dir.root": "'.$rootDir.'" (not an absolute path)');

        $rootDir = rTrim(str_replace('\\', '/', $rootDir), '/');
        $dirs = $config->get('app.dir', []);
        $this->expandDirsRecursive($dirs, $rootDir);
        $config->set('app.dir', $dirs);                     // store everything back
    }


    /**
     * Expand an array of "app.dir.*" values by the specified root directory. The array may contain nested levels.
     *
     * @param  array &$dirs    - absolute or relative directory values
     * @param  string $rootDir - application root directory
     */
    protected function expandDirsRecursive(array &$dirs, $rootDir) {
        foreach ($dirs as $name => &$dir) {
            if (is_array($dir)) {
                $this->{__FUNCTION__}($dir, $rootDir);
                continue;
            }
            if (isRelativePath($dir))
                $dir = $rootDir.'/'.$dir;
            if (is_dir($dir)) $dir = realPath($dir);
        }; unset($dir);
    }


    /**
     * Load and initialize a default {@link DiInterface}.
     *
     * @param  string $serviceDir - directory with service configurations
     *
     * @return DiInterface
     */
    protected function initDefaultDi($serviceDir) {
        return !CLI ? new DefaultDi($serviceDir) : new DefaultCliDi($serviceDir);
    }


    /**
     * Setup the application's error handling.
     *
     * @param  int|string $value - configuration value as passed to the framework loader
     */
    protected function setupErrorHandling($value) {
        $flag = self::THROW_EXCEPTIONS;
        if (is_string($value)) {
            $value = trim(strToLower($value));
            if      ($value == 'weak'  ) $flag = self::LOG_ERRORS;  // default: THROW_EXCEPTIONS
            else if ($value == 'ignore') $flag = null;
        }
        if ($flag) {
            ErrorHandler::setupErrorHandling($flag);
        }
    }


    /**
     * Setup the application's exception handling.
     *
     * @param  bool|int|string $value - configuration value as passed to the framework loader
     */
    protected function setupExceptionHandling($value) {
        $enabled = true;
        if (is_bool($value) || is_int($value)) {
            $enabled = (bool) $value;
        }
        elseif (is_string($value)) {
            $value = trim(strToLower($value));
            if ($value=='0' || $value=='off' || $value=='false')
                $enabled = false;                                   // default: true
        }

        if ($enabled) {
            ErrorHandler::setupExceptionHandling();
        }
    }


    /**
     * Map common definitions in namespace "\rosasurfer" to the global namespace.
     *
     * @param  mixed $value - configuration value as passed to the framework loader
     */
    protected function loadGlobals($value) {
        $enabled = false;
        if (is_bool($value) || is_int($value)) {
            $enabled = (bool) $value;
        }
        elseif (is_string($value)) {
            $value   = trim(strToLower($value));
            $enabled = ($value=='1' || $value=='on' || $value=='true');
        }

        if ($enabled) {
            include(MINISTRUTS_ROOT.'/src/globals.php');
        }
    }


    /**
     * Replace an existing Composer class loader.
     *
     * @param  mixed $value - configuration value as passed to the framework loader
     */
    protected function replaceComposer($value) {
        $enabled = false;
        if (is_bool($value) || is_int($value)) {
            $enabled = (bool) $value;
        }
        elseif (is_string($value)) {
            $value   = trim(strToLower($value));
            $enabled = ($value=='1' || $value=='on' || $value=='true');
        }

        if ($enabled) {
            // replace Composer
        }
    }




    /**
     * Return the current default configuration of the {@link Application}. This is the configuration previously set
     * with {@link Application::setDefault()}.
     *
     * @return ConfigInterface
     */
    public static function getDefaultConfig() {
        return self::$defaultConfig;
    }


    /**
     * Set a new default configuration for the {@link Application}.
     *
     * @param  ConfigInterface $configuration
     *
     * @return ConfigInterface - the previously registered default configuration
     */
    final public static function setDefaultConfig(ConfigInterface $configuration) {
        $previous = self::$defaultConfig;
        self::$defaultConfig = $configuration;
        if (self::$defaultDi) {
            self::$defaultDi->set('config', $configuration);
        }
        return $previous;
    }


    /**
     * Return the default dependency injection container of the {@link Application}. This is the instance previously set
     * with {@link Application::setDefault()}.
     *
     * @return DiInterface
     */
    public static function getDefaultDi() {
        return self::$defaultDi;
    }


    /**
     * Set a new default dependency injection container for the {@link Application}.
     *
     * @param  DiInterface $di
     *
     * @return DiInterface - the previously registered default container
     */
    final public static function setDefaultDi(DiInterface $di) {
        $previous = self::$defaultDi;
        if (!$di->isService('app') && $previous && $previous->isService('app')) {
            $di['app'] = $previous['app'];
        }
        if (!$di->isService('config') && self::$defaultConfig) {
            $di['config'] = self::$defaultConfig;
        }
        self::$defaultDi = $di;
        return $previous;
    }


    /**
     * Whether or not the current remote IP address is white-listed for admin access. 127.0.0.1 and the web server's IP
     * address are always white-listed. More IP addresses can be white-listed per configuration:
     *
     *  "admin.ip.whitelist.<name> = <ip-address>"
     *
     * @return bool
     */
    public static function isAdminIP() {
        if (!isSet($_SERVER['REMOTE_ADDR']))
            return false;

        static $whiteList; if (!$whiteList) {
            $ips = ['127.0.0.1', $_SERVER['SERVER_ADDR']];

            if (!self::$defaultConfig)
                return in_array($_SERVER['REMOTE_ADDR'], $ips);

            $values = self::$defaultConfig->get('admin.ip.whitelist', []);
            if (!is_array($values)) $values = [$values];
            $whiteList = \array_keys(\array_flip(\array_merge($ips, $values)));
        }
        return in_array($_SERVER['REMOTE_ADDR'], $whiteList);
    }
}
