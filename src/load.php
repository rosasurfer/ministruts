<?php
namespace rosasurfer;

use rosasurfer\debug\ErrorHandler;
use rosasurfer\exception\ClassNotFoundException;
use rosasurfer\log\Logger;


/**
 * Load the Ministruts framework.
 *
 * Program flow:
 * (1) include helper functions and constants
 *
 * (2) register class loader
 * (3) setup error handling
 *
 * (4) check/adjust application requirements
 * (5) check/adjust PHP requirements
 * (6) execute phpinfo() if applicable (on localhost only)
 */

// block re-includes
if (defined(__NAMESPACE__.'\MINISTRUTS_ROOT')) return;
define(__NAMESPACE__.'\MINISTRUTS_ROOT', dirName(__DIR__));


/**
 * (1) include helper functions and constants
 */
include(MINISTRUTS_ROOT.'/src/rosasurfer/helpers.php');
include(MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/helpers.php');


/**
 * (2) register case-insensitive class loader
 *
 * @param  string $class
 */
spl_autoload_register(function($class) {
   // load and initialize class map
   static $classMap = null;
   !$classMap && $classMap=array_change_key_case(include(MINISTRUTS_ROOT.'/etc/vendor/composer/autoload_classmap.php'), CASE_LOWER);

   $classToLower = strToLower($class);
   try {
      // load file
      if (isSet($classMap[$classToLower]))
         include($classMap[$classToLower]);
   }
   catch (\Exception $ex) {
      if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
         Logger::log(ucFirst(metaTypeOf($class)).' '.$class.' was successfully auto-loaded but file caused an exception', L_WARN, ['exception'=>$ex]);
      }
      elseif ($ex instanceof ClassNotFoundException) {
         throw $ex;
      }
      else {
         throw new ClassNotFoundException('Cannot auto-load '.$class, null, $ex);
      }
   }
});


/**
 * (3) setup error handling
 */
ErrorHandler::setupErrorHandling();


/**
 * (4) check/adjust application requirements
 */
!defined('\APPLICATION_ROOT') && exit(1|echoPre('application error')|error_log('Error: The global constant APPLICATION_ROOT must be defined.'));
!defined('\APPLICATION_ID'  ) && define('APPLICATION_ID', md5(\APPLICATION_ROOT));


/**
 * (5) check/adjust PHP requirements
 */
!ini_get('short_open_tag')       && exit(1|echoPre('application error')|error_log('Error: The PHP configuration value "short_open_tag" must be enabled.'));
ini_get('request_order') != 'GP' && exit(1|echoPre('application error')|error_log('Error: The PHP configuration value "request_order" must be "GP".'));

ini_set('arg_separator.output'    , '&amp;'                );
ini_set('auto_detect_line_endings',  1                     );
ini_set('default_mimetype'        , 'text/html'            );
ini_set('default_charset'         , 'UTF-8'                );
ini_set('ignore_repeated_errors'  ,  0                     );
ini_set('ignore_repeated_source'  ,  0                     );
ini_set('ignore_user_abort'       ,  1                     );
ini_set('display_errors'          , (int)(CLI || LOCALHOST));
ini_set('display_startup_errors'  , (int)(CLI || LOCALHOST));
ini_set('log_errors'              ,  1                     );
ini_set('log_errors_max_len'      ,  0                     );
ini_set('track_errors'            ,  1                     );
ini_set('html_errors'             ,  0                     );
ini_set('session.use_cookies'     ,  1                     );
ini_set('session.use_trans_sid'   ,  0                     );
ini_set('session.cookie_httponly' ,  1                     );
ini_set('session.referer_check'   , ''                     );
ini_set('zend.detect_unicode'     ,  1                     );     // BOM header recognition


/**
 * (6) execute phpInfo() if URI ends with magic path (localhost only)
 */
if (LOCALHOST && strEndsWithI(strLeftTo($_SERVER['REQUEST_URI'], '?'), '/__phpinfo__')) {
   include(MINISTRUTS_ROOT.'/src/rosasurfer/debug/phpinfo.php');
   exit(0);
}
