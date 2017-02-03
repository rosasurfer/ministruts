<?php
namespace rosasurfer\log;

use rosasurfer\config\Config;
use rosasurfer\core\StaticClass;

use rosasurfer\debug\DebugHelper;
use rosasurfer\debug\ErrorHandler;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\InvalidArgumentException;
use rosasurfer\exception\RuntimeException;

use rosasurfer\exception\php\PHPError;

use rosasurfer\net\http\CurlHttpClient;
use rosasurfer\net\http\HttpRequest;
use rosasurfer\net\http\HttpResponse;

use rosasurfer\struts\Request;
use rosasurfer\util\PHP;

use function rosasurfer\ksort_r;
use function rosasurfer\printPretty;
use function rosasurfer\strContains;
use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;
use function rosasurfer\strStartsWithI;

use const rosasurfer\CLI;
use const rosasurfer\ERROR_LOG_DEFAULT;
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
 * This is the framework's default logger implementation which is used if no other logger is registered. The logger
 * passes the message to a chain of handlers. Each handler is invoked depending on the application's runtime environment
 * (CLI vs. web server, local vs. remote access) and the application configuration.
 *
 *  - PrintHandler:    Display the message on the standard device (STDOUT on a terminal, HTTP response for SAPI). The
 *                     handler is invoked if the script runs on CLI or as a result of a local web request. For remote
 *                     web requests it displays the message only if the PHP configuration value "display_errors" is set.
 *
 *  - MailHandler:     Send the message to the configured mail receivers (email addresses). The handler is invoked if
 *                     the application configuration contains one or more mail receivers for log messages.
 *
 *                     Example:
 *                     --------
 *                     log.mail.receiver = address1@domain.tld, address2@another-domain.tld
 *
 *  - SMSHandler:      Send the message to the configured SMS receivers (phone numbers). The handler is invoked if the
 *                     application configuration contains one or more phone numbers for log messages and a valid SMS
 *                     operator configuration (at log time). For text messages an additional loglevel constraint can be
 *                     specified (on top of the default loglevel constraint). At the moment the message providers
 *                     Clickatell and Nexmo are supported.
 *
 *                     Example:
 *                     --------
 *                     log.sms.level    = error                           # additional loglevel constraint for SMS
 *                     log.sms.receiver = +3591234567, +441234567         # international number format
 *
 *  - ErrorLogHandler: The last resort log handler. Passes the message to the PHP default error log mechanism as defined
 *                     by the PHP configuration value "error_log". The handler is invoked if the MailHandler was not
 *                     invoked or if a circular or similar fatal error occurred. Typically this handler writes into the
 *                     PHP error log file which again should be monitored by a logwatch script.
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
   private static $appLogLevel = self::DEFAULT_LOGLEVEL;


   /** @var bool - whether or not the PrintHandler is enabled */
   private static $printHandler = false;

   /** @var int - counter for messages handled by the PrintHandler */
   private static $printCounter = 0;


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
   private static $errorLogHandler = true;


   /** @var string[] - loglevel descriptions for message formatter */
   private static $logLevels = [
      L_DEBUG  => 'Debug' ,
      L_INFO   => 'Info'  ,
      L_NOTICE => 'Notice',
      L_WARN   => 'Warn'  ,
      L_ERROR  => 'Error' ,
      L_FATAL  => 'Fatal' ,
   ];


   /**
    * Initialize the Logger configuration.
    */
   public static function init() {
      static $initialized = false;
      if ($initialized) return;


      // (1) application default loglevel: if not configured the built-in default loglevel
      if (!$config=Config::getDefault())
         throw new RuntimeException('Service locator returned invalid default config: '.getType($config));
      $logLevel = $config->get('log.level', null);

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
      self::$printHandler = CLI || LOCALHOST || PHP::ini_get_bool('display_errors');


      // (3) MailHandler: enabled if mail receivers are configured
      $receivers = [];
      foreach (explode(',', $config->get('log.mail.receiver', '')) as $receiver) {
         if ($receiver=trim($receiver))
            $receivers[] = $receiver;                                // @TODO: validate address format
      }
      if ($receivers) {
         if ($forcedReceivers=$config->get('mail.forced-receiver', null)) {
            $receivers = [];                                         // To reduce possible errors we use internal PHP mailing
            foreach (explode(',', $forcedReceivers) as $receiver) {  // functions (not a class) which means we have to manually
               if ($receiver=trim($receiver))                        // check the config for the setting "mail.forced-receiver"
                  $receivers[] = $receiver;                          // (which the SMTPMailer would do automatically).
            }
         }
      }
      self::$mailHandler   = (bool) $receivers;
      self::$mailReceivers =        $receivers;


      // (4) SMSHandler: enabled if SMS receivers are configured (operator settings are checked at log message time)
      self::$smsReceivers = [];
      foreach (explode(',', $config->get('log.sms.receiver', '')) as $receiver) {
         if ($receiver=trim($receiver)) {
            if (strStartsWith($receiver, '+' )) $receiver = subStr($receiver, 1);
            if (strStartsWith($receiver, '00')) $receiver = subStr($receiver, 2);
            if (!ctype_digit($receiver)) {
               Logger::log('Invalid SMS receiver configuration: "'.$receiver.'"', L_WARN, ['class'=>__CLASS__, 'file'=>__FILE__, 'line'=>__LINE__]);
               continue;
            }
            self::$smsReceivers[] = $receiver;
         }
      }
      $logLevel = $config->get('log.sms.level', self::$appLogLevel);
      if (is_string($logLevel)) {                                    // a string if a configured value
         $logLevel = self::logLevelToId($logLevel);
         !$logLevel && $logLevel=self::$appLogLevel;
      }
      self::$smsLogLevel = $logLevel;
      self::$smsOptions  = $config->get('sms', []);
      self::$smsHandler  = self::$smsReceivers && self::$smsOptions;


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
         if (!$config=Config::getDefault())
            throw new RuntimeException('Service locator returned invalid default config: '.getType($config));

         $logLevels = $config->get('log.level', []);
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
      // Wrap everything in a try-catch block to prevent user generated log messages
      // from getting lost if logging fails.
      try {

         // block recursive calls
         // TODO: instead of blocking recursive calls block duplicate messages
         static $isActive = false;
         if ($isActive) throw new RuntimeException('Blocking recursive call to '.__METHOD__.'()');
         $isActive = true;                                           // lock the method


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
         $filtered = false;
         if ($level == L_FATAL) {
            // Filtering not necessary for the highest level. This will cover all calls from the global exception handler
            // which always logs with the highest level (L_FATAL).
         }
         else {
            // resolve the calling class and check its loglevel
            !isSet($context['class']) && self::resolveLogCaller($loggable, $level, $context);
            if ($level < self::getLogLevel($context['class']))
               $filtered = true;                                     // message is not covered
         }


         // (3) filter "headers already sent" errors generated by a previously printed log message
         if (self::$printCounter && $loggable instanceof PHPError) {
            $message = $loggable->getMessage();
            if (strContains($message, '- headers already sent (output started at '.__FILE__)) {
               $filtered = true;
            }
            elseif (strContains($message, '- headers already sent by (output started at '.__FILE__)) {
               $filtered = true;
            }
         }


         // (4) invoke all active log handlers
         if (!$filtered) {
            self::$printHandler    && self::invokePrintHandler   ($loggable, $level, $context);
            self::$mailHandler     && self::invokeMailHandler    ($loggable, $level, $context);
            self::$smsHandler      && self::invokeSmsHandler     ($loggable, $level, $context);
            self::$errorLogHandler && self::invokeErrorLogHandler($loggable, $level, $context);
         }

         // unlock the method
         $isActive = false;

      }
      catch (\Exception $ex) {
         // Prevent user generated log messages from getting lost if logging fails. In the context of this method
         // only exceptions logged with L_FATAL are not user generated (i.e. they are generated by the registered
         // exception handler).
         if ($level!=L_FATAL || !$loggable instanceof \Exception) {
            $file = isSet($context['file']) ? $context['file'] : '';
            $line = isSet($context['line']) ? $context['line'] : '';
            $msg  = 'PHP ['.strToUpper(self::$logLevels[$level]).'] '.$loggable.NL.' in '.$file.' on line '.$line;
            error_log(trim($msg), ERROR_LOG_DEFAULT);
         }
         throw $ex;
      }
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
         if (self::$printCounter) $message = NL;
         $message .= $context['cliMessage'];
         if (isSet($context['cliExtra']))
            $message .= $context['cliExtra'];
      }
      else {
         !isSet($context['htmlMessage']) && self::composeHtmlMessage($loggable, $level, $context);
         $message = $context['htmlMessage'];
      }

      echo $message;
      ob_get_level() && ob_flush();

      self::$printCounter++;
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
      if (!isSet($context['mailSubject']) || !isSet($context['mailMessage']))
         self::composeMailMessage($loggable, $level, $context);

      $subject = $context['mailSubject'];
      $message = $context['mailMessage'];

      $message = str_replace("\r\n", "\n", $message);                // use Unix line-breaks on Linux
      if (WINDOWS)                                                   // and Windows line-breaks on Windows
         $message = str_replace("\n", "\r\n", $message);
      $message = str_replace(chr(0), "?", $message);                 // replace NUL bytes which destroy the mail

      if (!$config=Config::getDefault()) throw new RuntimeException('Service locator returned invalid default config: '.getType($config));
      $sender  = $config->get('mail.from', null);
      $headers = [];
      $sender && $headers[]='From: '.$sender;

      foreach (self::$mailReceivers as $receiver) {
         // Windows: mail() fails if "sendmail_from" is not set and "From:" header is missing
         // Linux:   "From:" header is not reqired but set if provided
         mail($receiver, $subject, $message, join("\r\n", $headers));
      }
   }


   /**
    * Send the message to the configured SMS receivers (phone numbers).
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context with additional data
    *
    * @TODO:  replace CURL dependency with internal PHP functions
    */
   private static function invokeSmsHandler($loggable, $level, array &$context) {
      if (!self::$smsHandler) return;
      if (!isSet($context['cliMessage']))
         self::composeCliMessage($loggable, $level, $context);

      // (1) CURL options (all service providers)
      $curlOptions[CURLOPT_SSL_VERIFYPEER] = false;                  // the SSL certifikat may be self-signed or invalid
    //$curlOptions[CURLOPT_VERBOSE       ] = true;                   // enable debugging


      // (2) clean-up message
      $message = trim($context['cliMessage']);
      $message = str_replace("\r\n", "\n", $message);                // enforce Unix line-breaks
      $message = subStr($message, 0, 150);                           // limit length to about one text message


      // (3) check availability and use Clickatell
      if (isSet(self::$smsOptions['clickatell'])) {
         $smsOptions = self::$smsOptions['clickatell'];
         if (!empty($smsOptions['user']) && !empty($smsOptions['password']) && !empty($smsOptions['api_id'])) {
            $params = [];
            $params['user'    ] = $smsOptions['user'    ];
            $params['password'] = $smsOptions['password'];
            $params['api_id'  ] = $smsOptions['api_id'  ];
            $params['text'    ] = $message;

            foreach (self::$smsReceivers as $receiver) {
               $params['to'] = $receiver;
               $url      = 'https://api.clickatell.com/http/sendmsg?'.http_build_query($params, null, '&');
               $request  = HttpRequest::create()->setUrl($url);
               $client   = CurlHttpClient::create($curlOptions);
               $response = $client->send($request);
               $status   = $response->getStatus();
               $content  = $response->getContent();

               if ($status != 200) {
                  try {
                     Logger::log('Unexpected HTTP status code '.$status.' ('.HttpResponse::$sc[$status].') for url: '.$request->getUrl(), L_WARN, ['class'=>__CLASS__, 'file'=>__FILE__, 'line'=>__LINE__]);
                  }
                  catch (\Exception $ex) {/*eat it*/}
                  continue;
               }
               if (strStartsWithI($content, 'ERR: 113')) {
                  // @TODO: 'ERR: 113' => max message parts exceeded, repeat with shortened message
                  // @TODO:               consider to send concatenated messages
               }
            }
            return;
         }
      }


      // (4) check availability and use Nexmo
      // @TODO encoding issues when sending to Bulgarian receivers (some chars are auto-converted to ciryllic crap)
      if (isSet(self::$smsOptions['nexmo'])) {
         $smsOptions = self::$smsOptions['nexmo'];
         if (!empty($smsOptions['api_key']) && !empty($smsOptions['api_secret'])) {
            $params = [];
            $params['api_key'   ] =        $smsOptions['api_key'   ];
            $params['api_secret'] =        $smsOptions['api_secret'];
            $params['from'      ] = !empty($smsOptions['from'      ]) ? $smsOptions['from'] : 'PHP Logger';
            $params['text'      ] =        $message;

            foreach (self::$smsReceivers as $receiver) {
               $params['to'] = $receiver;
               $url      = 'https://rest.nexmo.com/sms/json?'.http_build_query($params, null, '&');
               $request  = HttpRequest::create()->setUrl($url);
               $client   = CurlHttpClient::create($curlOptions);
               $response = $client->send($request);
               $status   = $response->getStatus();
               $content  = $response->getContent();
               if ($status != 200) {
                  try {
                     Logger::log('Unexpected HTTP status code '.$status.' ('.HttpResponse::$sc[$status].') for url: '.$request->getUrl(), L_WARN, ['class'=>__CLASS__, 'file'=>__FILE__, 'line'=>__LINE__]);
                  }
                  catch (\Exception $ex) {/*eat it*/}
                  continue;
               }
               if ($content === null) {
                  try {
                     Logger::log('Empty reply from server, url: '.$request->getUrl(), L_WARN, ['class'=>__CLASS__, 'file'=>__FILE__, 'line'=>__LINE__]);
                  }
                  catch (\Exception $ex) {/*eat it*/}
                  continue;
               }
            }
            return;
         }
      }
   }


   /**
    * Pass the message to the PHP default error log mechanism as defined by the PHP configuration value "error_log".
    *
    * ini_get('error_log')
    *
    * Name of the file where script errors should be logged. If the special value "syslog" is used, errors are sent
    * to the system logger instead. On Unix, this means syslog(3) and on Windows it means the event log.
    * If this directive is not set, errors are sent to the SAPI error logger. For example, it is an error log in Apache
    * or STDERR in CLI.
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context with additional data
    */
   private static function invokeErrorLogHandler($loggable, $level, array &$context) {
      if (!self::$errorLogHandler) return;
      if (!isSet($context['cliMessage']))
         self::composeCliMessage($loggable, $level, $context);

      $msg = 'PHP '.$context['cliMessage'];
      if (isSet($context['cliExtra']))
         $msg .= $context['cliExtra'];
      $msg = str_replace(chr(0), "?", $msg);                   // replace NUL bytes which mess up the logfile

      if (!ini_get('error_log') && CLI) {
         // Suppress duplicated output to STDERR, the PrintHandler already wrote to STDOUT.
         // Instead of messing around here the PrintHandler must not print to STDOUT if the ErrorLogHandler
         // is active and prints to STDERR.
         //
         // @TODO: suppress output to STDERR in interactive terminals only (i.e. not in cron)
      }
      else {
         error_log(trim($msg), ERROR_LOG_DEFAULT);
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
      if (!isSet($context['file']) || !isSet($context['line']))
         self::resolveLogLocation($loggable, $level, $context);
      $file = $context['file'];
      $line = $context['line'];

      $text = $extra = null;
      $indent = ' ';

      // compose message
      if (is_string($loggable)) {
         // simple message
         $msg  = $loggable;
         $text = '['.strToUpper(self::$logLevels[$level]).'] '.$msg.NL.$indent.'in '.$file.' on line '.$line.NL;
      }
      else {
         // exception
         $type = null;
         $msg  = trim(DebugHelper::getBetterMessage($loggable));
         if (isSet($context['unhandled'])) {
            $type = 'Unhandled ';
            if ($loggable instanceof PHPError) {
               $msg   = strRightFrom($msg, ':');
               $type .= 'PHP error:';
            }
         }
         $text = '['.strToUpper(self::$logLevels[$level]).'] '.$type.$msg.NL.$indent.'in '.$file.' on line '.$line.NL;

         // the stack trace will go into "cliExtra"
         $traceStr  = $indent.'Stacktrace:'.NL.' -----------'.NL;
         $traceStr .= DebugHelper::getBetterTraceAsString($loggable, $indent);
         $extra    .= NL.$traceStr;
      }

      // append an existing context exception to "cliExtra"
      if (isSet($context['exception'])) {
         $exception = $context['exception'];
         $msg       = $indent.trim(DebugHelper::getBetterMessage($exception));
         $extra    .= NL.$msg.NL;
         $traceStr  = $indent.'Stacktrace:'.NL.' -----------'.NL;
         $traceStr .= DebugHelper::getBetterTraceAsString($exception, $indent);
         $extra    .= NL.$traceStr;
      }

      // store main and extra message
      $context['cliMessage'] = $text;
      if ($extra)
         $context['cliExtra'] = $extra;
   }


   /**
    * Compose a mail log message and store it in the passed log context under the keys "mailSubject" and "mailMessage".
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context
    */
   private static function composeMailMessage($loggable, $level, array &$context) {
      if (!isSet($context['cliMessage']))
         self::composeCliMessage($loggable, $level, $context);

      $msg = $context['cliMessage'];
      if (isSet($context['cliExtra']))
         $msg .= $context['cliExtra'];
      $location = null;

      // compose message
      if (CLI) {
         $msg     .= NL.NL.'Shell:'.NL.'------'.NL.print_r(ksort_r($_SERVER), true).NL;
         $location = $_SERVER['PHP_SELF'];
      }
      else {
         $request  = Request::me();
         $location = strLeftTo($request->getUrl(), '?');
         $session  = $request->isSession() ? print_r(ksort_r($_SESSION), true) : null;
         $ip       = $_SERVER['REMOTE_ADDR'];
         $host     = getHostByAddr($ip);
         if ($host != $ip)
            $ip .= ' ('.$host.')';
         $msg .= NL.NL.'Request:'.NL.'--------'.NL.$request.NL.NL
              . 'Session: '.($session ? NL.'--------'.NL.$session : '(none)'.NL.'--------'.NL).NL.NL
              . 'Server:'.NL.'-------'.NL.print_r(ksort_r($_SERVER), true).NL.NL
              . 'IP:   '.$ip.NL
              . 'Time: '.date('Y-m-d H:i:s').NL;
      }
      $type = ($loggable instanceof \Exception && isSet($context['unhandled'])) ? 'Unhandled Exception ':'';

      // store subject and message
      $context['mailSubject'] = 'PHP ['.self::$logLevels[$level].'] '.$type.'at '.$location;
      $context['mailMessage'] = $msg;
   }


   /**
    * Compose a HTML log message and store it in the passed log context under the key "htmlMessage".
    *
    * @param  mixed    $loggable - message or exception to log
    * @param  int      $level    - loglevel of the loggable
    * @param  mixed[] &$context  - reference to the log context
    */
   private static function composeHtmlMessage($loggable, $level, array &$context) {
      if (!isSet($context['file']) || !isSet($context['line']))
         self::resolveLogLocation($loggable, $level, $context);
      $file = $context['file'];
      $line = $context['line'];

      // break out of unfortunate HTML tags
      $html   = '</script></img></select></textarea></font></span></div></i></b>';
      $html  .= '<div align="left" style="clear:both; z-index:65535; font:normal normal 12px/normal arial,helvetica,sans-serif">';
      $indent = ' ';

      // compose message
      if (is_string($loggable)) {
         // simple message
         $msg   = $loggable;
         $html .= '<b>['.strToUpper(self::$logLevels[$level]).']</b> '.nl2br(htmlSpecialChars($msg, ENT_QUOTES|ENT_SUBSTITUTE))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
      }
      else {
         // exception
         $type = null;
         $msg  = trim(DebugHelper::getBetterMessage($loggable));
         if (isSet($context['unhandled'])) {
            $type = 'Unhandled ';
            if ($loggable instanceof PHPError) {
               $msg   = strRightFrom($msg, ':');
               $type .= 'PHP error:';
            }
         }
         $html     .= '<b>['.strToUpper(self::$logLevels[$level]).']</b> '.nl2br(htmlSpecialChars($type.$msg, ENT_QUOTES|ENT_SUBSTITUTE))."<br>in <b>".$file.'</b> on line <b>'.$line.'</b><br>';
         $traceStr  = $indent.'Stacktrace:'.NL.' -----------'.NL;
         $traceStr .= DebugHelper::getBetterTraceAsString($loggable, $indent);
         $html     .= '<br>'.printPretty($traceStr, true).'<br>';
      }

      // append an existing context exception
      if (isSet($context['exception'])) {
         $exception = $context['exception'];
         $msg       = DebugHelper::getBetterMessage($exception);
         $html     .= '<br>'.nl2br(htmlSpecialChars($msg, ENT_QUOTES|ENT_SUBSTITUTE)).'<br>';
         $traceStr  = $indent.'Stacktrace:'.NL.' -----------'.NL;
         $traceStr .= DebugHelper::getBetterTraceAsString($exception, $indent);
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
      if (!isSet($context['trace']))
         $context['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
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
      if (!isSet($context['trace']))
         $context['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      $trace = $context['trace'];

      $class = '';
      foreach ($trace as $frame) {                 // find the frame calling this logger
         if (!isSet($frame['class']))              // logger was called from a non-class context
            break;
         if ($frame['class'] != __CLASS__) {       // logger was called from another class
            $function     = DebugHelper::getFQFunctionName($frame);
            $errorHandler = ErrorHandler::getErrorHandler();
            if ($function == $errorHandler)        // continue if the caller is the registered error handler
               continue;
            $class = $frame['class'];
            break;
         }
      }
      $context['class'] = $class;
   }
}
Logger::init();
