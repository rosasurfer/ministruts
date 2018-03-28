<?php
namespace rosasurfer;

use rosasurfer\config\AutoConfig;
use rosasurfer\config\Config;
use rosasurfer\config\ConfigInterface as IConfig;
use rosasurfer\core\Object;
use rosasurfer\debug\ErrorHandler;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;
use rosasurfer\log\Logger;
use rosasurfer\ministruts\FrontController;
use rosasurfer\ministruts\Response;
use rosasurfer\util\PHP;


/**
 * A wrapper object for initializing and running an application.
 */
class Application extends Object {


    /** @var int - error handling mode in which regular PHP errors are logged */
    const LOG_ERRORS       = ErrorHandler::LOG_ERRORS;

    /** @var int - error handling mode in which regular PHP errors are converted to exceptions and thrown back */
    const THROW_EXCEPTIONS = ErrorHandler::THROW_EXCEPTIONS;


    /**
     * Create and initialize a new MiniStruts application.
     *
     * @param  array $options [optional] - Expects an array with any of the following options:
     *
     *        "app.config"            - IConfig: The project's configuration as an instance.<br>
     *
     *        "app.dir.config"        - string:  The project's configuration location as a directory or a file.<br>
     *                                           (default: directory returned by the instance passed in "app.config" or the<br>
     *                                           current directory)<br>
     *
     *        "app.dir.root"          - string:  The project's root directory.<br>
     *                                           (default: the current directory)<br>
     *
     *        "app.global-helpers"    - bool:    If set to TRUE the helper functions and constants defined in "src/helpers.php"<br>
     *                                           are mapped to the global PHP namespace.<br>
     *                                           (default: FALSE)<br>
     *
     *        "app.handle-errors"     - string:  How to handle regular PHP errors. If set to "strict" errors are converted to<br>
     *                                           PHP ErrorExceptions and thrown. If set to "weak" errors are only logged and<br>
     *                                           execution continues. If set to "ignore" you have to setup your own error<br>
     *                                           handling mechanism.<br>
     *                                           (default: "strict")<br>
     *
     *        "app.handle-exceptions" - bool:    If set to TRUE exceptions are handled by the built-in exception handler.<br>
     *                                           If set to FALSE you have to setup your own exception handling mechanism.<br>
     *                                           (default: TRUE)<br>
     *
     *        Additional options are added to the application's default configuration {@link Config} as regular config values.
     */
    public function __construct(array $options = []) {
        // set default values
        if (!isSet($options['app.handle-errors'    ])) $options['app.handle-errors'    ] = 'strict';
        if (!isSet($options['app.handle-exceptions'])) $options['app.handle-exceptions'] = true;
        if (!isSet($options['app.global-helpers'   ])) $options['app.global-helpers'   ] = false;

        // (1) setup configuration
        $this->setupErrorHandling    ($options['app.handle-errors'    ]);
        $this->setupExceptionHandling($options['app.handle-exceptions']);
        $this->loadGlobalHelpers     ($options['app.global-helpers'   ]);

        $config = $this->loadConfiguration($options);

        // (2) check "app.id"
        $appId = $config->get('app.id', null);
        if (!$appId) $config->set('app.id', subStr(md5($config->get('app.dir.root')), 0, 16));

        // (3) check for PHP admin tasks if the remote IP has allowance
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

        // (4) load further php.ini settings from the configuration
        $this->configurePhp();

        // (5) execute "config-info" task if enabled
        if ($configInfoTask) {
            $configFiles = Config::getDefault()->getMonitoredFiles();
            $files = [];
            foreach ($configFiles as $file => $exists) {
                $files[] = ($exists ? 'OK':'? ').'   '.$file;
            }
            ?>
            <div align="left" style="display:initial; visibility:initial; clear:both;
                                     position:relative; z-index:65535; top:initial; left:initial;
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
                   . print_r(Config::getDefault()->dump(['sort'=>SORT_ASC]), true)
                   ?>
                </pre>
            </div>
            <?php
            if (!$phpInfoTask && !$phpInfoAfterConfigTask)
                exit(0);
        }

        // (6) execute "phpinfo" after-config task if enabled
        if ($phpInfoTask || $phpInfoAfterConfigTask) {
            PHP::phpInfo();
            exit(0);
        }

        // (7) execute "cache-info" task if enabled
        if ($cacheInfoTask) {
            //include(MINISTRUTS_ROOT.'/src/debug/apc.php'); // TODO: not yet implemented
        }

        // (8) enforce mission-critical PHP requirements (after processing any admin tasks)
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
        if (CLI) {                              // cli application
            $response = null;
        }
        else {                                  // web application
            $response = FrontController::processRequest($options);
        }
        return $response;
    }


    /**
     * Update the PHP configuration with user defined settings.
     */
    private function configurePhp() {
        $memoryWarnLimit = php_byte_value(Config::getDefault()->get('log.warn.memory_limit', 0));
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
     * Load the specified configuration and register it as the application's default configuration.
     *
     * @param  array $options - config options as passed to the framework loader
     *
     * @return IConfig - initialized configuration
     */
    private function loadConfiguration(array $options) {
        if (isSet($options['app.dir.config']) && isSet($options['app.config']))
            throw new RuntimeException('Only one of the application options "app.config" or "app.dir.config" can be provided.');

        if (!isSet($options['app.config'])) {
            $location = isSet($options['app.dir.config']) ? $options['app.dir.config'] : getCwd();
            $config = new AutoConfig($location);
            unset($options['app.dir.config']);
        }
        else {
            $config = $options['app.config'];
            if (!$config instanceof IConfig) throw new IllegalTypeException('Illegal type of application option["app.config"]: '.getType($config));
            if (!$config->get('app.dir.config', false)) {
                $files = $config->getMonitoredFiles();
                end($files);
                list($file, $exists) = each($files);
                $config->set('app.dir.config', dirName($file));
            }
            unset($options['app.config']);
        }

        $rootDir = isSet($options['app.dir.root']) ? $options['app.dir.root'] : getCwd();
        unset($options['app.dir.root']);

        // copy all remaining options
        foreach ($options as $name => $value) {
            if (is_string($name))
                $config->set($name, $value);
        }

        // set root directory and expand relative app directories
        $config->set('app.dir.root', $rootDir);
        $this->expandAppDirs($config, $rootDir);

        // register the instance as the application's main configuration
        return Config::setDefault($config);
    }


    /**
     * Expand relative "app.dir.*" values by the specified root directory.
     *
     * @param  IConfig $config  - application configuration
     * @param  string  $rootDir - application root directory
     */
    private function expandAppDirs(IConfig $config, $rootDir) {
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
    private function expandDirsRecursive(array &$dirs, $rootDir) {
        $self = __FUNCTION__;

        foreach ($dirs as $name => &$dir) {
            if (is_array($dir)) {
                $this->$self($dir, $rootDir);
                continue;
            }
            if (isRelativePath($dir))
                $dir = $rootDir.'/'.$dir;
            if (is_dir($dir)) $dir = realPath($dir);
        }; unset($dir);
    }


    /**
     * Setup the application's error handling.
     *
     * @param  int|string $value - configuration value as passed to the framework loader
     */
    private function setupErrorHandling($value) {
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
    private function setupExceptionHandling($value) {
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
     * Map the helper constants and functions in namespace "\rosasurfer" to the global namespace.
     *
     * @param  mixed $value - configuration value as passed to the framework loader
     */
    private function loadGlobalHelpers($value) {
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
    private function replaceComposer($value) {
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

            if (!$config=Config::getDefault())
                return in_array($_SERVER['REMOTE_ADDR'], $ips);

            $values = $config->get('admin.ip.whitelist', []);
            if (!is_array($values)) $values = [$values];
            $whiteList = array_keys(array_flip(array_merge($ips, $values)));
        }
        return in_array($_SERVER['REMOTE_ADDR'], $whiteList);
    }
}
