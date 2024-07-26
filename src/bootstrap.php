<?php
/**
 * Framework loader.
 *
 * Load framework functions and constants and register the internal class loader.
 */
namespace rosasurfer\ministruts;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;
use rosasurfer\ministruts\core\loader\ClassLoader;


define('rosasurfer\ministruts\_ROOT_', dirname(__DIR__));
const MINISTRUTS_ROOT = _ROOT_;                          // constant declarations improve IDE code completion


// Include helper functions and constants which can't be auto-loaded (prevent multiple includes).
if (!defined('rosasurfer\ministruts\CLI'))               require(MINISTRUTS_ROOT.'/src/helpers.php');
if (!defined('rosasurfer\ministruts\db\orm\meta\BOOL'))  require(MINISTRUTS_ROOT.'/src/db/orm/meta/defines.php');
if (!defined('rosasurfer\ministruts\struts\MODULE_KEY')) require(MINISTRUTS_ROOT.'/src/struts/defines.php');


/**
 * Register the framework's class loader.
 *
 * If the framework is used without Composer a class loader is required. If Composer is used this
 * class loader simply has no effect. The loader registers itself after already registered class loaders.
 */
function registerClassLoader() {
    static $done = false;
    if ($done) return;

    // register a bootstrap loader for class rosasurfer\ministruts\core\loader\ClassLoader
    $bootstrap = function($class) {
        switch ($class) {
            case CObject     ::class: require(MINISTRUTS_ROOT.'/src/core/CObject.php'           ); break;
            case ObjectTrait ::class: require(MINISTRUTS_ROOT.'/src/core/ObjectTrait.php'       ); break;
            case DiAwareTrait::class: require(MINISTRUTS_ROOT.'/src/core/di/DiAwareTrait.php'   ); break;
            case ClassLoader ::class: require(MINISTRUTS_ROOT.'/src/core/loader/ClassLoader.php'); break;
        }
    };
    spl_autoload_register($bootstrap, true, true);

    // instantiate and register the framework's class loader
    (new ClassLoader())->register();
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
