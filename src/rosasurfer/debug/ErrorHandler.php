<?php
namespace rosasurfer\debug;

use rosasurfer\core\StaticClass;
use rosasurfer\exception\PHPError;
use rosasurfer\log\Logger;
use rosasurfer\util\DebugTools;

use function rosasurfer\_true;
use function rosasurfer\echoPre;


/**
 * Gobal error handling
 */
class ErrorHandler extends StaticClass {


   /** @var callable - the registered error handler */
   private static $errorHandler;

   /** @var callable - the registered exception handler */
   private static $exceptionHandler;

   /** @var bool - whether or not the script is in the shutdown phase */
   private static $inShutdown;


   /**
    * Whether or not the script is in the shutdown phase.
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
      set_error_handler(self::$errorHandler=__CLASS__.'::handleError', E_ALL);   // E_ALL because error_reporting()
                                                                                 // may change at runtime
      set_exception_handler(self::$exceptionHandler=function(\Exception $ex) {
         self::handleException($ex);
      });

      /**
       * Detect entering of the script's shutdown phase to be capable of handling destructor exceptions during shutdown
       * differently and avoid otherwise fatal errors. Should be the very first function on the shutdown function stack.
       *
       * @see  http://php.net/manual/en/language.oop5.decon.php
       * @see  ErrorHandler::handleDestructorException()
       */
      register_shutdown_function(function() {
         self::$inShutdown = true;
      });
   }


   /**
    * Global handler for traditional PHP errors.
    *
    * Errors are handled only if covered by the currently configured error reporting level. Errors of the levels
    * E_DEPRECATED, E_USER_DEPRECATED, E_USER_NOTICE and E_USER_WARNING are logged and script execution continuous
    * normally. All other errors are wrapped in a PHPErrorException and thrown back.
    *
    * @param  int     $level   - PHP error severity level
    * @param  string  $message - error message
    * @param  string  $file    - name of file where the error occurred
    * @param  int     $line    - line of file where the error occurred
    * @param  mixed[] $context - symbols of the point where the error occurred (variable scope at error trigger time)
    *
    * @return bool - TRUE,  if the error was successfully handled.
    *                FALSE, if the error shall be processed as if no error handler was installed.
    *                The error handler must return FALSE to populate the internal PHP variable <tt>$php_errormsg</tt>.
    *
    * @throws PHPErrorException
    */
   public static function handleError($level, $message, $file, $line, array $context) {
      // echoPre(__METHOD__.'()  '.DebugTools::errorLevelToStr($level).': $message='.$message.', $file='.$file.', $line='.$line);
      // TODO: detect and handle recursive calls

      // (1) Ignore suppressed errors and errors not covered by the current reporting level.
      $reportingLevel = error_reporting();
      if (!$reportingLevel)            return false;     // the @ operator was specified
      if (!($reportingLevel & $level)) return true;      // error is not covered by current reporting level

      $logContext = [];

      // (2) Process errors according to their severity level.
      switch ($level) {
         // log non-critical errors and continue normally
         case E_DEPRECATED:      return _true(Logger::log('E_DEPRECATED: '     .$message, L_NOTICE, $logContext));
         case E_USER_DEPRECATED: return _true(Logger::log('E_USER_DEPRECATED: '.$message, L_NOTICE, $logContext));
         case E_USER_NOTICE:     return _true(Logger::log('E_USER_NOTICE: '    .$message, L_NOTICE, $logContext));
         case E_USER_WARNING:    return _true(Logger::log('E_USER_WARNING: '   .$message, L_WARN  , $logContext));

         // wrap everything else in the matching PHPErrorException
         /*
         case E_PARSE:            break;
         case E_COMPILE_WARNING:  break;
         case E_COMPILE_ERROR:    break;
         case E_CORE_WARNING:     break;
         case E_CORE_ERROR:       break;
         case E_STRICT:           break;
         case E_NOTICE:           break;
         case E_WARNING:          break;
         case E_ERROR:            break;
         case E_RECOVERABLE_ERROR break;
         case E_USER_ERROR:       break;
         default:                 $errno_str = 'UNKNOWN';
         */
         default:
            $exception = new PHPError($message, $code=null, $severity=$level, $file, $line);
      }

      // (3) Handle cases where throwing an exception is not possible or not allowed.

      /**
       * Errors triggered by require() or require_once()
       *
       * Problem:  PHP errors triggered by require() or require_once() are non-catchable errors and do not follow
       *           regular application flow. PHP terminates the script after leaving the error handler, thrown
       *           exceptions are ignored. This termination is intended behaviour and the main difference to include()
       *           and include_once().
       * Solution: Manually call the exception handler.
       *
       * @see  http://stackoverflow.com/questions/25584494/php-set-exception-handler-not-working-for-error-thrown-in-set-error-handler-cal
       */
      $function = DebugTools::getFQFunctionName($exception->getBetterTrace()[0]);
      if ($function=='require' || $function=='require_once') {
         self::$exceptionHandler->__invoke($exception);           // that's Closure::__invoke()
         return true;                                             // PHP will terminate the script anyway
      }

      // (4) Throw back everything else.
      throw $exception;
   }


   /**
    * Global handler for otherwise unhandled exceptions.
    *
    * The exception is sent to the current default logger with loglevel L_FATAL. After the handler returns PHP will
    * terminate the script.
    *
    * @param \Exception $exception - the unhandled exception
    */
   public static function handleException(\Exception $exception) {
      //echoPre(__METHOD__.'()  '.DebugTools::getBetterMessage($exception));
      // TODO: detect and handle recursive calls

      $context = ['file' => $exception->getFile()];   // if the location is not preset the Logger will correctly
      $context = ['line' => $exception->getLine()];   // resolve this method as the originating location
      $context = ['type' => 'unhandled'          ];

      Logger::log($exception, L_FATAL, $context);     // log with the highest level
   }


   /**
    * Global handler for otherwise unhandled exceptions occurring in object destructors.
    *
    * Attempting to throw an exception from a destructor during script shutdown causes a fatal error. Therefore this
    * method has to be called manually from object destructors if an exception occurred. If the script is in the shutdown
    * phase the exception is passed on to the regular exception handler and the script is terminated. If the script is
    * currently not in the shutdown phase this method ignores the exception.
    *
    * @param  \Exception $exception
    *
    * @see     http://php.net/manual/en/language.oop5.decon.php
    *
    * @example For a code example see this namespace's README file.
    */
   public static function handleDestructorException(\Exception $exception) {
      if (self::isInShutdown()) {
         self::handleException($exception);
         exit(1);                            // exit und signal the error

         // Calling exit() is the only way to prevent the immediately following non-catchable fatal error.
         // However, calling exit() in a destructor will also prevent any remaining shutdown routines from executing.
         // @see above link
      }
   }
}
