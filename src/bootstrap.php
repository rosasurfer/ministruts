<?php
declare(strict_types=1);

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


define('rosasurfer\ministruts\ROOT_DIR', dirname(__DIR__));


// Include helper functions and constants which can't be auto-loaded (prevent multiple includes).
if (!defined('rosasurfer\ministruts\CLI'))               require(__DIR__.'/helpers.php');
if (!defined('rosasurfer\ministruts\db\orm\meta\BOOL'))  require(__DIR__.'/db/orm/meta/defines.php');
if (!defined('rosasurfer\ministruts\struts\MODULE_KEY')) require(__DIR__.'/struts/defines.php');


/**
 * Register the framework's class loader.
 *
 * If the framework is used without Composer a class loader is required. If Composer is used this
 * class loader simply has no effect. The loader registers itself after already registered class loaders.
 *
 * @return void
 */
function registerClassLoader() {
    static $done = false;
    if ($done) return;

    // register a bootstrap loader for class rosasurfer\ministruts\core\loader\ClassLoader
    $bootstrap = function($class) {
        switch ($class) {
            case CObject     ::class: require(__DIR__.'/core/CObject.php'           ); break;
            case ObjectTrait ::class: require(__DIR__.'/core/ObjectTrait.php'       ); break;
            case DiAwareTrait::class: require(__DIR__.'/core/di/DiAwareTrait.php'   ); break;
            case ClassLoader ::class: require(__DIR__.'/core/loader/ClassLoader.php'); break;
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
