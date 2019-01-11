<?php
/**
 * Framework loader.
 *
 * Load helper functions and constants and register the internal class loader.
 */
namespace rosasurfer;

use rosasurfer\core\Object;
use rosasurfer\core\ObjectTrait;
use rosasurfer\di\DiAwareTrait;
use rosasurfer\loader\ClassLoader;


define(__NAMESPACE__.'\_MINISTRUTS_ROOT', dirName(__DIR__));
const MINISTRUTS_ROOT = _MINISTRUTS_ROOT;                       // local constants improve IDE code completion


// Include helper functions and constants which can't be auto-loaded.
require(MINISTRUTS_ROOT.'/src/helpers.php');
require(MINISTRUTS_ROOT.'/src/db/orm/meta/defines.php');
require(MINISTRUTS_ROOT.'/src/ministruts/defines.php');


/**
 * Register the framework's class loader.
 *
 * - If the framework is used without Composer a loader for the framework classes is required. If Composer is used this
 *   class loader simply has no effect.
 * - The loader is registered after already registered SPL class loaders. To provide backward compatibility to non-SPL
 *   projects an existing __autoload() function is registered first.
 * - The registration is wrapped in a function call to scope isolate its execution.
 */
function registerClassLoader() {
    // check for an existing legacy auto-loader
    $legacyAutoLoad = function_exists('__autoload');
    if ($legacyAutoLoad) {
        $splLoaders = spl_autoload_functions();
        if ($splLoaders) {
            $legacyAutoLoad = (sizeOf($splLoaders)==1 && $splLoaders[0]=='__autoload');
        }
    }

    // create a bootstrap loader for the class rosasurfer\loader\ClassLoader
    $bootstrap = function($class) {
        switch ($class) {
            case Object      ::class: require(MINISTRUTS_ROOT.'/src/core/Object.php'       ); break;
            case ObjectTrait ::class: require(MINISTRUTS_ROOT.'/src/core/ObjectTrait.php'  ); break;
            case DiAwareTrait::class: require(MINISTRUTS_ROOT.'/src/di/DiAwareTrait.php'   ); break;
            case ClassLoader ::class: require(MINISTRUTS_ROOT.'/src/loader/ClassLoader.php'); break;
        }
    };
    spl_autoload_register($bootstrap, $throw=true, $prepend=true);

    // instantiate and register the framework's class loader
    $loader = new ClassLoader();
    $loader->register();
    spl_autoload_unregister($bootstrap);

    // register an otherwise lost legacy auto-loader
    if ($legacyAutoLoad && spl_autoload_functions()[0]!='__autoload') {
        spl_autoload_register('__autoload', $throw=true, $prepend=true);
    }
}
registerClassLoader();


/**
 * Register a SIGINT handler in CLI mode to catch Ctrl-C and execute destructors on shutdown.
 */
if (CLI && function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function($signo, $signinfo = null) {
        exit(0);                                                // calling exit() is sufficient to execute destructors
    });
}
