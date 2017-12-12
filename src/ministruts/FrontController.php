<?php
namespace rosasurfer\ministruts;

use rosasurfer\cache\Cache;
use rosasurfer\config\Config;
use rosasurfer\core\Singleton;
use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\RuntimeException;
use rosasurfer\monitor\FileDependency;

use function rosasurfer\strLeftTo;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
use const rosasurfer\MINUTE;
use const rosasurfer\WINDOWS;


/**
 * FrontController
 *
 * To avoid repeated loading and parsing of the XML configuration the FrontController instance is cached and re-used across
 * multiple HTTP requests (until cache invalidation). The class implementation is "request safe" and holds no variable
 * runtime status (similar to "thread safe" implementations in other languages).
 */
class FrontController extends Singleton {


    /** @var Module[] - all registered Struts modules (array key is the module prefix) */
    private $modules = [];


    /**
     * Return the singleton instance of this class. The instance might be loaded from a cache.
     *
     * @return static
     */
    public static function me() {
        if (CLI) throw new IllegalStateException('Can not use '.static::class.' in this context.');

        $cache = Cache::me();

        // cache hit?
        $controller = $cache->get(static::class);
        if (!$controller) {
            // synchronize parsing of the struts-config.xml                 // TODO: Don't lock on the config file because it can block
            // $lock = new FileLock($configFile);                           //       concurrent reads, see Todo-File at locking.
            //                                                              //
            // $controller = $cache->get($class);                           // re-check after the lock is aquired

                if (!$controller) {
                    $controller = Singleton::getInstance(static::class);
                    $configDir  = Config::getDefault()->get('app.dir.config');
                    $configFile = str_replace('\\', '/', $configDir.'/struts-config.xml');
                    $dependency = FileDependency::create($configFile);
                    if (!WINDOWS && !LOCALHOST)                             // distinction dev/production?  TODO: non-sense
                        $dependency->setMinValidity(1 * MINUTE);

                    // ...and cache it with a FileDependency
                    $cache->set(static::class, $controller, Cache::EXPIRES_NEVER, $dependency);
                }

            //$lock->release();
        }
        return $controller;
    }


    /**
     * Constructor
     *
     * Load and parse all Struts configuration files and create the corresponding object hierarchy.
     *
     * @throws StrutsConfigException on configuration errors
     */
    protected function __construct() {
        parent::__construct();

        // lookup Struts configuration files
        $configDir = Config::getDefault()->get('app.dir.struts', null);
        !$configDir && $configDir=Config::getDefault()->get('app.dir.config');      // fall-back to std config directory

        $mainConfig = str_replace('\\', '/', $configDir.'/struts-config.xml');      // main module config
        if (!is_file($mainConfig)) throw new StrutsConfigException('Struts configuration file not found: "'.$mainConfig.'"');

        $subConfigs = glob($configDir.'/struts-config-*.xml', GLOB_ERR) ?: [];      // scan for submodule configs
        $configs    = array_merge([$mainConfig], $subConfigs);

        // create and register a Module for each found configuration file
        $file = null;
        try {
            foreach ($configs as $file) {
                $baseName = baseName($file, '.xml');
                $prefix = (strStartsWith($baseName, 'struts-config-')) ? subStr($baseName, 14).'/' : '';

                $module = new Module($file, $prefix);
                $module->freeze();

                if (isSet($this->modules[$prefix])) throw new StrutsConfigException('All Struts modules must have unique prefixes, non-unique prefix found: "'.$prefix.'"');
                $this->modules[$prefix] = $module;
            }
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('Error loading config file "'.$file.'"');
        }
        catch (\Exception $ex) {
            throw new RuntimeException('Error loading config file "'.$file.'"', null, $ex);
        }
    }


    /**
     * Process the current HTTP request.
     *
     * @param  array $options [optional] - runtime options (default: none)
     *
     * @return Response - respone wrapper
     */
    public static function processRequest(array $options = []) {
        $controller = self::me();
        $request    = Request::me();
        $response   = Response::me();

        // select Module
        $prefix = $controller->getModulePrefix($request);
        $module = $controller->modules[$prefix];
        $request->setAttribute(MODULE_KEY, $module);

        // get RequestProcessor
        $processor = $controller->getRequestProcessor($module, $options);

        // process Request
        $processor->process($request, $response);

        return $response;
    }


    /**
     * Resolve the prefix of the {@link Module} responsible for processing of the given {@link Request}.
     *
     * @param  Request $request
     *
     * @return string - Module prefix
     */
    private function getModulePrefix(Request $request) {
        $requestPath = $request->getPath();
        $baseUri     = $request->getApplicationBaseUri();

        if (!strStartsWith($requestPath, $baseUri)) throw new RuntimeException('Can not resolve module prefix from request path: '.$requestPath);

        $value = subStr($requestPath, strLen($baseUri));        // baseUri ends with and prefix doesn't start with a slash
        if (strLen($value)) {
            $value = strLeftTo($value, '/').'/';                // the prefix ends with a slash only for non-root modules
        }

        return isSet($this->modules[$value]) ? $value : '';
    }


    /**
     * Get the {@link RequestProcessor} instance responsible for the given {@link Module}.
     *
     * @param  Module $module
     * @param  array  $options - processing runtime options
     *
     * @return RequestProcessor
     */
    private function getRequestProcessor(Module $module, array $options) {
        $class = $module->getRequestProcessorClass();
        return new $class($module, $options);
    }
}
