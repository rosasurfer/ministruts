<?php
use rosasurfer\ministruts\exceptions\PHPError;

use const rosasurfer\CLI;
use const rosasurfer\L_WARN;
use const rosasurfer\NL;


/**
 * Framework system class: general system-wide functionality
 */
class System extends StaticClass {


   /** @var callable */
   private static $errorHandler;                   // the registered error handler

   /** @var callable */
   private static $exceptionHandler;               // the registered exception handler

   /** @var bool */
   private static $inShutdown;                     // whether or not the script is in shutdown phase


   /**
    * Whether or not the script is currently in shutdown phase.
    *
    * @return bool
    */
   public static function isInShutdown() {
      return (bool) self::$inShutdown;
   }


   /**
    * Setup global error and exception handling.
    */
   public static function setupErrorHandling() {
      $errorLevel = E_ALL;                         // default if unspecified
      $errorLevel = error_reporting();             // TODO: remove later, it blocks runtime error level changes
      set_error_handler(self::$errorHandler=__CLASS__.'::handleError', $errorLevel);

      set_exception_handler(self::$exceptionHandler=function(\Exception $ex) {
         self::handleException($ex);
      });

      /**
       * Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
       *
       * @see  http://php.net/manual/en/language.oop5.decon.php
       */
      register_shutdown_function(function() {      // Detect entering of the shutdown phase. Should be the very first
         self::$inShutdown = true;                 // function on the shutdown function stack.
      });
   }


   /**
    * Global handler for traditional PHP errors.
    *
    * Errors are handled only if covered by the currently configured error reporting level. Errors of the levels
    * E_DEPRECATED, E_USER_DEPRECATED, E_USER_NOTICE and E_USER_WARNING are logged and script execution continuous.
    * All other errors are wrapped in an PHPError and thrown back.
    *
    * @param  int     $level   - PHP error severity level
    * @param  string  $message - error message
    * @param  string  $file    - name of file where the error occurred
    * @param  int     $line    - line of file where the error occurred
    * @param  mixed[] $context - symbol table of the point where the error occurred (current variable scope at error trigger time)
    *
    * @return bool - TRUE,  if the error was successfully handled
    *                FALSE, if the error shall be processed as if no error handler was installed
    *
    * NOTE: The error handler must return FALSE to populate the internal PHP variable $php_errormsg.
    */
   public static function handleError($level, $message, $file, $line, array $context) {
      // TODO: detect and handle recursive calls

      //echoPre(__METHOD__.'(1)  '.DebugTools::errorLevelToStr($level).': $message='.$message.', $file='.$file.', $line='.$line);

      // ignore suppressed errors and errors not covered by the current reporting level
      $reportingLevel = error_reporting();
      if (!$reportingLevel)            return false;     // 0: the @ operator was specified
      if (!($reportingLevel & $level)) return true;      // error not covered by current reporting level

      // wrap error in an \ErrorException
      $exception = new PHPError($message, $code=null, $severity=$level, $file, $line);

      // log non-critical errors and continue
      /*
      if ($level == E_DEPRECATED     ) { Logger::log(null, $exception, L_NOTICE); return true; }
      if ($level == E_USER_DEPRECATED) { Logger::log(null, $exception, L_NOTICE); return true; }
      if ($level == E_USER_NOTICE    ) { Logger::log(null, $exception, L_NOTICE); return true; }
      if ($level == E_USER_WARNING   ) { Logger::log(null, $exception, L_WARN  ); return true; }
      */


      /**
       * Handle cases where throwing an exception is not possible or not allowed.
       */

      /**
       * (1) Errors triggered by require() or require_once()
       *
       *     PHP errors triggered by require() or require_once() are non-catchable errors and do not follow regular
       *     application flow. PHP terminates the script after leaving the error handler, thrown exceptions are ignored.
       *     In fact this termination is intended behaviour and the main difference to include() and include_once().
       *
       *     @see  http://stackoverflow.com/questions/25584494/php-set-exception-handler-not-working-for-error-thrown-in-set-error-handler-cal
       *
       *     Solution: manually call the exception handler
       */
      $function = DebugTools::getFQFunctionName($exception->getBetterTrace()[0]);
      if ($function=='require' || $function=='require_once') {
         self::$exceptionHandler->__invoke($exception);           // that's Closure::__invoke()
         return true;
      }

      // throw back everything else
      throw $exception;
   }


   /**
    * Handler for exceptions occurring in object destructors. This method is only called manually. If the script currently
    * is in the shutdown phase the exception is passed on to the global exception handler (whereafter script execution
    * always terminates). If the script is currently not in shutdown phase the exception is ignored.
    *
    * @param   Exception $exception
    *
    * @see     http://php.net/manual/en/language.oop5.decon.php
    *          Attempting to throw an exception from a destructor during script shutdown causes a fatal error.
    *
    * @example sample destructor:
    *
    * <pre>
    * /**
    *  * Destructor
    *  *\/
    * public function __destruct() {
    *    try {
    *       //...some work that might trigger an exception
    *    }
    *    catch (\Exception $ex) {
    *       System::handleDestructorException($ex);
    *       throw $ex;
    *    }
    * }
    * </pre>
    */
   public static function handleDestructorException(\Exception $exception) {
      if (self::isInShutdown()) {
         self::handleException($exception);
         exit(1);                            // exit und signal the error
      }
   }


   /**
    * Global handler for otherwise unhandled exceptions. The exception is logged with level L_FATAL and script execution
    * is terminated.
    *
    * @param  Exception $exception - the unhandled exception
    */
   private static function handleException(\Exception $exception) {
      // TODO: detect and handle recursive calls

      // !!! old legacy version !!!
      Logger::handleException($exception);
      return;


      // collect data
      $type       = $exception instanceof \ErrorException ? 'Unexpected':'Unhandled';
      $exMessage  = trim(DebugTools::getBetterMessage($exception));
      $indent     = ' ';
      $traceStr   = $indent.'Stacktrace:'.NL.' -----------'.NL;
      $traceStr  .= DebugTools::getBetterTraceAsString($exception, $indent);
      $file       = $exception->getFile();
      $line       = $exception->getLine();
      $cliMessage = '[FATAL] '.$type.' '.$exMessage.NL.$indent.'in '.$file.' on line '.$line.NL;

      // display it
      if (CLI) {
         echoPre($cliMessage.NL.$traceStr.NL);
      }
      else {
         echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>[FATAL]</b> '.$type.' '.nl2br(htmlSpecialChars($exMessage, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
         echo '<br>'.printPretty($traceStr, true);
         echo '<br></div>'.NL;
      }
      exit(1);                               // exit und signal the error
   }
}
