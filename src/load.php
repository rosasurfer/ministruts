<?php
namespace rosasurfer;

use rosasurfer\core\Object;
use rosasurfer\loader\ClassLoader;


// Block re-includes.
if (defined('rosasurfer\MINISTRUTS_ROOT'))
   return;
define('rosasurfer\MINISTRUTS_ROOT', dirName(__DIR__));


// Include helper functions and constants which can't be auto-loaded.
include(MINISTRUTS_ROOT.'/src/rosasurfer/helpers.php');
include(MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/helpers.php');


// Register the framework's class loader.
registerClassLoader();


/**
 * Register the framework's class loader.
 *
 * If the framework is used in a project not using Composer a class loader for the framework's classes is required.
 * On the other hand if Composer is used this registration has no effect but is done anyway because detecting Composer
 * is not reliable and might fail in the future.
 *
 * The loader is registered after any other registered SPL loaders. To provide backward compatibility an existing
 * __autoload() function is registered first if no other SPL loader is yet registered.
 *
 * Everything is wrapped in a function to prevent modifications of the global scope.
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
         case Object::class:      require(__DIR__.'/rosasurfer/core/Object.php'       ); break;
         case ClassLoader::class: require(__DIR__.'/rosasurfer/loader/ClassLoader.php'); break;
      }
   };
   spl_autoload_register($bootstrap, $throw=true, $prepend=true);

   // instantiate and register the framework's class loader
   $loader = new ClassLoader();
   $loader->register();
   spl_autoload_unregister($bootstrap);

   // register and save an otherwise lost legacy auto-loader
   if ($legacyAutoLoad && spl_autoload_functions()[0]!='__autoload') {
      spl_autoload_register('__autoload', $throw=true, $prepend=true);
   }
}
