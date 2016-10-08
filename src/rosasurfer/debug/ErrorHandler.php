<?php
namespace rosasurfer\debug;

use rosasurfer\core\StaticClass;

use rosasurfer\debug\Helper as DebugHelper;

use rosasurfer\exception\php\PHPCompileError;
use rosasurfer\exception\php\PHPCompileWarning;
use rosasurfer\exception\php\PHPCoreError;
use rosasurfer\exception\php\PHPCoreWarning;
use rosasurfer\exception\php\PHPError;
use rosasurfer\exception\php\PHPNotice;
use rosasurfer\exception\php\PHPParseError;
use rosasurfer\exception\php\PHPRecoverableError;
use rosasurfer\exception\php\PHPStrictError;
use rosasurfer\exception\php\PHPUnknownError;
use rosasurfer\exception\php\PHPUserError;
use rosasurfer\exception\php\PHPWarning;

use rosasurfer\log\Logger;

use function rosasurfer\_true;
use function rosasurfer\echoPre;

use const rosasurfer\L_FATAL;
use const rosasurfer\L_NOTICE;
use const rosasurfer\L_WARN;


/**
 * Gobal error handling
 */
class ErrorHandler extends StaticClass {


   /** @var int - error handling mode in which regular PHP errors are logged */
   const LOG_ERRORS       = 1;

   /** @var int - error handling mode in which regular PHP errors are converted to exceptions and thrown back */
   const THROW_EXCEPTIONS = 2;


   /** @var callable - the registered error handler */
   private static $errorHandler;

   /** @var int - the mode the error handler is configured for, can be either self::LOG_ERRORS or self::THROW_EXCEPTIONS */
   private static $errorMode;

   /** @var callable - the registered exception handler */
   private static $exceptionHandler;

   /** @var bool - whether or not the script is in the shutdown phase */
   private static $inShutdown;


   /**
    * Get the registered error handler (if any).
    *
    * @return callable
    */
   public static function getErrorHandler() {
      return self::$errorHandler;
   }


   /**
    * Get the registered exception handler (if any).
    *
    * @return callable
    */
   public static function getExceptionHandler() {
      return self::$exceptionHandler;
   }


   /**
    * Whether or not the script is in the shutdown phase.
    *
    * @return bool
    */
   public static function isInShutdown() {
      return (bool) self::$inShutdown;
   }


   /**
    * Setup global error handling.
    *
    * @param  int $mode - mode the error handler to setup for
    *                     can be either self::LOG_ERRORS or self::THROW_EXCEPTIONS
    */
   public static function setupErrorHandling($mode) {
      if     ($mode === self::LOG_ERRORS      ) self::$errorMode = self::LOG_ERRORS;
      elseif ($mode === self::THROW_EXCEPTIONS) self::$errorMode = self::THROW_EXCEPTIONS;
      else                                  return;

      set_error_handler(self::$errorHandler=static::class.'::handleError', E_ALL);  // E_ALL because error_reporting()
   }                                                                                // may change at runtime


   /**
    * Setup global exception handling.
    */
   public static function setupExceptionHandling() {
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
    * E_DEPRECATED, E_USER_DEPRECATED, E_USER_NOTICE and E_USER_WARNING are always logged and script execution
    * continues normally. All other errors are logged according to the configured error handling mode. Either they
    * are logged and script exceution continues normally, or they are wrapped in a PHPError exception and thrown back.
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
    * @throws PHPError
    */
   public static function handleError($level, $message, $file, $line, array $context) {
      // echoPre(__METHOD__.'()  '.DebugHelper::errorLevelToStr($level).': $message='.$message.', $file='.$file.', $line='.$line);
      // TODO: detect and handle recursive calls

      // (1) Ignore suppressed errors and errors not covered by the current reporting level.
      $reportingLevel = error_reporting();
      if (!$reportingLevel)            return false;     // the @ operator was specified
      if (!($reportingLevel & $level)) return true;      // error is not covered by current reporting level

      $logContext['file'] = $file;
      $logContext['line'] = $line;

      // (2) Process errors according to their severity level.
      switch ($level) {
         // always log non-critical and user errors and continue normally (without a stacktrace)
         case E_DEPRECATED     : return _true(Logger::log('E_DEPRECATED: '     .$message, L_NOTICE, $logContext));
         case E_USER_DEPRECATED: return _true(Logger::log('E_USER_DEPRECATED: '.$message, L_NOTICE, $logContext));
         case E_USER_NOTICE    : return _true(Logger::log('E_USER_NOTICE: '    .$message, L_NOTICE, $logContext));
         case E_USER_WARNING   : return _true(Logger::log('E_USER_WARNING: '   .$message, L_WARN  , $logContext));
      }

      // (3) Wrap everything else in the matching PHPError exception.
      switch ($level) {
         case E_PARSE            : $exception = new PHPParseError      ($message, $code=null, $severity=$level, $file, $line); break;
         case E_COMPILE_WARNING  : $exception = new PHPCompileWarning  ($message, $code=null, $severity=$level, $file, $line); break;
         case E_COMPILE_ERROR    : $exception = new PHPCompileError    ($message, $code=null, $severity=$level, $file, $line); break;
         case E_CORE_WARNING     : $exception = new PHPCoreWarning     ($message, $code=null, $severity=$level, $file, $line); break;
         case E_CORE_ERROR       : $exception = new PHPCoreError       ($message, $code=null, $severity=$level, $file, $line); break;
         case E_STRICT           : $exception = new PHPStrictError     ($message, $code=null, $severity=$level, $file, $line); break;
         case E_NOTICE           : $exception = new PHPNotice          ($message, $code=null, $severity=$level, $file, $line); break;
         case E_WARNING          : $exception = new PHPWarning         ($message, $code=null, $severity=$level, $file, $line); break;
         case E_ERROR            : $exception = new PHPError           ($message, $code=null, $severity=$level, $file, $line); break;
         case E_RECOVERABLE_ERROR: $exception = new PHPRecoverableError($message, $code=null, $severity=$level, $file, $line); break;
         case E_USER_ERROR       : $exception = new PHPUserError       ($message, $code=null, $severity=$level, $file, $line); break;
         default:
            $exception = new PHPUnknownError($message, $code=null, $severity=$level, $file, $line);
      }

      // (4) Handle the exception according to the configuration.
      if (self::$errorMode == self::LOG_ERRORS) {
         Logger::log($exception, L_ERROR, $logContext);
         return true;
      }

      // (5) Handle cases where throwing an exception is not possible or not allowed.

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
      $function = DebugHelper::getFQFunctionName($exception->getBetterTrace()[0]);
      if ($function=='require' || $function=='require_once') {
         self::$exceptionHandler->__invoke($exception);           // that's Closure::__invoke()
         return true;                                             // PHP will terminate the script anyway
      }

      // (6) Throw back everything else.
      throw $exception;
   }


   /**
    * Global handler for otherwise unhandled exceptions.
    *
    * The exception is sent to the default logger with loglevel L_FATAL. After the handler returns PHP will terminate
    * the script.
    *
    * @param \Exception $exception - the unhandled exception
    */
   public static function handleException(\Exception $exception) {
      $context = [];

      try {
         $context['class'    ] = static::class;          // atm not required but somewhen somewhere somebody might ask for it
         $context['file'     ] = $exception->getFile();  // if the location is not preset the Logger will correctly
         $context['line'     ] = $exception->getLine();  // resolve this method as the originating location
         $context['unhandled'] = true;

         Logger::log($exception, L_FATAL, $context);     // log with the highest level
      }

      // Exceptions thrown from within the exception handler will not be passed back to the handler again. Instead the
      // script terminates with an uncatchable fatal error.
      catch (\Exception $secondary) {                    // the application is crashing, last try to log
         $indent = ' ';

         // secondary exception
         $msg2  = 'PHP [FATAL] Unhandled '.trim(DebugHelper::getBetterMessage($secondary)).NL;
         $file  = $secondary->getFile();
         $line  = $secondary->getLine();
         $msg2 .= $indent.'in '.$file.' on line '.$line.NL.NL;
         $msg2 .= $indent.'Stacktrace:'.NL.' -----------'.NL;
         $msg2 .= DebugHelper::getBetterTraceAsString($secondary, $indent);

         // primary (the causing) exception
         if (isSet($context['cliMessage'])) {
            $msg1 = $context['cliMessage'];
            if (isSet($context['cliExtra']))
               $msg1 .= $context['cliExtra'];
         }
         else {
            $msg1  = $indent.'Unhandled '.trim(DebugHelper::getBetterMessage($exception)).NL;
            $file  = $exception->getFile();
            $line  = $exception->getLine();
            $msg1 .= $indent.'in '.$file.' on line '.$line.NL.NL;
            $msg1 .= $indent.'Stacktrace:'.NL.' -----------'.NL;
            $msg1 .= DebugHelper::getBetterTraceAsString($exception, $indent);
         }

         $msg  = $msg2.NL;
         $msg .= $indent.'caused by'.NL;
         $msg .= $msg1;
         $msg  = str_replace(chr(0), "?", $msg);                  // replace NUL bytes which mess up the logfile

         error_log(trim($msg), ERROR_LOG_DEFAULT);
      }
   }


   /**
    * Manually called handler for exceptions occurring in object destructors.
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
    * @example For an example see this folders's README file.
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
