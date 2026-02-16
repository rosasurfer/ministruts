<?php
declare(strict_types=1);

namespace rosasurfer\ministruts;

use rosasurfer\ministruts\config\Config;
use rosasurfer\ministruts\config\PropertyConfig;
use rosasurfer\ministruts\console\Command;
use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\container\ContainerException;
use rosasurfer\ministruts\core\container\ContainerInterface as Container;
use rosasurfer\ministruts\core\container\DefaultCliContainer;
use rosasurfer\ministruts\core\container\DefaultWebContainer;
use rosasurfer\ministruts\core\container\service\ServiceNotFoundException;
use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\IllegalStateException;
use rosasurfer\ministruts\core\exception\InvalidValueException;
use rosasurfer\ministruts\core\exception\RuntimeException;
use rosasurfer\ministruts\log\Logger;
use rosasurfer\ministruts\struts\FrontController;
use rosasurfer\ministruts\struts\Response;
use rosasurfer\ministruts\util\PHP;

/**
 * A class representing the application instance.
 */
class Application extends CObject {

    /** @var ?Application - the application instance */
    protected static ?self $instance;

    /** @var Config - the main configuration */
    protected Config $config;

    /** @var Container - the dependency container */
    protected Container $container;

    /** @var Command[] - registered CLI commands */
    protected array $commands = [];

    /**
     * Create and initialize a new application.
     *
     * @param  array<string, ?scalar> $options [optional] - array with explicit application config settings, specifically:
     *
     *        "app.dir.root"            - string: The project's root directory.
     *                                            (default: the current directory)
     *
     *        "app.dir.config"          - string: The project's configuration location (directory or file path).
     *                                            (default: the current directory)
     *
     *        "app.error-handler.mode"  - string: Error handling mode, one of:
     *                                            "off":       No error handling.
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

        // initialize the configuration
        $this->config = $this->initConfig($options);
        $configDir = $this->config->string('app.dir.config');

        // initialize the container
        $this->container = $this->initContainer($configDir)
                                ->set('app', $this)
                                ->set('config', $this->config);

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
     * @param  mixed $mode  - error handling mode: "off | log | exception"
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
            case 'off':
                return;
            case 'log':
                $iMode = ErrorHandler::MODE_LOG;
                break;
            case 'exception':
                $iMode = ErrorHandler::MODE_EXCEPTION;
                break;
            default:
                throw new InvalidValueException("Invalid parameter \$mode: \"$mode\"");
        }
        ErrorHandler::setupErrorHandling($level, $iMode);
    }


    /**
     * Create and initialize the main configuration.
     *
     * @param  array<string, ?scalar> $options - configuration options as passed to the framework loader
     *
     * @return Config
     */
    protected function initConfig(array $options): Config {
        $location = (string)($options['app.dir.config'] ?? getcwd());
        $config = PropertyConfig::createFrom($location);
        unset($options['app.dir.config']);

        // set application root directory
        $rootDir = (string)($options['app.dir.root'] ?? getcwd());
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
     * Create and initialize a new dependency container.
     *
     * @param  string $directory - directory with service configurations
     *
     * @return Container
     */
    protected function initContainer(string $directory): Container {
        $class = CLI ? DefaultCliContainer::class : DefaultWebContainer::class;
        return new $class($directory);
    }


    /**
     * Adjust the application's runtime behavior.
     *
     * @return void
     */
    protected function configure(): void {
        $config = $this->config;

        // ensure that we have an "app.id"
        if (!$config->string('app.id', '')) {
            $config->set('app.id', substr(md5($config->string('app.dir.root')), 0, 16));
        }

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

        // in CLI mode: register SIGINT handler to catch Ctrl-C; scripts must regularly call Process::dispatchSignals()
        if (CLI && function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, static function(int $signo, $signinfo = null): void {
                exit(1);            // only an explicit exit() will execute destructors
            });
        }

        // on shutdown: log excessive memory consumption
        register_shutdown_function(function(): void {
            $warnLimit = php_byte_value($this->config->string('log.warn.memory_limit', (string)PHP_INT_MAX));
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
            if (self::isAdminIP()) {
                // execute "config-info" task if enabled
                if ($configInfoTask) {
                    $configFiles = $this->config->getConfigFiles();
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
                           .print_r($this->config->dump(['sort'=>SORT_ASC]), true)
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
        if ($rootDir=='' || isRelativePath($rootDir)) throw new InvalidValueException("Invalid config option \"app.dir.root\" = \"$rootDir\" (not an absolute path)");

        $rootDir = rtrim(str_replace('\\', '/', $rootDir), '/');
        $dirs = $config->array('app.dir', []);
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
     * @param  Config $configuration
     *
     * @return $this
     */
    public function setConfig(Config $configuration): self {
        $this->config = $configuration;
        $this->container->set('config', $configuration);
        return $this;
    }


    /**
     * Set the {@link Application}'s dependency container.
     *
     * @param  Container $container
     *
     * @return $this
     */
    public function setContainer(Container $container): self {
        if (!$container->has('app')) {
            $container->set('app', $this);
        }
        if (!$container->has('config')) {
            $container->set('config', $this->config);
        }
        $this->container = $container;
        return $this;
    }


    /**
     * Return the {@link Application}'s dependency container.
     *
     * @return ?Container
     */
    public static function container(): ?Container {
        $app = self::$instance;
        return $app ? $app->container : null;
    }


    /**
     * Service locator
     *
     * Resolve an application service and return its implementation. This method always returns the same instance.
     * Alias of {@link Container::get()}.
     *
     * @param  string $name
     *
     * @return object
     *
     * @throws ServiceNotFoundException if the service was not found
     * @throws ContainerException       if the dependency could not be resolved
     */
    public static function service(string $name): object {
        if ($container = self::container()) {
            return $container->get($name);
        }
        throw new RuntimeException('Application not initialized');
    }


    /**
     * Factory pattern
     *
     * Resolve a named dependency and return a new instance of the wrapped object.
     *
     * @param  string   $name
     * @param  mixed ...$args - instantiation arguments
     *
     * @return object
     *
     * @throws ServiceNotFoundException if the service was not found
     * @throws ContainerException       if the dependency could not be resolved
     */
    public static function factory(string $name, ...$args): object {
        if ($container = self::container()) {
            return $container->factory($name, ...$args);
        }
        throw new RuntimeException('Application not initialized');
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
     *  admin.ip.a-name       = <ip-address>            // the 'name' field is arbitrary
     *  admin.ip.another-name = <ip-address>
     * </pre>
     */
    public static function isAdminIP(): bool {
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            return false;
        }

        static $whiteList = null;
        if (!$whiteList && self::$instance) {
            $values = (array) self::$instance->config->get('admin.ip', []);
            $whiteList = array_flip($values);
        }                                               // add always white-listed IPs (default)
        $list = $whiteList + ['127.0.0.1'=>'localhost', $_SERVER['SERVER_ADDR']=>'serverIP'];

        $addr = $_SERVER['HTTP_X_REAL_IP']              // nginx and others
             ?? $_SERVER['HTTP_TRUE_CLIENT_IP']         // Akamai, Cloudflare Enterprise
             ?? $_SERVER['HTTP_CF_CONNECTING_IP']       // Cloudflare
             ?? $_SERVER['HTTP_X_FORWARDED_FOR']        // de facto standard
             ?? $_SERVER['HTTP_X_UP_FORWARDED_FOR']     // legacy mobile
             ?? $_SERVER['HTTP_CLIENT_IP']              // legacy proxies
             ?? $_SERVER['REMOTE_ADDR'];
        $remoteIP = trim(explode(',', $addr, 2)[0]);

        return isset($list[$remoteIP]);
    }
}
