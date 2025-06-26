<?php
declare(strict_types=1);

namespace rosasurfer\ministruts;

use rosasurfer\ministruts\config\Config;
use rosasurfer\ministruts\config\ConfigInterface as IConfig;
use rosasurfer\ministruts\console\Command;
use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\di\DiInterface as Di;
use rosasurfer\ministruts\core\di\auto\CliServiceContainer;
use rosasurfer\ministruts\core\di\auto\WebServiceContainer;
use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\log\Logger;
use rosasurfer\ministruts\struts\FrontController;
use rosasurfer\ministruts\struts\Response;
use rosasurfer\ministruts\util\PHP;

/**
 * A class representing the application instance.
 */
class Application extends CObject {

    /** @var Application - the application itself */
    protected static self $instance;

    /** @var ?IConfig - the application's main configuration */
    protected static ?IConfig $config = null;

    /** @var Di|null - the application's service container */
    protected static ?Di $di = null;

    /** @var Command[] - registered CLI commands */
    private array $commands = [];


    /**
     * Create and initialize a new MiniStruts application.
     *
     * @param  array<string, string> $options [optional] - array with explicit application config settings, specifically:
     *
     *        "app.dir.root"            - string: The project's root directory.
     *                                            (default: the current directory)
     *
     *        "app.dir.config"          - string: The project's configuration location (directory or file path).
     *                                            (default: the current directory)
     *
     *        "app.error-handler.mode"  - string: Error handling mode, one of:
     *                                            "ignore":    PHP errors and exceptions are ignored.
     *                                            "log":       PHP errors and exceptions are logged but execution continues. Exceptions terminate
     *                                                         the script.
     *                                            "exception": PHP errors are converted to exceptions, both are logged and both terminate the
     *                                                         script (default).
     *
     *        "app.error-handler.level" - int:    Error reporting level. If set the level is fixed and userland code will not be able to modify
     *                                            error reporting at runtime (default: the current runtime reporting level).
     *
     * All passed settings are added to the regular application configuration.
     */
    public function __construct(array $options = []) {
        if (isset(self::$instance)) throw new IllegalStateException('Cannot create more than one Application instance');
        self::$instance = $this;

        // setup error handling
        $errorMode  = $options['app.error-handler.mode'] ?? 'exception';
        $errorLevel = $options['app.error-handler.level'] ?? null;
        $this->initErrorHandling($errorLevel, $errorMode);

        $config = $this->initConfig($options);
        $configDir = $config->getString('app.dir.config');
        $di = $this->initDi($configDir);
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
    public function addCommand(Command $command): self {
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
     * @param  mixed $level - error reporting level
     * @param  mixed $mode  - error handling: "ignore | log | exception"
     *
     * @return void
     */
    protected function initErrorHandling($level, $mode): void {
        if (is_string($level)) {
            if (!strIsDigits($level)) throw new InvalidValueException("Invalid parameter \$level: \"$level\" (not numeric)");
            $level = (int)$level;
        }
        Assert::nullOrInt($level, '$level');
        Assert::string($mode, '$mode');

        switch ($mode) {
            case 'ignore':
                $iMode = ErrorHandler::MODE_IGNORE;
                break;
            case 'log':
                $iMode = ErrorHandler::MODE_LOG;
                break;
            case 'exception':
                $iMode = ErrorHandler::MODE_EXCEPTION;
                break;
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
     * @return IConfig
     */
    protected function initConfig(array $options): IConfig {
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
     * Load and initialize the dependency/service container.
     *
     * @param  string $directory - directory with service configurations
     *
     * @return Di
     */
    protected function initDi(string $directory): Di {
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
    protected function configure(): void {
        $config = self::$config;
        if (!$config) return;

        // ensure that we have an "app.id"
        $appId = $config->get('app.id', null);
        if (!$appId) $config->set('app.id', substr(md5($config->getString('app.dir.root')), 0, 16));

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
        register_shutdown_function(static function(): void {
            if (!self::$config) return;

            $warnLimit = php_byte_value(self::$config->get('log.warn.memory_limit', PHP_INT_MAX));
            $usedBytes = memory_get_peak_usage(true);
            if ($usedBytes > $warnLimit) {
                Logger::log('Memory consumption exceeded '.prettyBytes($warnLimit).' (peak usage: '.prettyBytes($usedBytes).')', L_WARN, ['class' => self::class]);
            }
        });
    }


    /**
     * Check for and execute specified admin tasks (needs admin rights).
     * To not interfer with the application the script will be terminated if any task was executed.
     *
     * @return void
     */
    protected function checkAdminTasks(): void {
        // __phpinfo__             : show the PHP configuration
        // __config__ + __phpinfo__: show the application configuration followed by the PHP configuration
        // __cache__               : show the cache admin interface
        $cacheInfoTask  = isset($_GET['__cache__'  ]);
        $phpInfoTask    = isset($_GET['__phpinfo__']) && !$cacheInfoTask;   // the cache task can't be combined with any other task
        $configInfoTask = isset($_GET['__config__' ]) && !$cacheInfoTask;

        if ($configInfoTask || $phpInfoTask || $cacheInfoTask) {
            $config = self::$config;
            if ($config && self::isAdminIP()) {
                // execute "config-info" task if enabled
                if ($configInfoTask) {
                    $configFiles = $config->getConfigFiles();
                    $files = [];
                    foreach ($configFiles as $file => $exists) {
                        $files[] = ($exists ? 'OK':'? ').'   '.str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file);
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
                           .print_r($config->dump(['sort'=>SORT_ASC]), true)
                           ?>
                        </pre>
                    </div>
                    <?php
                    if (!$phpInfoTask) exit(0);
                }

                // execute "php-info" task if enabled
                if ($phpInfoTask) {
                    PHP::phpinfo();
                    exit(0);
                }

                // execute "cache-info" task if enabled
                if ($cacheInfoTask) {
                    // TODO: include(__DIR__.'/debug/apc.php');
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
    protected function expandAppDirs(Config $config, string $rootDir): void {
        if (!strlen($rootDir) || isRelativePath($rootDir)) throw new InvalidValueException("Invalid config option \"app.dir.root\" = \"$rootDir\" (not an absolute path)");

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
     * @return void - the passed array is modified
     */
    protected function expandDirsRecursive(array &$dirs, string $rootDir): void {
        foreach ($dirs as &$dir) {
            if (is_array($dir)) {
                $this->expandDirsRecursive($dir, $rootDir);
                continue;
            }
            if (isRelativePath($dir)) {
                $dir = "$rootDir/$dir";
            }
            if (is_dir($dir)) $dir = realpath($dir);
        }
        unset($dir);
    }


    /**
     * Set the {@link Application}'s main configuration.
     *
     * @param  IConfig $configuration
     *
     * @return $this
     */
    protected function setConfig(IConfig $configuration): self {
        self::$config = $configuration;

        if (isset(self::$di)) {
            self::$di->set('config', $configuration);
        }
        return $this;
    }


    /**
     * Set the {@link Application}s dependency/service container.
     *
     * @param  Di $di
     *
     * @return $this
     */
    protected function setDi(Di $di): self {
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
     * Return the {@link Application}'s dependency container. This method should be used to access
     * the container from a non-class context (i.e. from procedural code).
     *
     * @return ?Di
     */
    public static function getDi(): ?Di {
        return self::$di;
    }


    /**
     * Whether the current remote IP is white-listed for admin access. 127.0.0.1 and the server's own IP
     * are always white-listed. More IP addresses can be white-listed via configuration.
     *
     * @return bool
     *
     * @example
     * <pre>
     *  configuration example:
     *  ----------------------
     *  admin.ip.a-name       = <ip-address>        // the 'name' field is an arbitrary value
     *  admin.ip.another-name = <ip-address>
     * </pre>
     */
    public static function isAdminIP(): bool {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return false;
        }

        static $whiteList = null;
        if (!$whiteList && self::$config) {
            $values = self::$config->get('admin.ip', []);
            if (!\is_array($values)) $values = [$values];
            $whiteList = \array_values($values);
        }
        $list = \array_merge($whiteList ?? [], ['127.0.0.1', $_SERVER['SERVER_ADDR']]);

        return \in_array($_SERVER['REMOTE_ADDR'], $list, true);
    }
}
