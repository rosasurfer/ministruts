<?php
namespace rosasurfer;

use rosasurfer\core\StaticClass;
use rosasurfer\debug\ErrorHandler;


/**
 * Framework initialization
 */
class MiniStruts extends StaticClass {


   /** @var int - error handling mode in which regular PHP errors are logged */
   const LOG_ERRORS = ErrorHandler::LOG_ERRORS;

   /** @var int - error handling mode in which regular PHP errors are converted to exceptions and thrown back */
   const THROW_ERRORS = ErrorHandler::THROW_ERRORS;


   /**
    * Initialize the framework. This method expects an array with any of the following options:
    *
    * "config"            - String: Full path to a custom application configuration file to use. The default is to use
    *                       the configuration found in "APPLICATION_ROOT/app/config/config.properties". If the application
    *                       structure follows its own standards use this setting to provide a custom configuration file.
    *
    * "global-helpers"    - Boolean: If this option is set to TRUE, the helper functions and constants defined in
    *                       namespace "rosasurfer\" are additionally mapped to the global namespace.
    *                       see {@link ./global-helpers.php}
    *                       Default: FALSE (no global helpers)
    *
    * "handle-errors"     - Integer: Flag specifying how to handle regular PHP errors. Possible values:
    *                       LOG_ERRORS:   PHP errors are logged by the built-in default logger.<br>
    *                                     see {@link \rosasurfer\log\Logger}
    *
    *                       THROW_ERRORS: PHP errors are converted to PHP ErrorExceptions and thrown back. If this
    *                                     option is used it is required to either configure the framework's exception
    *                                     handler or to register your own exception handling mechanism. Without an
    *                                     exception handler PHP will terminate a script with a FATAL error after
    *                                     such an exception.
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
            case 'config'           : self::useConfiguration ($value); continue;    // todo
            case 'global-helpers'   : self::loadGlobalHelpers($value); continue;
            case 'handle-errors'    : self::handleErrors     ($value); continue;
            case 'handle-exceptions': self::handleExceptions ($value); continue;
            case 'replace-composer' : self::replaceComposer  ($value); continue;    // todo
         }
      }


      // (1) check/adjust application requirements
      !defined('\APPLICATION_ROOT') && exit(1|echoPre('application error')|error_log('Error: The global constant APPLICATION_ROOT must be defined.'));
      !defined('\APPLICATION_ID'  ) && define('APPLICATION_ID', md5(\APPLICATION_ROOT));


      // (2) check/adjust PHP requirements
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


      // (3) execute phpinfo() if magic parameter "__phpinfo__" is set (localhost only)
      if (LOCALHOST && isSet($_REQUEST['__phpinfo__'])) {
         include(MINISTRUTS_ROOT.'/src/rosasurfer/debug/phpinfo.php');
         exit(0);
      }
   }


   /**
    * Use the specified file as the application's main configuration file.
    *
    * @param  mixed $value - configuration value as passed to the framework loader
    */
   private static function useConfiguration($value) {
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
         if     ($value == self::LOG_ERRORS  ) $flag = self::LOG_ERRORS;
         elseif ($value == self::THROW_ERRORS) $flag = self::THROW_ERRORS;
      }
      elseif (is_string($value)) {
         $value = trim(strToUpper($value));
         if     ($value=='LOG_ERRORS'   || $value=='LOG'  ) $flag = self::LOG_ERRORS;
         elseif ($value=='THROW_ERRORS' || $value=='THROW') $flag = self::THROW_ERRORS;
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
include(__DIR__.'/../load.php');
