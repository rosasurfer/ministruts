<?php
use rosasurfer\ministruts\exceptions\BaseException as RosasurferException;
use rosasurfer\ministruts\exceptions\PHPError;

use const rosasurfer\ministruts\CLI as CLI;


/**
 * Framework system class: general system-wide functionality
 */
class System extends StaticClass {


   /** @var callable */
   private static $errorHandler;                   // the registered error handler

   /** @var callable */
   private static $exceptionHandler;               // the registered exception handler


   /**
    * Setup global error and exception handling.
    */
   public static function setupErrorHandling() {
      $errorLevel = E_ALL;                         // default if unspecified
      $errorLevel = error_reporting();             // TODO: remove later, it blocks runtime error level changes
      set_error_handler(self::$errorHandler=__CLASS__.'::handleError', $errorLevel);

      set_exception_handler(self::$exceptionHandler=function(\Exception $ex) {
         self::handleException($ex);
         exit(1);                                  // exit and signal the error
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
      //echoPre(__METHOD__.'()  error_reporting='.error_reporting().'  '.errorLevelToStr($level).': $message='.$message.', $file='.$file.', $line='.$line);

      // TODO: detect and handle recursive calls

      // ignore suppressed errors and errors not covered by the current reporting level
      $reportingLevel = error_reporting();
      if (!$reportingLevel)            return false;     // 0: the @ operator was specified
      if (!($reportingLevel & $level)) return true;      // error not covered by current reporting level


      /**
       * !!! old legacy version !!!
       */
      // Error kapseln...
      $exception = new PHPError($message, $file, $line, $context);
      // ...und behandeln
      if     ($level == E_USER_NOTICE ) Logger::log_1(null, $exception, L_NOTICE);
      elseif ($level == E_USER_WARNING) Logger::log_1(null, $exception, L_WARN  );
      else {
         // Spezialfälle, die nicht zurückgeworfen werden dürfen/können
         if ($level==E_STRICT || ($file=='Unknown' && $line==0)) {
            self::handleException($exception);
            exit(1);
         }
         // alles andere zurückwerfen
         throw $exception;
      }
      return true;





      /**
       * !!! new version !!!
       */

      // wrap error in an exception
      $exception = new PHPError($message, $code=null, $severity=$level, $file, $line);

      // log non-critical errors and continue
      /*
      if ($level == E_DEPRECATED     ) { Logger::log(null, $exception, L_INFO  ); return true; }
      if ($level == E_USER_DEPRECATED) { Logger::log(null, $exception, L_INFO  ); return true; }
      if ($level == E_USER_NOTICE    ) { Logger::log(null, $exception, L_NOTICE); return true; }
      if ($level == E_USER_WARNING   ) { Logger::log(null, $exception, L_WARN  ); return true; }
      */


      // Handle cases where throwing an exception is not possible or not allowed.

      /**
       * (1) Errors triggered by require() or require_once()
       *
       *     PHP errors triggered by require() or require_once() are non-catchable errors and do not follow regular
       *     application flow. PHP terminates the script after leaving the error handler, thrown exceptions are ignored.
       *     In fact this termination is intended behaviour and the main difference to include() and include_once().
       *
       *     @see  http://stackoverflow.com/questions/25584494/php-set-exception-handler-not-working-for-error-thrown-in-set-error-handler-cal
       *
       *     Workaround: manually call the exception handler
       */
      $function = Debug::getFQFunctionName($exception->getBetterTrace()[0]);
      if ($function=='require' || $function=='require_once') {
         self::$exceptionHandler->__invoke($exception);           // that's Closure::__invoke()
         return true;
      }

      /*
      if ($level==E_STRICT || ($file=='Unknown' && $line==0)) {
         self::$exceptionHandler->__invoke($exception);
         return true;
      }
      */

      // throw back everything else
      throw $exception;
   }


   /**
    * Global handler for otherwise unhandled exceptions. Exceptions are logged with level L_FATAL and the script is
    * terminated.
    *
    * @param  \Exception $exception - the unhandled exception
    */
   public static function handleException(\Exception $exception) {
      /**
       * !!! old legacy version !!!
       */
      Logger::handleException($exception);
      return;



      // TODO: detect and handle recursive calls

      // collect data
      $type       = $exception instanceof \ErrorException ? 'Unexpected':'Unhandled';
      $exMessage  = trim(RosasurferException::printBetterMessage($exception, true));
      $traceStr   = RosasurferException::printBetterTrace($exception, true);
      $file       = $exception->getFile();
      $line       = $exception->getLine();
      $cliMessage = '[FATAL] '.$type.' '.$exMessage."\n in ".$file.' on line '.$line."\n";

      // display it
      if (CLI) {
         echoPre($cliMessage."\n".$traceStr."\n");
      }
      else {
         echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>[FATAL]</b> '.$type.' '.nl2br(htmlSpecialChars($exMessage, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
         echo '<br>'.printPretty($traceStr, true);
         echo "<br></div>\n";
      }
      return true;
   }
}
