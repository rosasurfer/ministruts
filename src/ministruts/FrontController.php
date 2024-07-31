<?php
namespace rosasurfer\ministruts;

use rosasurfer\cache\Cache;
use rosasurfer\cache\monitor\FileDependency;
use rosasurfer\config\ConfigInterface;
use rosasurfer\core\Singleton;
use rosasurfer\core\exception\IllegalStateException;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\core\exception\RuntimeException;
use rosasurfer\di\proxy\Request as RequestProxy;
use rosasurfer\net\http\HttpResponse;

use function rosasurfer\strLeftTo;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
use const rosasurfer\MINUTE;
use const rosasurfer\WINDOWS;


/**
 * FrontController
 *
 * Represents the instantiated Struts XML configuration of the application. Cached and re-used across multiple HTTP requests.
 * The implementation must be "request safe" (multiple requests use the same deserialized state), meaning it must not hold
 * variable runtime status.
 */
class FrontController extends Singleton {


    /** @var Module[] - all registered Struts modules with the module prefix as index */
    private $modules = [];


    /**
     * Return the {@link \rosasurfer\core\Singleton} instance of this class. The instance might be loaded from a cache.
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

            if (!$controller) {                                             // @phpstan-ignore booleanNot.alwaysTrue (TODO: keep for caching fix)
                $controller = self::getInstance(static::class);

                $config = self::di('config');
                if (!$config) throw new RuntimeException('Application configuration not found');

                $configDir = $config['app.dir.config'];
                $configFile = str_replace('\\', '/', $configDir.'/struts-config.xml');

                $dependency = new FileDependency($configFile);
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

        /** @var ConfigInterface $config */
        $config = $this->di('config');

        // lookup Struts configuration files
        $configDir = $config->get('app.dir.struts', null);
        !$configDir && $configDir=$config['app.dir.config'];                        // fall-back to standard config directory

        $mainConfig = str_replace('\\', '/', $configDir.'/struts-config.xml');      // main module config
        if (!is_file($mainConfig)) throw new StrutsConfigException('Struts configuration file not found: "'.$mainConfig.'"');

        $subConfigs = glob($configDir.'/struts-config-*.xml', GLOB_ERR) ?: [];      // scan for submodule configs
        $configs    = \array_merge([$mainConfig], $subConfigs);

        // create and register a Module for each found configuration file
        $file = $ex = null;
        try {
            foreach ($configs as $file) {
                $basename = basename($file, '.xml');
                $prefix = (strStartsWith($basename, 'struts-config-')) ? substr($basename, 14).'/' : '';

                $module = new Module($file, $prefix);
                $module->freeze();

                if (isset($this->modules[$prefix])) throw new StrutsConfigException('All Struts modules must have unique prefixes, non-unique prefix found: "'.$prefix.'"');
                $this->modules[$prefix] = $module;
            }
        }
        catch (IRosasurferException $ex) {}
        catch (\Throwable           $ex) { $ex = new StrutsConfigException($ex->getMessage(), $ex->getCode(), $ex); }
        catch (\Exception           $ex) { $ex = new StrutsConfigException($ex->getMessage(), $ex->getCode(), $ex); }   // @phpstan-ignore catch.alreadyCaught (PHP5 compatibility)

        if ($ex) throw $ex->addMessage('Error instantiating Struts module from file "'.$file.'"');
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
        $request = RequestProxy::instance();
        $response = Response::me();

        if (strStartsWith($request->getPath(), '/')) {
            // select the Module
            $prefix = $controller->getModulePrefix($request);
            $module = $controller->modules[$prefix];
            $request->setAttribute(MODULE_KEY, $module);

            // get the RequestProcessor
            $processor = $controller->getRequestProcessor($module, $options);

            // process the request
            $processor->process($request, $response);
        }
        else {
            // proxy request or any other invalid request: HTTP 404
            $response->setStatus(HttpResponse::SC_NOT_FOUND);
            header('HTTP/1.1 404 Not Found', true, HttpResponse::SC_NOT_FOUND);

            echo <<<FRONTCONTROLLER_PROCESS_REQUEST_ERROR_SC_404
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
<hr>
<address>...lamented the MiniStruts.</address>
</body></html>
FRONTCONTROLLER_PROCESS_REQUEST_ERROR_SC_404;
        }
        return $response;
    }


    /**
     * Resolve the prefix of the {@link Module} responsible for processing of the passed {@link Request}.
     *
     * @param  Request $request
     *
     * @return string - Module prefix
     */
    protected function getModulePrefix(Request $request) {
        $requestPath = $request->getPath();
        $baseUri     = $request->getApplicationBaseUri();

        if (!strStartsWith($requestPath, $baseUri))
            throw new RuntimeException('Can not resolve module prefix from request path "'.$requestPath.'" (application base URI: "'.$baseUri.'")');

        $value = substr($requestPath, strlen($baseUri));        // baseUri ends with and prefix doesn't start with a slash
        if (strlen($value)) {
            $value = strLeftTo($value, '/').'/';                // the prefix ends with a slash only for non-main modules
        }

        return isset($this->modules[$value]) ? $value : '';
    }


    /**
     * Get the {@link RequestProcessor} instance responsible for processing requests to the passed {@link Module}.
     *
     * @param  Module $module
     * @param  array  $options - processing runtime options
     *
     * @return RequestProcessor
     */
    protected function getRequestProcessor(Module $module, array $options) {
        $class = $module->getRequestProcessorClass();
        return new $class($module, $options);
    }
}
