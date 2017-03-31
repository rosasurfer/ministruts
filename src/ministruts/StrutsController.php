<?php
namespace rosasurfer\ministruts;

use rosasurfer\cache\Cache;
use rosasurfer\config\Config;
use rosasurfer\core\Singleton;

use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\monitor\FileDependency;
use \Exception;

use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
use const rosasurfer\MINUTE;
use const rosasurfer\WINDOWS;


/**
 * StrutsController
 *
 * To avoid repeated loading and parsing of the XML configuration after instantiation the one and only StrutsController
 * instance is serialized, cached and re-used across following HTTP requests (until cache invalidation). For this reason
 * the class implementation is "request safe" (in analogy to "thread safety") and holds no variable runtime status.
 */
class StrutsController extends Singleton {


    /** @var Module[] - all registered Modules, array key is the Module prefix */
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
            // TODO: fix wrong lock usage (see TODO file)
            // synchronize parsing of the struts-config.xml
            // $lock = new FileLock($configFile);
            // $controller = $cache->get($class);                       // re-check after the lock is aquired

                if (!$controller) {
                    // create new controller instance...
                    $controller = Singleton::getInstance(static::class);

                    $configDir  = Config::getDefault()->getDirectory();
                    $configFile = str_replace('\\', '/', $configDir.'/struts-config.xml');
                    $dependency = FileDependency::create($configFile);
                    if (!WINDOWS && !LOCALHOST)                           // distinction dev/production
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

        // lookup configuration files
        $configDir  = Config::getDefault()->getDirectory().DIRECTORY_SEPARATOR;
        $mainConfig = $configDir.'struts-config.xml';                           // main module config
        if (!is_file($mainConfig)) throw new StrutsConfigException('Configuration file not found: "'.$mainConfig.'"');

        $subConfigs = glob($configDir.'struts-config-*.xml', GLOB_ERR) ?: [];   // scan for submodule configs
        $configs    = [$mainConfig] + $subConfigs;

        // create and register a Module for each found configuration file
        $file = null;
        try {
            foreach ($configs as $file) {
                $baseName = baseName($file, '.xml');
                $prefix = (strStartsWith($baseName, 'struts-config-')) ? '/'.subStr($baseName, 14) : '';

                $module = new Module($file, $prefix);
                $module->freeze();

                if (isSet($this->modules[$prefix])) throw new StrutsConfigException('All Struts modules must have unique prefixes, non-unique prefix found: "'.$prefix.'"');
                $this->modules[$prefix] = $module;
            }
        }
        catch (IRosasurferException $ex) {
            throw $ex->addMessage('Error loading config file "'.$file.'"');
        }
        catch (Exception $ex) {
            throw new RuntimeException('Error loading config file "'.$file.'"', null, $ex);
        }
    }


    /**
     * Process the current HTTP request.
     */
    public static function processRequest() {
        $controller = self::me();
        $request    = Request::me();
        $response   = Response::me();

        // select Module
        $prefix = $controller->getModulePrefix($request);
        $module = $controller->modules[$prefix];
        $request->setAttribute(MODULE_KEY, $module);

        // get RequestProcessor
        $processor = $controller->getRequestProcessor($module);

        // process Request
        $processor->process($request, $response);
    }


    /**
     * Resolve the prefix of the Module responsible for processing of the given Request
     *
     * @param  Request $request
     *
     * @return string - Module prefix
     */
    private function getModulePrefix(Request $request) {
        $requestPath = $request->getPath();
        $baseUri     = $request->getApplicationBaseUri();

        if (!strStartsWith($requestPath, $baseUri)) throw new RuntimeException('Can not resolve module prefix from request path: '.$requestPath);

        $value = strRightFrom($requestPath, $baseUri);
        $value = strLeftTo($value, '/');

        return isSet($this->modules[$value]) ? $value : '';
    }


    /**
     * Get the RequestProcessor instance responsible forthe given Module.
     *
     * @param  Module $module
     *
     * @return RequestProcessor
     */
    private function getRequestProcessor(Module $module) {
        $class = $module->getRequestProcessorClass();
        return new $class($module);
    }
}
