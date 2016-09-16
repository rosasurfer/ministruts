<?php
namespace rosasurfer;

use rosasurfer\exception\ClassNotFoundException;


// block re-includes
if (defined('rosasurfer\MINISTRUTS_ROOT')) return;
define('rosasurfer\MINISTRUTS_ROOT', dirName(__DIR__));


/**
 * Load the Ministruts framework.
 *
 * Program flow:
 * (1) define helper constants
 * (2) include required non-class files
 *
 * (3) register class loader
 * (4) setup error handling
 *
 * (6) check/adjust application requirements
 * (5) check/adjust PHP requirements
 * (7) execute phpinfo() if applicable
 */


/**
 * (1) define namespaced helper constants
 */
define('rosasurfer\CLI'      , !isSet($_SERVER['REQUEST_METHOD']));                 // whether or not we run on a command line interface
define('rosasurfer\LOCALHOST', !CLI && @$_SERVER['REMOTE_ADDR']=='127.0.0.1');      // whether or not we run on localhost
define('rosasurfer\WINDOWS'  , (strToUpper(subStr(PHP_OS, 0, 3))=='WIN'));          // whether or not we run on Windows

// custom log level
define('rosasurfer\L_DEBUG' ,  1);
define('rosasurfer\L_INFO'  ,  2);
define('rosasurfer\L_NOTICE',  4);
define('rosasurfer\L_WARN'  ,  8);
define('rosasurfer\L_ERROR' , 16);
define('rosasurfer\L_FATAL' , 32);

// log destinations for the built-in function error_log()
define('rosasurfer\ERROR_LOG_DEFAULT', 0);                                          // message is sent to the configured log or the system logger
define('rosasurfer\ERROR_LOG_MAIL'   , 1);                                          // message is sent by email
define('rosasurfer\ERROR_LOG_DEBUG'  , 2);                                          // message is sent through the PHP debugging connection
define('rosasurfer\ERROR_LOG_FILE'   , 3);                                          // message is appended to a file destination
define('rosasurfer\ERROR_LOG_SAPI'   , 4);                                          // message is sent directly to the SAPI logging handler

// time periods
define('rosasurfer\SECOND',   1          ); define('rosasurfer\SECONDS', SECOND);
define('rosasurfer\MINUTE',  60 * SECONDS); define('rosasurfer\MINUTES', MINUTE);
define('rosasurfer\HOUR'  ,  60 * MINUTES); define('rosasurfer\HOURS'  , HOUR  );
define('rosasurfer\DAY'   ,  24 * HOURS  ); define('rosasurfer\DAYS'   , DAY   );
define('rosasurfer\WEEK'  ,   7 * DAYS   ); define('rosasurfer\WEEKS'  , WEEK  );
define('rosasurfer\MONTH' ,  31 * DAYS   ); define('rosasurfer\MONTHS' , MONTH );   // fuzzy but garantied to cover any month
define('rosasurfer\YEAR'  , 366 * DAYS   ); define('rosasurfer\YEARS'  , YEAR  );   // fuzzy but garantied to cover any year

// weekdays
define('rosasurfer\SUNDAY'   , 0);
define('rosasurfer\MONDAY'   , 1);
define('rosasurfer\TUESDAY'  , 2);
define('rosasurfer\WEDNESDAY', 3);
define('rosasurfer\THURSDAY' , 4);
define('rosasurfer\FRIDAY'   , 5);
define('rosasurfer\SATURDAY' , 6);

// miscellaneous
define('rosasurfer\EOL', PHP_EOL);
define('rosasurfer\NL' , "\n"   );
!defined('PHP_INT_MIN') && define('PHP_INT_MIN', ~PHP_INT_MAX);                     // global definition (built-in since PHP 7.0)


/**
 * (2) include always required non-class files (utility constants and functions)
 */
include(MINISTRUTS_ROOT.'/src/rosasurfer/ns_definitions.php');
include(MINISTRUTS_ROOT.'/src/rosasurfer/ministruts/ns_definitions.php');


/**
 * (3) register case-insensitive class loader
 *
 * @param  string $class
 */
spl_autoload_register(function($class) {
   // load and initialize class map
   static $classMap = null;
   !$classMap && $classMap=array_change_key_case(include(MINISTRUTS_ROOT.'/src/rosasurfer/loader/classmap.php'), CASE_LOWER);

   $classToLower = strToLower($class);
   try {
      // load file
      if (isSet($classMap[$classToLower]))
         include($classMap[$classToLower].'.php');
   }
   catch (\Exception $ex) {
      if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
         \Logger::warn(ucFirst(metaTypeToStr($class)).' '.$class.' was successfully auto-loaded but file caused an exception', $ex, __CLASS__);
      }
      else throw ($ex instanceof ClassNotFoundException) ? $ex : new ClassNotFoundException('Cannot auto-load '.$class, null, $ex);
   }
});


/**
 * (4) setup error handling
 */
\System::setupErrorHandling();


/**
 * (5) check/adjust application requirements
 */
!defined('\APPLICATION_ROOT') && exit(1|echoPre('application error')|error_log('Error: The global constant APPLICATION_ROOT must be defined.'));
!defined('\APPLICATION_ID'  ) && define('APPLICATION_ID', md5(\APPLICATION_ROOT));


/**
 * (6) check/adjust PHP requirements
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
 * (7) execute phpInfo() if magic parameter specified (localhost only)
 */
if (LOCALHOST && (strEndsWith(strLeftTo($_SERVER['REQUEST_URI'], '?'), '/=phpinfo') || strEndsWith(strLeftTo($_SERVER['REQUEST_URI'], '?'), '/=phpinfo.php'))) {
   include(MINISTRUTS_ROOT.'/src/rosasurfer/util/phpinfo.php');
   exit(0);
}
