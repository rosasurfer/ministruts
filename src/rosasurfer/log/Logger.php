<?php
namespace rosasurfer\log;

use rosasurfer\config\Config;

use rosasurfer\core\StaticClass;

use rosasurfer\debug\ErrorHandler;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\ministruts\Request;

use rosasurfer\net\http\CurlHttpClient;
use rosasurfer\net\http\HttpRequest;
use rosasurfer\net\http\HttpResponse;

use rosasurfer\util\DebugTools;
use rosasurfer\util\System;

use function rosasurfer\echoPre;
use function rosasurfer\ksort_r;
use function rosasurfer\printPretty;
use function rosasurfer\strLeftTo;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;
use const rosasurfer\ERROR_LOG_DEFAULT;
use const rosasurfer\ERROR_LOG_MAIL;
use const rosasurfer\L_DEBUG;
use const rosasurfer\L_ERROR;
use const rosasurfer\L_FATAL;
use const rosasurfer\L_INFO;
use const rosasurfer\L_NOTICE;
use const rosasurfer\L_WARN;
use const rosasurfer\LOCALHOST;
use const rosasurfer\NL;
use const rosasurfer\WINDOWS;


/**
 * Log a message through a chain of standard log handlers.
 *
 * This is the frameworks default logger implementation which is used if no other logger is registered. The logger
 * passes the message to a chain of handlers. Each handler is invoked depending on the application's runtime environment
 * (CLI vs. web server, local vs. remote access) and the application configuration.
 *
 *  • PrintHandler    - Display the message on the standard device (STDOUT on a terminal, HTTP response for SAPI). The
 *                      handler is invoked if the script runs on CLI or as a result of a local web request. For remote
 *                      web requests it displays the message only if the PHP configuration value "display_errors" is set.
 *
 *  • MailHandler     - Send the message to the configured mail receivers (email addresses). The handler is invoked if
 *                      the application configuration contains one or more mail receivers for log messages.
 *
 *                      Example:
 *                      --------
 *                      log.mail.receiver = address1@domain.tld, address2@another-domain.tld
 *
 *  • SMSHandler      - Send the message to the configured SMS receivers (phone numbers). The handler is invoked if the
 *                      application configuration contains one or more phone numbers for log messages and a valid SMS
 *                      operator configuration (at log time). For text messages an additional loglevel constraint can be
 *                      specified (on top of the default loglevel constraint).
 *
 *                      Example:
 *                      --------
 *                      log.sms.level    = error                           # additional loglevel constraint for SMS
 *                      log.sms.receiver = +3591234567, +441234567         # international number format
 *
 *  • ErrorLogHandler - The last resort log handler. Passes the message to the PHP default error log mechanism as defined
 *                      by the PHP configuration value "error_log". The handler is invoked if the MailHandler was not
 *                      invoked or if a circular or similar fatal error occurred. Typically this handler writes into the
 *                      PHP error log file which again should be monitored by a logwatch script.
 *
 *
 * Loglevel configuration:
 * -----------------------
 * The loglevel can be configured per class. For a class without a specific configuration the specified application
 * loglevel applies. Without a specified application loglevel the built-in default loglevel of L_NOTICE is used.
 *
 * Example:
 *  log.level                  = warn              # the general application loglevel is set to L_WARN
 *  log.level.MyClassA         = debug             # the loglevel for "MyClassA" is set to L_DEBUG
 *  log.level.foo\bar\MyClassB = notice            # the loglevel for "foo\bar\MyClassB" is set to L_NOTICE
 *  log.sms.level              = error             # the loglevel for text messages is set a bit higher to L_ERROR
 *
 *
 * @TODO: Logger::resolveLogCaller()   - test with Closure and internal PHP functions
 * @TODO: Logger::composeHtmlMessage() - append an existing context exception
 *
 * @TODO: refactor and separate handlers into single classes
 * @TODO: implement \Psr\Log\LoggerInterface and remove static crap
 * @TODO: implement full mail address support as in "Joe Blow <address@domain.tld>"
 */
class Logger extends StaticClass {


   /** @var int - built-in default loglevel; used if no application loglevel is configured */
   const DEFAULT_LOGLEVEL = L_NOTICE;


   /** @var int - application loglevel */
   private static $appLogLevel = null;


   /** @var bool - whether or not the PrintHandler is enabled */
   private static $printHandler = false;

   /** @var bool - whether or not the PrintHandler displays the full message */
   private static $printFullMessage = false;


   /** @var bool - whether or not the MailHandler is enabled */
   private static $mailHandler = false;

   /** @var string[] - mail receivers */
   private static $mailReceivers = [];


   /** @var bool - whether or not the SMSHandler is enabled */
   private static $smsHandler = false;

   /** @var string[] - SMS receivers */
   private static $smsReceivers = [];

   /** @var int - additional SMS loglevel constraint */
   private static $smsLogLevel = null;

   /** @var string[] - SMS options; resolved at log message time */
   private static $smsOptions = [];


   /** @var bool - whether or not the ErrorLogHandler is enabled */
   private static $errorLogHandler = false;


   /** @var string[] - loglevel descriptions for message formatter */
   private static $logLevels = [
      L_DEBUG  => '[Debug]' ,
      L_INFO   => '[Info]'  ,
      L_NOTICE => '[Notice]',
      L_WARN   => '[Warn]'  ,
      L_ERROR  => '[Error]' ,
      L_FATAL  => '[Fatal]' ,
   ];


   /**
    * Initialize the Logger configuration.
    */
   public static function init() {
      static $initialized = false;
      if ($initialized) return;

      // (1) application default loglevel: if not configured the built-in default loglevel
      $logLevel = Config::getDefault()->get('log.level', null);

      if (is_string($logLevel) || isSet($logLevel[''])) {   // the application and/or some class loglevels are configured
         if (is_array($logLevel))
            $logLevel = $logLevel[''];
         $logLevel = self::logLevelToId($logLevel);
         !$logLevel && $logLevel=self::DEFAULT_LOGLEVEL;
      }
      else {                                                // no loglevels are configured
         $logLevel = self::DEFAULT_LOGLEVEL;
      }
      self::$appLogLevel = $logLevel;

      // (2) PrintHandler: enabled for local access or if explicitely enabled
      self::$printHandler = CLI || LOCALHOST || ini_get('display_errors');

      // (3) MailHandler: enabled if mail receivers are configured
      $receivers = [];
      foreach (explode(',', Config::getDefault()->get('log.mail.receiver', '')) as $receiver) {
         if ($receiver=trim($receiver))
            $receivers[] = $receiver;                                // @TODO: validate address format
      }
      if ($receivers) {
         if ($forcedReceivers=Config::getDefault()->get('mail.address.forced-receiver', null)) {
            $receivers = [];                                         // To reduce possible errors we use internal PHP mailing
            foreach (explode(',', $forcedReceivers) as $receiver) {  // functions (not a class) which means we have to manually
               if ($receiver=trim($receiver))                        // check the config for the setting "mail.address.forced-receiver"
                  $receivers[] = $receiver;                          // (which the SMTPMailer would do automatically).
            }
         }
      }
      self::$mailHandler   = (bool) $receivers;
      self::$mailReceivers =        $receivers;

      // (4) SMSHandler: enabled if SMS receivers are configured (operator settings are checked at log message time)
      self::$smsReceivers = [];
      foreach (explode(',', Config::getDefault()->get('log.sms.receiver', '')) as $receiver) {
         if ($receiver=trim($receiver))
            self::$smsReceivers[] = $receiver;
      }
      self::$smsHandler = (bool) self::$smsReceivers;

      $logLevel = Config::getDefault()->get('log.sms.level', self::$appLogLevel);
      if (is_string($logLevel)) {                                    // a string if a configured value
         $logLevel = self::logLevelToId($logLevel);
         !$logLevel && $logLevel=self::$appLogLevel;
      }
      self::$smsLogLevel = $logLevel;

      // (5) ErrorLogHandler: enabled if the MailHandler is disabled
      self::$errorLogHandler = !self::$mailHandler;

      $initialized = true;
   }


   /**
    * Convert a loglevel description into a loglevel constant.
    *
    * @param  string $value - loglevel description
    *
    * @return int - loglevel constant or NULL, if $value is not a valid loglevel description
    */
   public static function logLevelToId($value) {
      if (!is_string($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));

      switch (strToLower($value)) {
         case 'debug' : return L_DEBUG;
         case 'info'  : return L_INFO;
         case 'notice': return L_NOTICE;
         case 'warn'  : return L_WARN;
         case 'error' : return L_ERROR;
         case 'fatal' : return L_FATAL;
         default:
            return null;
      }
   }


   /**
    * Resolve the loglevel of the specified class.
    *
    * @param  string $class - class name
    *
    * @return int - configured loglevel or the application loglevel if no class specific loglevel is configured
    */
   public static function getLogLevel($class = '') {
      if (!is_string($class)) throw new IllegalTypeException('Illegal type of parameter $class: '.getType($class));

      // read the configured class specific loglevels
      static $logLevels = null;
      if ($logLevels === null) {
         $logLevels = Config::getDefault()->get('log.level', []);
         if (is_string($logLevels))                                  // only the general application loglevel is configured
            $logLevels = ['' => $logLevels];

         foreach ($logLevels as $className => $level) {
            if (!is_string($level)) throw new IllegalTypeException('Illegal configuration value for "log.level.'.$className.'": '.getType($level));

            if ($level == '') {                                      // classes with empty values fall back to the application loglevel
               unset($logLevels[$className]);
            }
            else {
               $logLevel = self::logLevelToId($level);
               if (!$logLevel)      throw new InvalidArgumentException('Invalid configuration value for "log.level.'.$className.'" = '.$level);
               $logLevels[$className] = $logLevel;

               if (strStartsWith($className, '\\')) {                // normalize class names: remove leading back slash
                  unset($logLevels[$className]);
                  $className = subStr($className, 1);
                  $logLevels[$className] = $logLevel;
               }
            }
         }
         $logLevels = array_change_key_case($logLevels, CASE_LOWER); // normalize class names: lower-case for case-insensitive look-up
      }

      // look-up the loglevel for the specified class
      $class = strToLower($class);
      if (isSet($logLevels[$class]))
         return $logLevels[$class];

      // return the general application loglevel if no class specific loglevel is configured
      return self::$appLogLevel;
   }


   /**
    * Log a message.
    *
    * @param  mixed   $loggable - a message or an object implementing <tt>__toString()</tt>
    * @param  int     $level    - loglevel
    * @param  mixed[] $context  - optional logging context with additional data
    */
   public static function log($loggable, $level, array $context=[]) {
      // (1) validate parameters
      if (!is_string($loggable)) {
         if (!is_object($loggable))                   throw new IllegalTypeException('Illegal type of parameter $loggable: '.getType($loggable));
         if (!method_exists($loggable, '__toString')) throw new IllegalTypeException('Illegal type of parameter $loggable: '.get_class($loggable).'::__toString() not found');
         if (!$loggable instanceof \Exception)
            $loggable = (string) $loggable;
      }
      if (!is_int($level))                            throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));
      if (!isSet(self::$logLevels[$level]))           throw new InvalidArgumentException('Invalid argument $level: '.$level.' (not a loglevel)');


      // (2) filter messages not covered by the current loglevel
      if ($level == L_FATAL) {
         // Not necessary for the highest level. This will cover all calls from the global exception handler
         // which always calls with the highest level (L_FATAL).
      }
      else {
         // resolve the calling class and check its loglevel
         !isSet($context['class']) && self::resolveLogCaller($loggable, $level, $context);
         $class = $context['class'];
         if ($level < self::getLogLevel($class))      // return if message is not covered
            return;
      }


      // (3) invoke all active log handlers
      self::$printHandler    && self::invokePrintHandler   ($loggable, $level, $context);
      self::$mailHandler     && self::invokeMailHandler    ($loggable, $level, $context);
      self::$smsHandler      && self::invokeSmsHandler     ($loggable, $level, $context);
      self::$errorLogHandler && self::invokeErrorLogHandler($loggable, $level, $context);
   }


   /**
    * Display the message on the standard device (STDOUT on a terminal, HTTP response for a web request).
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context with additional data
    */
   private static function invokePrintHandler($loggable, $level, array &$context) {
      if (!self::$printHandler) return;
      $message = null;

      if (CLI) {
         !isSet($context['cliMessage']) && self::composeCliMessage($loggable, $level, $context);
         $message = $context['cliMessage'];
         if (isSet($context['cliExtra']))
            $message .= $context['cliExtra'];
      }
      else {
         !isSet($context['htmlMessage']) && self::composeHtmlMessage($loggable, $level, $context);
         $message = $context['htmlMessage'];
      }

      echo $message;
      ob_get_level() && ob_flush();
   }


   /**
    * Send the message to the configured mail receivers (email addresses).
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context with additional data
    */
   private static function invokeMailHandler($loggable, $level, array &$context) {
      if (!self::$mailHandler) return;

      echoPre(__METHOD__.'() not yet implemented');

      !isSet($context['cliMessage']) && self::composeCliMessage($loggable, $level, $context);
      $cliMessage = $context['cliMessage'];
   }


   /**
    * Send the message to the configured SMS receivers (phone numbers).
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context with additional data
    */
   private static function invokeSmsHandler($loggable, $level, array &$context) {
      if (!self::$smsHandler) return;

      echoPre(__METHOD__.'() not yet implemented');

      !isSet($context['cliMessage']) && self::composeCliMessage($loggable, $level, $context);
      $cliMessage = $context['cliMessage'];
   }


   /**
    * Pass the message to the PHP default error log mechanism as defined by the PHP configuration value "error_log".
    *
    * ini_get('error_log')
    *
    * Name of the file where script errors should be logged. If the special value "syslog" is used, errors are sent
    * to the system logger instead. On Unix, this means syslog(3) and on Windows NT it means the event log.
    * If this directive is not set, errors are sent to the SAPI error logger. For example, it is an error log in Apache
    * or STDERR in CLI.
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context with additional data
    */
   private static function invokeErrorLogHandler($loggable, $level, array &$context) {
      if (!self::$errorLogHandler) return;
      !isSet($context['cliMessage']) && self::composeCliMessage($loggable, $level, $context);

      $message = 'PHP '.$context['cliMessage'];                // skip "cliExtra"
      $message = str_replace(["\r\n", "\n"], ' ', $message);   // remove line breaks
      $message = str_replace(chr(0), "…", $message);           // replace NUL bytes which mess up the logfile

      if (!ini_get('error_log') && CLI) {
         // suppress duplicated output to STDERR, the PrintHandler already wrote to STDOUT
         // @TODO: suppress output to STDERR in interactive terminals only (i.e. not in cron)
      }
      else {
         error_log($message, ERROR_LOG_DEFAULT);
      }
   }


   /**
    * Compose a CLI log message and store it in the passed log context under the keys "cliMessage" and "cliExtra".
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context
    */
   private static function composeCliMessage($loggable, $level, array &$context) {
      if (!isSet($context['file']) || !isSet($context['line'])) self::resolveLogLocation($loggable, $level, $context);
      $file = $context['file'];
      $line = $context['line'];

      $text = $extra = null;
      $indent = ' ';

      // compose message
      if (is_string($loggable)) {
         // simple message
         $msg  = $loggable;
         $text = self::$logLevels[$level].'  '.$msg.NL.$indent.'in '.$file.' on line '.$line.NL;
      }
      else {
         // exception
         $type = isSet($context['type']) ? ucFirst($context['type']).' ' : '';
         $msg  = $type.trim(DebugTools::getBetterMessage($loggable));
         $text = self::$logLevels[$level].'  '.$msg.NL.$indent.'in '.$file.' on line '.$line.NL;

         // the stack trace will go into "cliExtra"
         $traceStr  = $indent.'Stacktrace:'.NL.' -----------'.NL;
         $traceStr .= DebugTools::getBetterTraceAsString($loggable, $indent);
         $extra    .= NL.$traceStr.NL;
      }

      // append an existing context exception to "cliExtra"
      if (isSet($context['exception'])) {
         $exception = $context['exception'];
         $msg       = trim(DebugTools::getBetterMessage($exception));
         $traceStr  = $indent.'Stacktrace:'.NL.' -----------'.NL;
         $traceStr .= DebugTools::getBetterTraceAsString($exception, $indent);
         $extra    .= NL.$msg.NL.NL.$traceStr.NL;
      }

      // store main and extra message
      $context['cliMessage'] = $text;
      if ($extra)
         $context['cliExtra'] = $extra;
   }


   /**
    * Compose a HTML log message and store it in the passed log context under the key "htmlMessage".
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context
    */
   private static function composeHtmlMessage($loggable, $level, array &$context) {
      if (!isSet($context['file']) || !isSet($context['line'])) self::resolveLogLocation($loggable, $level, $context);
      $file = $context['file'];
      $line = $context['line'];

      // break out of unfortunate HTML tags
      $html   = '</script></img></select></textarea></font></span></div></i></b>';
      $html  .= '<div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif">';
      $indent = ' ';

      // compose message
      if (is_string($loggable)) {
         // simple message
         $msg   = $loggable;
         $html .= '<b>'.self::$logLevels[$level].'</b> '.nl2br(htmlSpecialChars($msg, ENT_QUOTES|ENT_SUBSTITUTE))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
      }
      else {
         // exception
         $type      = isSet($context['type']) ? ucFirst($context['type']).' ' : '';
         $msg       = $type.DebugTools::getBetterMessage($loggable);
         $html     .= '<b>'.self::$logLevels[$level].'</b> '.nl2br(htmlSpecialChars($msg, ENT_QUOTES|ENT_SUBSTITUTE))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
         $traceStr  = $indent.'Stacktrace:'.NL.' -----------'.NL;
         $traceStr .= DebugTools::getBetterTraceAsString($loggable, $indent);
         $html     .= '<br>'.printPretty($traceStr, true).'<br>';
      }

      // append an existing context exception
      if (isSet($context['exception'])) {
         $exception = $context['exception'];
         $msg       = DebugTools::getBetterMessage($exception);
         $html     .= '<br>'.nl2br(htmlSpecialChars($msg, ENT_QUOTES|ENT_SUBSTITUTE)).'<br>';
         $traceStr  = $indent.'Stacktrace:'.NL.' -----------'.NL;
         $traceStr .= DebugTools::getBetterTraceAsString($exception, $indent);
         $html     .= '<br>'.printPretty($traceStr, true);
     }

      // append the current HTTP request
      if (!CLI) {
         $html .= '<br>'.printPretty('Request:'.NL.'--------'.NL.Request::me(), true).'<br>';
      }

      // close and store the HTML tag
      $html .= '</div>';
      $context['htmlMessage'] = $html;
   }


   /**
    * Resolve the location the current log statement originated from and store it in the passed log context under the
    * keys "file" and "line".
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context
    */
   private static function resolveLogLocation($loggable, $level, array &$context) {
      !isSet($context['trace']) && $context['trace']=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      $trace = $context['trace'];

      foreach ($trace as $i => $frame) {           // find the first non-logger frame with "file"
         if (isSet($frame['class']) && $frame['class']==__CLASS__)
            continue;
         if (!isSet($trace[$i-1]['file']))         // first non-logger frame, "file" and "line" are in the previous frame
            continue;                              // skip internal PHP functions
         $context['file'] = $trace[$i-1]['file'];
         $context['line'] = $trace[$i-1]['line'];
         break;
      }
      // intentionally cause an error if not found (should never happen)
   }


   /**
    * Resolve the class calling the logger and store it in the passed log context under the key "class".
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context
    *
    *
    * @TODO:  test with Closure and internal PHP functions
    */
   private static function resolveLogCaller($loggable, $level, array &$context) {
      !isSet($context['trace']) && $context['trace']=debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      $trace = $context['trace'];

      $class = '';
      foreach ($trace as $frame) {                 // find the frame calling this logger
         if (!isSet($frame['class']))              // logger was called from a non-class context
            break;
         if ($frame['class'] != __CLASS__) {       // logger was called from another class
            $class = $frame['class'];
            break;
         }
      }
      $context['class'] = $class;
   }

















   /**
    * Globaler Handler für nicht abgefangene Exceptions. Die Exception wird mit dem Loglevel L_FATAL geloggt und das Script beendet.
    * Der Aufruf kann automatisch (durch installierten Errorhandler) oder manuell (durch Code, der selbst keine Exceptions werfen darf)
    * erfolgen.
    *
    * NOTE: PHP bricht das Script nach Aufruf dieses Handlers automatisch ab.
    *
    * @param  Exception $exception - die zu behandelnde Exception
    */
   public static function handleException(\Exception $exception) {
      try {
         // 1. Fehlerdaten ermitteln
         $type       = $exception instanceof \ErrorException ? 'Unexpected':'Unhandled';
         $exMessage  = trim(DebugTools::getBetterMessage($exception));
         $indent     = ' ';
         $traceStr   = $indent.'Stacktrace:'.NL.' -----------'.NL;
         $traceStr  .= DebugTools::getBetterTraceAsString($exception, $indent);
         $file       = $exception->getFile();
         $line       = $exception->getLine();
         $cliMessage = '[FATAL] '.$type.' '.$exMessage.NL.$indent.'in '.$file.' on line '.$line.NL;

         // 2. Exception anzeigen
         if (self::$print) {
            if (CLI) {
               echoPre($cliMessage.NL.$traceStr.NL);
            }
            else {
               echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>[FATAL]</b> '.$type.' '.nl2br(htmlSpecialChars($exMessage, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
               echo '<br>'.printPretty($traceStr, true);
               echo '<br></div>'.NL;
            }
         }
         else {
            echoPre('application error');
         }

         // 3. Exception an die konfigurierten Adressen mailen
         if (self::$mail) {
            $mailMsg = $cliMessage.NL.$traceStr;
            $request = null;

            if (CLI) {
               $mailMsg .= NL.NL.'Shell:'.NL.'------'.NL.print_r(ksort_r($_SERVER), true).NL.NL;
            }
            else {
               $request = Request::me();
               $session = $request->isSession() ? print_r(ksort_r($_SESSION), true) : null;
               $ip      = $_SERVER['REMOTE_ADDR'];
               $host    = getHostByAddr($ip);
               if ($host != $ip)
                  $ip .= ' ('.$host.')';

               $mailMsg .= NL
                        .  NL
                        . 'Request:'                                                   .NL
                        . '--------'                                                   .NL
                        .  $request                                                    .NL
                        .                                                               NL
                        . 'Session: '.($session ? NL.'--------'.NL.$session : '(none)').NL
                        .                                                               NL
                        . 'Server:'                                                    .NL
                        . '-------'                                                    .NL
                        .  print_r(ksort_r($_SERVER), true)                            .NL
                        .                                                               NL
                        . 'IP:   '.$ip                                                 .NL
                        . 'Time: '.date('Y-m-d H:i:s')                                 .NL;
            }
            $subject = 'PHP [FATAL] Unhandled Exception at '.($request ? strLeftTo($request->getUrl(), '?') : $_SERVER['PHP_SELF']);
            self::mail_log($subject, $mailMsg);
         }


         // (4) Logmessage ins Error-Log schreiben, wenn sie nicht per Mail rausging
         else {
            self::error_log('PHP '.$cliMessage);
         }


         // (5) Logmessage per SMS verschicken
         if (self::$sms) {
            self::sms_log($cliMessage);
         }
      }
      catch (\Exception $secondary) {
         $file = $exception->getFile();
         $line = $exception->getLine();
         $msg  = 'PHP primary '.(string)$exception.' in '.$file.' on line '.$line;
         self::$print && echoPre($msg);
         self::error_log($msg);

         $file = $secondary->getFile();
         $line = $secondary->getLine();
         $msg  = 'PHP secondary '.(string)$secondary.' in '.$file.' on line '.$line;
         self::$print && echoPre($msg);
         self::error_log($msg);
      }
   }


   /**
    * Loggt eine Message und/oder eine Exception.
    *
    * @param  string     $message   - zu loggende Message
    * @param  \Exception $exception - zu loggende Exception
    * @param  int        $level     - LogLevel
    * @param  string     $class     - Name der Klasse, von der aus geloggt wurde
    */
   public static function log_old($message, $exception, $level, $class) {
      // lock method against circular calls
      static $isActive = false;                                      // TODO: SYSLOG implementieren
      if ($isActive)     return echoPre(__METHOD__.'()  Sending circular log message to SYSLOG:  '.$message.', '.$exception);
      $isActive        = true;

      if ($level >= self::getLogLevel($class)) {
         self::log_1($message, $exception, $level);
      }

      // unlock method
      $isActive = false;
   }


   /**
    * Loggt eine Message und/oder eine Exception.
    *
    * @param  string    $message   - zu loggende Message
    * @param  Exception $exception - zu loggende Exception
    * @param  int       $level     - zu loggender Loglevel
    */
   private static function log_1($message, \Exception $exception=null, $level) {
      $request    = CLI ? null : Request::me();
      $cliMessage = null;

      try {
         // 1. Logdaten ermitteln
         $exMessage = $exTraceStr = null;
         if ($exception) {
            $exMessage   = trim(DebugTools::getBetterMessage($exception));
            $indent      = ' ';
            $exTraceStr  = $indent.'Stacktrace:'.NL.' -----------'.NL;
            $exTraceStr .= DebugTools::getBetterTraceAsString($exception, $indent);
         }

         $trace = DebugTools::fixTrace(debug_backtrace());  // die Frames des Loggers selbst können weg
         if ($trace[0]['class'].'::'.$trace[0]['function'] == 'Logger::log_1') array_shift($trace);
         if ($trace[0]['class'].'::'.$trace[0]['function'] == 'Logger::log'  ) array_shift($trace);

         $file = $line = null;
         foreach ($trace as $frame) {                       // ersten Frame mit __FILE__ suchen
            if (isSet($frame['file'])) {
               $file = $frame['file'];
               $line = $frame['line'];
               break;
            }
         }
         $cliMessage = self::$logLevels[$level].'  '.$message.NL.'in '.$file.' on line '.$line;


         // 2. Logmessage anzeigen
         if (self::$print) {
            if (CLI) {
               echo $cliMessage.NL;
               if ($exception) {
                  echo NL.$exMessage.NL.NL.$exTraceStr.NL;
               }
            }
            else {
               echo '</script></img></select></textarea></font></span></div></i></b><div align="left" style="clear:both; font:normal normal 12px/normal arial,helvetica,sans-serif"><b>'.self::$logLevels[$level].'</b>: '.nl2br(htmlSpecialChars($message, ENT_QUOTES))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
               if ($exception) {
                  echo '<br>'.htmlSpecialChars($exMessage, ENT_QUOTES).'<br>';
                  echo '<br>'.printPretty($exTraceStr, true).'<br>';
               }
               echo '<br>'.printPretty('Request:'.NL.'--------'.NL.$request, true).'<br></div>'.NL;
            }
            ob_get_level() && ob_flush();
         }


         // 3. Logmessage an die konfigurierten Adressen mailen
         if (self::$mail) {
            $mailMsg = $cliMessage.NL.($exception ? NL.$exMessage.NL.NL.$exTraceStr.NL : '');

            if (CLI) {
               $mailMsg .= NL.NL.NL.'Shell:'.NL.'------'.NL.print_r(ksort_r($_SERVER), true).NL.NL.NL;
            }
            else {
               $session = $request->isSession() ? print_r(ksort_r($_SESSION), true) : null;
               $ip      = $_SERVER['REMOTE_ADDR'];
               $host    = getHostByAddr($ip);
               if ($host != $ip)
                  $ip .= ' ('.$host.')';

               $mailMsg .= NL.NL.NL.'Request:'.NL.'--------'.NL.$request.NL.NL.NL
                        .  'Session: '.($session ? NL.'--------'.NL.$session : '(none)').NL.NL.NL
                        .  'Server: '.NL.'-------'.NL.print_r(ksort_r($_SERVER), true).NL.NL.NL
                        .  'IP:   '.$ip.NL
                        .  'Time: '.date('Y-m-d H:i:s').NL;
            }
            $subject = 'PHP: '.self::$logLevels[$level].' at '.($request ? strLeftTo($request->getUrl(), '?') : $_SERVER['PHP_SELF']);
            self::mail_log($subject, $mailMsg);
         }


         // (4) Logmessage ins Error-Log schreiben, wenn sie nicht per Mail rausging
         else {
            self::error_log('PHP '.$cliMessage);
         }


         // (5) Logmessage per SMS verschicken, wenn der konfigurierte SMS-Loglevel erreicht ist
         if (self::$sms && $level >= self::$smsLogLevel) {
            self::sms_log($cliMessage.($exception ? NL.$exMessage:''));
         }
      }
      catch (\Exception $ex) {
         $msg = 'PHP (0) '.$cliMessage ? $cliMessage:$message;
         self::$print && echoPre($msg);
         self::error_log($msg);
         throw $ex;
      }
   }


   /**
    * Verschickt eine Logmessage per E-Mail.
    *
    * @param  string $subject - Subject der E-Mail
    * @param  string $message - zu loggende Message
    */
   private static function mail_log($subject, $message) {
      $message = str_replace("\r\n", "\n", $message);                // Linux: Unix-Zeilenumbrüche
      if (WINDOWS)
         $message = str_replace("\n", "\r\n", $message);             // Windows: Windows-Zeilenumbrüche
      $message = str_replace(chr(0), "*\x00*", $message);            // NUL-Characters ersetzen (zerschießen E-Mail)

      foreach (self::$mailReceivers as $receiver) {
         // TODO: durch mail() ersetzen, damit Subject-Header von PHP nicht doppelt geschrieben wird
         error_log($message, ERROR_LOG_MAIL, $receiver, 'Subject: '.$subject);
      }
   }


   /**
    * Verschickt eine Logmessage per SMS an die konfigurierten Rufnummern.
    *
    * @param  string $message - zu loggende Message
    */
   private static function sms_log($message) {
      if (empty($message))
         return;
      $message  = str_replace("\r\n", "\n", $message);                     // Unix-Zeilenumbrüche
      $message  = subStr($message, 0, 150);                                // Länge der Message auf eine SMS begrenzen

      $username = self::$smsOptions['username'];
      $password = self::$smsOptions['password'];
      $api_id   = self::$smsOptions['api_id'  ];

      foreach (self::$smsReceivers as $receiver) {
         if (strStartsWith($receiver, '+' )) $receiver = subStr($receiver, 1);
         if (strStartsWith($receiver, '00')) $receiver = subStr($receiver, 2);
         if (!ctype_digit($receiver)) throw new InvalidArgumentException('Invalid argument $receiver: "'.$receiver.'"');

         $url = 'https://api.clickatell.com/http/sendmsg?user='.$username.'&password='.$password.'&api_id='.$api_id.'&to='.$receiver.'&text='.urlEncode($message);

         // TODO: CURL-Abhängigkeit möglichst durch interne URL-Funktionen ersetzen

         // HTTP-Request erzeugen und ausführen
         $request  = HttpRequest::create()->setUrl($url);
         $options[CURLOPT_SSL_VERIFYPEER] = false;                            // das SSL-Zertifikat kann nicht überprüfbar oder ungültig sein
         $response = CurlHttpClient::create($options)->send($request);
         $status   = $response->getStatus();
         $content  = $response->getContent();
         if ($status != 200) throw new RuntimeException('Unexpected HTTP status code from api.clickatell.com: '.$status.' ('.HttpResponse ::$sc[$status].')');

         // TODO: SMS ggf. auf zwei message parts verlängern
         if (striPos($content, 'ERR: 113') === 0)                             // ERR: 113, Max message parts exceeded
            return self ::sms_log(subStr($message, 0, strLen($message)-10));  // mit kürzerer Message wiederholen
      }
   }
}
Logger::init();
