<?php
namespace rosasurfer;

use rosasurfer\core\Object;
use rosasurfer\loader\ClassLoader;


// block re-includes
if (defined(__NAMESPACE__.'\MINISTRUTS_ROOT'))
   return;
define(__NAMESPACE__.'\MINISTRUTS_ROOT', dirName(__DIR__));


/**
 * Include helper functions and constants which can't be auto-loaded.
 */
include(MINISTRUTS_ROOT.'/src/rosasurfer/helpers.php');
include(MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/helpers.php');


/**
 * Register the framework's class loader.
 *
 * If the framework is used in a project not using Composer a class loader for the framework's classes is required.
 * On the other hand if Composer is used this registration has no effect but is done anyway because detecting Composer
 * is not reliable and might fail in the future.
 *
 * The loader is registered after any other registered SPL loaders. To provide backward compatibility an existing
 * __autoload() function is registered first if no other SPL loader is yet registered.
 */
call_user_func(function() {                                                   // encapsulated to protect global scope
   // check the existing legacy auto-loader configuration
   $legacyAutoLoad = function_exists('__autoload');
   if ($legacyAutoLoad) {
      $splLoaders = spl_autoload_functions();
      if ($splLoaders) {
         $legacyAutoLoad = (sizeOf($splLoaders)==1 && $splLoaders[0]=='__autoload');
      }
   }

   // create a bootstrap loader for the class ClassLoader
   $callable = function($class) {
      switch ($class) {
         case Object::class:      require(__DIR__.'/rosasurfer/core/Object.php'       ); break;
         case ClassLoader::class: require(__DIR__.'/rosasurfer/loader/ClassLoader.php'); break;
      }
   };

   // instantiate and register the framwork's class loader
   spl_autoload_register($callable, $throw=true, $prepend=true);
   (new ClassLoader())->register();
   spl_autoload_unregister($callable);

   // register an otherwise lost legay auto-loader
   if ($legacyAutoLoad && spl_autoload_functions()[0]!='__autoload') {
      spl_autoload_register('__autoload', $throw=true, $prepend=true);
   }
});
