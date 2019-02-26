<?php
/**
 * Framework loader.
 *
 * Load framework functions and constants and register the internal class loader.
 */
namespace rosasurfer;

use rosasurfer\core\Object;
use rosasurfer\core\ObjectTrait;
use rosasurfer\core\loader\ClassLoader;
use rosasurfer\di\DiAwareTrait;


define('rosasurfer\_MINISTRUTS_ROOT', dirname(__DIR__));
const MINISTRUTS_ROOT = _MINISTRUTS_ROOT;                       // local constants improve IDE code completion


// Include helper functions and constants which can't be auto-loaded (prevent multiple includes).
if (!defined('rosasurfer\CLI'))                   require(MINISTRUTS_ROOT.'/src/helpers.php');
if (!defined('rosasurfer\db\orm\meta\BOOL'))      require(MINISTRUTS_ROOT.'/src/db/orm/meta/defines.php');
if (!defined('rosasurfer\ministruts\MODULE_KEY')) require(MINISTRUTS_ROOT.'/src/ministruts/defines.php');


/**
 * Register the framework's class loader.
 *
 * If the framework is used without Composer a loader for the framework classes is required. If Composer is used this
 * class loader simply has no effect. The loader registers itself after already registered other SPL class loaders.
 */
function registerClassLoader() {
    static $done = false;
    if ($done) return;

    // register a bootstrap loader for class rosasurfer\core\loader\ClassLoader
    $bootstrap = function($class) {
        switch ($class) {
            case Object      ::class: require(MINISTRUTS_ROOT.'/src/core/Object.php'            ); break;
            case ObjectTrait ::class: require(MINISTRUTS_ROOT.'/src/core/ObjectTrait.php'       ); break;
            case ClassLoader ::class: require(MINISTRUTS_ROOT.'/src/core/loader/ClassLoader.php'); break;
            case DiAwareTrait::class: require(MINISTRUTS_ROOT.'/src/di/DiAwareTrait.php'        ); break;
        }
    };
    spl_autoload_register($bootstrap, $throw=true, $prepend=true);

    // instantiate and register the framework's class loader
    $loader = new ClassLoader();
    $loader->register();
    spl_autoload_unregister($bootstrap);

    $done = true;
}
registerClassLoader();


/**
 * In CLI mode register a SIGINT handler to catch Ctrl-C and execute destructors on shutdown.
 */
if (CLI && function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function($signo, $signinfo = null) {
        exit(1);                                                // calling exit() is sufficient to execute destructors
    });
}
