<?php
namespace rosasurfer\ministruts;

use rosasurfer\core\Singleton;

use rosasurfer\exception\FileNotFoundException;
use rosasurfer\exception\IllegalStateException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\ministruts\Struts;

use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;
use const rosasurfer\L_DEBUG;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
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


   /** @var bool */
   private static $logDebug, $logInfo, $logNotice;

   /**
    * @var Module[] - all registered Modules, array key is the Module prefix
    */
   private $modules = [];


   /**
    * Return the singleton instance of this class. The instance might be loaded from a Cache.
    *
    * @return Singleton
    */
   public static function me() {
      if (CLI) throw new IllegalStateException('Can not use a '.__CLASS__.' in this context.');

      $cache = Cache::me();

      // cache hit?
      $controller = $cache->get(__CLASS__);
      if (!$controller) {
         // TODO: fix wrong lock usage (see TODO file)
         // synchronize parsing of the struts-config.xml
         //$lock = new FileLock($configFile);
         //   $controller = $cache->get(__CLASS__);                    // re-check after the lock is aquired

            if (!$controller) {
               // create new controller instance...
               $controller = Singleton::getInstance(__CLASS__);

               $configFile = str_replace('\\', '/', APPLICATION_ROOT.'/app/config/struts-config.xml');
               $dependency = \FileDependency::create($configFile);
               if (!WINDOWS && !LOCALHOST)                           // distinction dev/production
                  $dependency->setMinValidity(1 * MINUTE);

               // ...and cache it with a FileDependency
               $cache->set(__CLASS__, $controller, \Cache::EXPIRES_NEVER, $dependency);
            }

         //$lock->release();
      }
      return $controller;
   }


   /**
    * Constructor
    *
    * Load and parse the Struts configuration and create the corresponding object hierarchy.
    */
   protected function __construct() {
      $loglevel        = \Logger ::getLogLevel(__CLASS__);
      self::$logDebug  = ($loglevel <= L_DEBUG );
      self::$logInfo   = ($loglevel <= L_INFO  );
      self::$logNotice = ($loglevel <= L_NOTICE);

      // lookup configuration files
      $appDirectory = str_replace('\\', '/', APPLICATION_ROOT);
      if (!is_file($appDirectory.'/app/config/struts-config.xml'))
         throw new FileNotFoundException('Configuration file not found: "struts-config.xml"');

      $files   = glob($appDirectory.'/app/config/struts-config-*.xml', GLOB_ERR);
      $files[] = $appDirectory.'/app/config/struts-config.xml';


      // create and register a Module for each found file
      try {
         foreach ($files as $file) {
            $baseName = baseName($file, '.xml');
            $prefix = (strStartsWith($baseName, 'struts-config-')) ? '/'.subStr($baseName, 14) : '';

            $module = new \Module($file, $prefix);
            $module->freeze();

            if (isSet($this->modules[$prefix]))
               throw new RuntimeException('All modules must have unique module prefixes, non-unique prefix: "'.$prefix.'"');

            $this->modules[$prefix] = $module;
         }
      }
      catch (\Exception $ex) {
         throw new RuntimeException('Error loading '.$file, null, $ex);
      }
   }


   /**
    * Process the current HTTP request.
    */
   public static function processRequest() {
      $controller = self     ::me();
      $request    =  Request ::me();
      $response   = \Response::me();

      // select Module
      $prefix = $controller->getModulePrefix($request);
      $module = $controller->modules[$prefix];
      $request->setAttribute(Struts::MODULE_KEY, $module);

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
   private function getRequestProcessor(\Module $module) {
      $class = $module->getRequestProcessorClass();
      return new $class($module);
   }
}
