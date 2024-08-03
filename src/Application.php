<?php
declare(strict_types=1);

namespace rosasurfer\ministruts;

use rosasurfer\ministruts\config\Config;
use rosasurfer\ministruts\console\Command;
use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\di\DiInterface as Di;
use rosasurfer\ministruts\core\di\auto\CliServiceContainer;
use rosasurfer\ministruts\core\di\auto\WebServiceContainer;
use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\log\Logger;
use rosasurfer\ministruts\struts\FrontController;
use rosasurfer\ministruts\struts\Response;
use rosasurfer\ministruts\util\PHP;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\core\exception\InvalidTypeException;


/**
 * A class representing the application instance.
 *
 * TODO: support creation of multiple instances for testing
 */
class Application extends CObject {


    /** @var self - the application itself */
    protected static self $instance;

    /** @var ?Config - the application's main configuration */
    protected static $config = null;

    /** @var ?Di - the application's service container */
    protected static $di = null;

    /** @var Command[] - registered CLI commands */
    private $commands;


    /**
     * Create and initialize a new MiniStruts application.
     *
     * @param  array<string, string> $options [optional] - array with any of the following options:
     *
     *        "app.dir.root"       - string:  The project's root directory.
     *                                        (default: the current directory)
     *
     *        "app.dir.config"     - string:  The project's configuration location (directory or file path).
     *                                        (default: the current directory)
     *
     *        "app.error.level"    - int:     Error reporting level, e.g. E_ALL & ~E_STRICT
     *                                        (default: no change)
     *
     *        "app.error.on-error" - string:  Error handling mode, one of:
     *                                        "ignore":    PHP errors and exceptions are ignored.
     *                                        "log":       PHP errors and exceptions are logged but execution continues. Exceptions terminate
     *                                                     the script.
     *                                        "exception": PHP errors are converted to exceptions, both are logged and both terminate the
     *                                                     script (default).
     *
     * All further options are added to the application's configuration as regular config values.
     */
    public function __construct(array $options = []) {
        if (isset(self::$instance)) throw new IllegalStateException('Cannot create more than one Application instance');
        self::$instance = $this;

        // setup error handling
        $errorLevel = $options['app.error.level'   ] ?? error_reporting();
        $errorMode  = $options['app.error.on-error'] ?? 'exception';
        $this->initErrorHandling($errorLevel, $errorMode);

        /** @var Config $config */
        $config = $this->initConfig($options);

        /** @var Di di */
        $di = $this->initDi($config['app.dir.config']);
        $di->set('app', $this);
        $di->set('config', $config);

        // adjust runtime behavior
        $this->configure();

        // check for and execute specified admin tasks (needs admin rights)
        $this->checkAdminTasks();
    }


    /**
     * Register {@link Command} with the application for execution in CLI mode. An already registered command
     * with the same name will be overwritten.
     *
     * @param  Command $command
     *
     * @return $this
     */
    public function addCommand(Command $command) {
        $this->commands[$command->getName()] = $command;
        $command->freeze();
        return $this;
    }


    /**
     * Run the application.
     *
     * @param  array<string, scalar> $options [optional] - additional execution options (default: none)
     *
     * @return Response|int - the HTTP response wrapper for a web application, or the error status for a CLI application
     */
    public function run(array $options = []) {
        if (!CLI) {
            return FrontController::processRequest($options);
        }
        if ($this->commands) {
            if (sizeof($this->commands) > 1) echof('Multi-level commands are not yet supported.');

            /** @var Command $cmd */
            $cmd = first($this->commands);
            return $cmd->run();
        }
        return 0;
    }


    /**
     * Initialize error handling.
     *
     * @param mixed $level - error reporting level
     * @param mixed $mode  - error handling: "ignore | log | exception"
     *
     * @return void
     */
    protected function initErrorHandling($level, $mode) {
        if (is_string($level)) {
            if (!strIsDigits($level)) throw new InvalidValueException('Invalid parameter $level: "'.$level.'" (not numeric)');
            $level = (int)$level;
        }
        if (!is_int($level))   throw new InvalidTypeException('Invalid type of parameter $level: '.gettype($level).' (numeric expected)');
        if (!is_string($mode)) throw new InvalidTypeException('Invalid type of parameter $mode: '.gettype($mode).' (string expected)');

        switch ($mode) {
            case 'ignore':    $iMode = ErrorHandler::MODE_IGNORE;    break;
            case 'log':       $iMode = ErrorHandler::MODE_LOG;       break;
            case 'exception': $iMode = ErrorHandler::MODE_EXCEPTION; break;
            default:
                throw new InvalidValueException('Invalid parameter $mode: "'.$mode.'"');
        }
        ErrorHandler::setupErrorHandling($level, $iMode);
    }


    /**
     * Load and initialize the main configuration.
     *
     * @param  array<string, string> $options - configuration options as passed to the framework loader
     *
     * @return Config
     */
    protected function initConfig(array $options) {
        $config = Config::createFrom($options['app.dir.config'] ?? getcwd());
        $this->setConfig($config);
        unset($options['app.dir.config']);

        // set root directory
        $rootDir = $options['app.dir.root'] ?? getcwd();
        $config->set('app.dir.root', $rootDir);
        unset($options['app.dir.root']);

        // expand relative app directories
        $this->expandAppDirs($config, $rootDir);

        // add remaining options to the config
        foreach ($options as $name => $value) {
            $config->set($name, $value);
        }
        return $config;
    }


    /**
     * Load and initialize the service container.
     *
     * @param  string $directory - directory with service configurations
     *
     * @return Di
     */
    protected function initDi($directory) {
        $class = CLI ? CliServiceContainer::class : WebServiceContainer::class;
        $di = new $class($directory);
        $this->setDi($di);
        return $di;
    }


    /**
     * Adjust the application's runtime behavior.
     *
     * @return void
     */
    protected function configure() {
        $config = self::$config;
        if (!$config) return;

        // ensure we have an "app.id"
        $appId = $config->get('app.id', null);
        if (!$appId) $config->set('app.id', substr(md5($config['app.dir.root']), 0, 16));

        // enforce mission-critical PHP requirements
        if (!php_ini_loaded_file()) {
            $errorLog = '';
            if (self::isAdminIP()) {
                $errorLog = ': '.(ini_get('error_log') ?: (CLI ? 'STDERR':'web server'));
            }
            echof("application error (see error log$errorLog)");
            error_log('Error: No "php.ini" configuration file was loaded.');
            exit(1);
        }

        // log excessive memory consumption
        register_shutdown_function(function() {
            if (!self::$config) return;

            $warnLimit = php_byte_value(self::$config->get('log.warn.memory_limit', PHP_INT_MAX));
            $usedBytes = memory_get_peak_usage(true);
            if ($usedBytes > $warnLimit) {
                Logger::log('Memory consumption exceeded '.prettyBytes($warnLimit).' (peak usage: '.prettyBytes($usedBytes).')', L_WARN, ['class' => __CLASS__]);
            }
        });
        /*
        ini_set('arg_separator.output'    , '&amp;'                );
        ini_set('default_mimetype'        , 'text/html'            );
        ini_set('default_charset'         , 'UTF-8'                );
        ini_set('ignore_repeated_errors'  ,  0                     );
        ini_set('ignore_repeated_source'  ,  0                     );
        ini_set('ignore_user_abort'       ,  1                     );
        ini_set('display_errors'          , (int)(CLI || LOCALHOST));
        ini_set('display_startup_errors'  , (int)(CLI || LOCALHOST));
        ini_set('log_errors'              ,  1                     );
        ini_set('log_errors_max_len'      ,  0                     );
        ini_set('html_errors'             ,  0                     );
        ini_set('session.use_cookies'     ,  1                     );
        ini_set('session.use_trans_sid'   ,  0                     );
        ini_set('session.cookie_httponly' ,  1                     );
        ini_set('session.referer_check'   , ''                     );
        ini_set('zend.detect_unicode'     ,  1                     );     // BOM header recognition
        */
    }


    /**
     * Check for and execute specified admin tasks (needs admin rights).
     * To not interfer with the application the script will be terminated if any task was executed.
     *
     * @return void
     */
    protected function checkAdminTasks() {
        // __phpinfo__             : show the PHP configuration
        // __config__ + __phpinfo__: show the PHP and the application configuration
        // __cache__               : show the cache admin interface
        $cacheInfoTask  = isset($_GET['__cache__'  ]);
        $phpInfoTask    = isset($_GET['__phpinfo__']) && !$cacheInfoTask;   // the cache task can't be combined with any other task
        $configInfoTask = isset($_GET['__config__' ]) && !$cacheInfoTask;

        if ($configInfoTask || $phpInfoTask || $cacheInfoTask) {
            $config = self::$config;
            if ($config && self::isAdminIP()) {
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
                    if (!$phpInfoTask) exit(0);
                }

                // execute "phpinfo" task if enabled
                if ($phpInfoTask) {
                    PHP::phpinfo();
                    exit(0);
                }

                // execute "cache-info" task if enabled
                if ($cacheInfoTask) {
                    // TODO: include(ROOT_DIR.'/src/debug/apc.php');
                }
            }
        }
    }


    /**
     * Expand relative "app.dir.*" values by the specified root directory.
     *
     * @param  Config $config  - application configuration
     * @param  string $rootDir - application root directory
     *
     * @return void
     */
    protected function expandAppDirs(Config $config, $rootDir) {
        Assert::string($rootDir, '$rootDir');
        if (!strlen($rootDir) || isRelativePath($rootDir)) throw new InvalidValueException('Invalid config option "app.dir.root": "'.$rootDir.'" (not an absolute path)');

        $rootDir = rtrim(str_replace('\\', '/', $rootDir), '/');
        $dirs = $config->get('app.dir', []);
        $this->expandDirsRecursive($dirs, $rootDir);

        $config->set('app.dir', $dirs);                     // store everything back
    }


    /**
     * Expand an array of "app.dir.*" values by the specified root directory. The array may contain nested levels.
     *
     * @param  string[]|string[][] $dirs    - reference to an array of absolute or relative directory names
     * @param  string              $rootDir - application root directory
     *
     * @return void
     */
    protected function expandDirsRecursive(array &$dirs, $rootDir) {
        foreach ($dirs as &$dir) {
            if (is_array($dir)) {
                $this->{__FUNCTION__}($dir, $rootDir);
                continue;
            }
            if (isRelativePath($dir))
                $dir = $rootDir.'/'.$dir;
            if (is_dir($dir)) $dir = realpath($dir);
        }
        unset($dir);
    }


    /**
     * Set the {@link Application}'s main configuration.
     *
     * @param  Config $configuration
     *
     * @return $this
     */
    protected function setConfig(Config $configuration) {
        $previous = self::$config;
        self::$config = $configuration;

        if (isset(self::$di)) {
            self::$di->set('config', $configuration);
        }
        return $this;
    }


    /**
     * Set the {@link Application}s service container.
     *
     * @param  Di $di
     *
     * @return $this
     */
    protected function setDi(Di $di) {
        $previous = self::$di;

        if (!$di->has('app')) {
            $di->set('app', $this);
        }
        if (!$di->has('config') && self::$config) {
            $di->set('config', self::$config);
        }
        self::$di = $di;
        return $this;
    }


    /**
     * Return the {@link Application}'s service container. This method should be used to access the application's
     * service container from a non-class context (i.e. from procedural code).
     *
     * @return Di
     */
    public static function getDi() {
        return self::$di;
    }


    /**
     * Whether the current remote IP address is white-listed for admin access. 127.0.0.1 and the web server's IP
     * address are always white-listed. More IP addresses can be white-listed per configuration:
     *
     *  "admin.ip.whitelist.<name> = <ip-address>"
     *
     * @return bool
     */
    public static function isAdminIP() {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return false;
        }

        static $whiteList = null;
        if (!$whiteList) {
            $ips = ['127.0.0.1', $_SERVER['SERVER_ADDR']];

            if (!self::$config) {
                return in_array($_SERVER['REMOTE_ADDR'], $ips);
            }
            $values = self::$config->get('admin.ip.whitelist', []);
            if (!is_array($values)) $values = [$values];
            $whiteList = \array_keys(\array_flip(\array_merge($ips, $values)));
        }
        return in_array($_SERVER['REMOTE_ADDR'], $whiteList);
    }
}
