<?php
namespace rosasurfer;

use rosasurfer\config\Config;
use rosasurfer\core\StaticClass;
use rosasurfer\debug\ErrorHandler;
use rosasurfer\util\PHP;


/**
 * Framework initialization
 */
class MiniStruts extends StaticClass {


   /** @var int - error handling mode in which regular PHP errors are logged */
   const LOG_ERRORS       = ErrorHandler::LOG_ERRORS;

   /** @var int - error handling mode in which regular PHP errors are converted to exceptions and thrown back */
   const THROW_EXCEPTIONS = ErrorHandler::THROW_EXCEPTIONS;

   /** @var string - the applications main config directory */
   private static $configDir;


   /**
    * Initialize the framework. This method expects an array with any of the following options:
    *
    * "config-dir"        - String: Path of a custom application config directory. If the application structure follows
    *                       its own standards this setting can be used to read config files from a non-standard directory.
    *                       Default: Read config files from the directory "APPLICATION_ROOT/app/config/".
    *
    * "global-helpers"    - Boolean: If this option is set to TRUE, the helper functions and constants defined in
    *                       namespace "rosasurfer\" are additionally mapped to the global namespace.
    *                       see {@link ./global-helpers.php}
    *                       Default: FALSE (no global helpers)
    *
    * "handle-errors"     - Integer: Flag specifying how to handle regular PHP errors. Possible values:
    *
    *                       LOG_ERRORS: PHP errors are logged by the built-in default logger.<br>
    *                                   see {@link \rosasurfer\log\Logger}
    *
    *                       THROW_EXCEPTIONS: PHP errors are converted to PHP ErrorExceptions and thrown back. If this
    *                                   option is used it is required to either configure the framework's exception
    *                                   handler or to register your own exception handling mechanism. Without an
    *                                   exception handler PHP will terminate a script with a FATAL error after such an
    *                                   exception.
    *                       Default: NULL (no error handling)
    *
    * "handle-exceptions" - Boolean: If this option is set to TRUE, the framework will send otherwise unhandled exceptions
    *                       to the built-in default logger before PHP will terminate the script.<br>
    *                       see {@link \rosasurfer\log\Logger}
    *
    *                       Enabling this option is required if the option "handle-errors" is set to ERROR_HANDLER_THROW
    *                       and you don't provide your own exception handling mechanism.
    *                       Default: FALSE (no exception handling)
    *
    * "replace-composer"  - Boolean: If this option is set to TRUE, the framework replaces an existing Composer class
    *                       loader (non-standard compliant) with it's own standard compliant version. Use this option if
    *                       the case-sensitivity of Composer's class loader causes errors.
    *                       Default: FALSE
    *
    * @param  array $options
    */
   public static function init(array $options = []) {
      foreach ($options as $key => $value) {
         switch ($key) {
            case 'config-dir'       : self::setConfigDir     ($value); continue;
            case 'global-helpers'   : self::loadGlobalHelpers($value); continue;
            case 'handle-errors'    : self::handleErrors     ($value); continue;
            case 'handle-exceptions': self::handleExceptions ($value); continue;
            case 'replace-composer' : self::replaceComposer  ($value); continue;    // TODO
         }
      }


      // (1) check application settings                              // TODO: remove APPLICATION_ROOT dependency
      !defined('\APPLICATION_ROOT') && exit(1|echoPre('application error (see error log)')|error_log('Error: The global constant APPLICATION_ROOT must be defined.'));
      !defined('\APPLICATION_ID'  ) && define('APPLICATION_ID', md5(\APPLICATION_ROOT));


      // (2) check for and execute magic task if on localhost
      $executedTasks = $cacheTask = false;
      if (!CLI && (LOCALHOST || $_SERVER['REMOTE_ADDR']==$_SERVER['SERVER_ADDR'])) {
         foreach ($_REQUEST as $param => $value) {
            if ($param == '__phpinfo__') {
               PHP::phpInfo();
               $executedTasks = true;
            }
            else if ($param == '__config__') {
               //Config::getDefault()->show();                       // TODO: not yet implemented
               $executedTasks = true;
               self::updatePhpConfig();
            }
            else if ($param == '__cache__') {
               if (!$executedTasks) {                                // ignore if there are already other tasks
                  $cacheTask = true;                                 // delayed after PHP's configuration update (if any)
                  break;
               }
            }
         }
         $executedTasks && exit(0);
      }


      // (3) update PHP configuration
      self::updatePhpConfig();


      // (4) execute opcode cache task (if enabled)
      if ($cacheTask) {
         //include(MINISTRUTS_ROOT.'/src/rosasurfer/debug/apc.php'); // TODO: not yet implemented
         exit(0);
      }


      // (5) enforce PHP requirements
      !PHP::ini_get_bool('short_open_tag') && exit(1|echoPre('application error (see error log)')|error_log('Error: The PHP configuration value "short_open_tag" must be enabled (security).'));
      ini_get('request_order') != 'GP'     && exit(1|echoPre('application error (see error log)')|error_log('Error: The PHP configuration value "request_order" must be "GP".'));
   }


   /**
    * Update the PHP configuration with user defined settings.
    */
   private static function updatePhpConfig() {
      /*
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
      */
   }


   /**
    * Get the application's main config directory.
    *
    * @return string
    */
   public static function getConfigDir() {
      if (!self::$configDir) {
         self::$configDir = realPath(APPLICATION_ROOT.'/app/config');
      }
      return self::$configDir;
   }


   /**
    * Set the specified directory as the application's main config directory.
    *
    * @param  mixed $value - configuration value as passed to the framework loader
    */
   private static function setConfigDir($value) {
      if (is_string($value)) {
         if (is_dir($value)) {
            self::$configDir = realPath($value);
         }
      }
   }


   /**
    * Map the helper constants and functions in "rosasurfer/helpers.php" to the global namespace.
    *
    * @param  mixed $value - configuration value as passed to the framework loader
    */
   private static function loadGlobalHelpers($value) {
      $enabled = false;
      if (is_bool($value) || is_int($value)) {
         $enabled = (bool) $value;
      }
      elseif (is_string($value)) {
         $value   = trim(strToLower($value));
         $enabled = ($value=='1' || $value=='on' || $value=='true');
      }

      if ($enabled) {
         include(MINISTRUTS_ROOT.'/src/rosasurfer/global-helpers.php');
      }
   }


   /**
    * Setup the application's error handling.
    *
    * @param  mixed $value - configuration value as passed to the framework loader
    */
   private static function handleErrors($value) {
      $flag = null;
      if (is_int($value)) {
         if     ($value == self::LOG_ERRORS      ) $flag = self::LOG_ERRORS;
         elseif ($value == self::THROW_EXCEPTIONS) $flag = self::THROW_EXCEPTIONS;
      }
      elseif (is_string($value)) {
         $value = trim(strToUpper($value));
         if     ($value=='LOG_ERRORS'       || $value=='LOG'  ) $flag = self::LOG_ERRORS;
         elseif ($value=='THROW_EXCEPTIONS' || $value=='THROW') $flag = self::THROW_EXCEPTIONS;
      }

      if ($flag) {
         ErrorHandler::setupErrorHandling($flag);
      }
   }


   /**
    * Setup the application's exception handling.
    *
    * @param  mixed $value - configuration value as passed to the framework loader
    */
   private static function handleExceptions($value) {
      $enabled = false;
      if (is_bool($value) || is_int($value)) {
         $enabled = (bool) $value;
      }
      elseif (is_string($value)) {
         $value   = trim(strToLower($value));
         $enabled = ($value=='1' || $value=='on' || $value=='true');
      }

      if ($enabled) {
         ErrorHandler::setupExceptionHandling();
      }
   }


   /**
    * Replace an existing Composer class loader.
    *
    * @param  mixed $value - configuration value as passed to the framework loader
    */
   private static function replaceComposer($value) {
      $enabled = false;
      if (is_bool($value) || is_int($value)) {
         $enabled = (bool) $value;
      }
      elseif (is_string($value)) {
         $value   = trim(strToLower($value));
         $enabled = ($value=='1' || $value=='on' || $value=='true');
      }

      if ($enabled) {
         // replace Composer
      }
   }
}


// make sure the framework is loaded (e.g. if only this class is loaded by Composer)
if (!defined(__NAMESPACE__.'\MINISTRUTS_ROOT')) {
   include(__DIR__.'/../load.php');
}
