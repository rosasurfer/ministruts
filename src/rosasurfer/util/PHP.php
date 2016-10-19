<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;
use rosasurfer\debug\DebugHelper;
use rosasurfer\exception\IllegalTypeException;

use function rosasurfer\echoPre;
use function rosasurfer\strRight;
use function rosasurfer\strStartsWith;

use const rosasurfer\CLI;
use const rosasurfer\MB;
use const rosasurfer\NL;
use const rosasurfer\WINDOWS;


/**
 * PHP system-related functionality.
 */
class PHP extends StaticClass {


   /**
    * Trigger execution of the garbage collector.
    */
   public static function collectGarbage() {
      $wasEnabled = gc_enabled();
      !$wasEnabled && gc_enable();

      gc_collect_cycles();

      !$wasEnabled && gc_disable();
   }


   /**
    * Execute a shell command in a cross-platform compatible way and return STDOUT. Works around a Windows bug where
    * a DOS EOF character (0x1A = ASCII 26) in the STDOUT stream causes further reading to stop.
    *
    * @param  string $cmd - shell command to execute
    *
    * @return string - content of STDOUT
    */
   public static function shellExec($cmd) {
      if (!is_string($cmd)) throw new IllegalTypeException('Illegal type of parameter $cmd: '.getType($cmd));

      if (!WINDOWS) return \shell_exec($cmd);

      // pOpen() suffers from the same bug, probably caused by both using feof()

      $descriptors = [0 => ['pipe', 'rb'],         // stdin
                      1 => ['pipe', 'wb'],         // stdout
                      2 => ['pipe', 'wb']];        // stderr
      $pipes = [];
      $hProc = proc_open($cmd, $descriptors, $pipes, null, null, ['bypass_shell'=>true]);

      $stdout = stream_get_contents($pipes[1]);    // $pipes now looks like this:
      fClose($pipes[0]);                           // 0 => writeable handle connected to child stdin
      fClose($pipes[1]);                           // 1 => readable handle connected to child stdout
      fClose($pipes[2]);                           // 2 => readable handle connected to child stderr
      proc_close($hProc);                          // we MUST close the pipes before proc_close() to avoid a deadlock

      return $stdout;
   }


   /**
    * Check PHP settings, display issues and call phpInfo().
    *
    * PHP_INI_ALL    - entry can be set anywhere
    * PHP_INI_USER   - entry can be set in scripts
    * PHP_INI_ONLY   - entry can be set in php.ini
    * PHP_INI_SYSTEM - entry can be set in php.ini or in httpd.conf
    * PHP_INI_PERDIR - entry can be set in php.ini, httpd.conf or in .htaccess
    */
   public static function info() {
      $issues = [];

      // (1) core configuration
      // ----------------------
      /*PHP_INI_PERDIR*/ if (!self::ini_get_bool('short_open_tag'                ))                               $issues[] = 'Error: short_open_tag is not On  [security]';
      /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('asp_tags'                      ) && PHP_VERSION_ID <  70000)    $issues[] = 'Info:  asp_tags is not Off  [code quality]';
      /*PHP_INI_ONLY  */ if ( self::ini_get_bool('expose_php'                    ))                               $issues[] = 'Warn:  expose_php is not Off  [security]';
      /*PHP_INI_ALL   */ if ( self::ini_get_int ('max_execution_time'            ) != 30 && !CLI/*hardcoded*/)    $issues[] = 'Info:  max_execution_time is not 30: '.ini_get('max_execution_time').'  [resources]';
      /*PHP_INI_ALL   */ if ( self::ini_get_int ('default_socket_timeout'        ) != 60)                         $issues[] = 'Info:  default_socket_timeout is not 60: '.ini_get('default_socket_timeout').'  [resources]';
      /*PHP_INI_ALL   */ $bytes = self::ini_get_bytes('memory_limit'             );
         if      ($bytes ==     -1)                                                                               $issues[] = 'Warn:  memory_limit is unlimited  [resources]';
         else if ($bytes <=      0)                                                                               $issues[] = 'Error: memory_limit is invalid: '.ini_get('memory_limit');
         else if ($bytes <  4 * MB)                                                                               $issues[] = 'Info:  memory_limit is very low: '.ini_get('memory_limit').'  [resources]';
         else if ($bytes > 32 * MB)                                                                               $issues[] = 'Info:  memory_limit is very high: '.ini_get('memory_limit').'  [resources]';
      /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('register_globals'              ) && PHP_VERSION_ID <  50400)    $issues[] = 'Error: register_globals is not Off  [security]';
      /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('register_long_arrays'          ) && PHP_VERSION_ID <  50400)    $issues[] = 'Info:  register_long_arrays is not Off  [performance]';
      /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('register_argc_argv'            ) && !CLI/*hardcoded*/)          $issues[] = 'Info:  register_argc_argv is not Off  [performance]';
      /*PHP_INI_PERDIR*/ if (!self::ini_get_bool('auto_globals_jit'              ))                               $issues[] = 'Info:  auto_globals_jit is not On  [performance]';
      /*PHP_INI_ALL   */ if ( self::ini_get_bool('define_syslog_variables'       ) && PHP_VERSION_ID <  50400)    $issues[] = 'Info:  define_syslog_variables is not Off  [performance]';
      /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('allow_call_time_pass_reference') && PHP_VERSION_ID <  50400)    $issues[] = 'Info:  allow_call_time_pass_reference is not Off  [code quality]';
      /*PHP_INI_ALL   */ if (!self::ini_get_bool('y2k_compliance'                ) && PHP_VERSION_ID <  50400)    $issues[] = 'Info:  y2k_compliance is not On  [functionality]';
      /*PHP_INI_ALL   */ $timezone = ini_get    ('date.timezone'                 );
         if (empty($timezone) && (!isSet($_ENV['TZ'])                              || PHP_VERSION_ID >= 50400))   $issues[] = 'Warn:  date.timezone is not set  [functionality]';
      /*PHP_INI_SYSTEM*/ if ( self::ini_get_bool('safe_mode'                     ) && PHP_VERSION_ID <  50400)    $issues[] = 'Info:  safe_mode is not Off  [performance]';
      /*PHP_INI_ALL   */ if (!empty(ini_get     ('open_basedir'                  )))                              $issues[] = 'Info:  open_basedir is not empty: "'.ini_get('open_basedir').'"  [performance]';
      /*PHP_INI_ALL   */ if (!self::ini_get_bool('auto_detect_line_endings'      ))                               $issues[] = 'Info:  auto_detect_line_endings is not On  [funtionality]';
      /*PHP_INI_SYSTEM*/ if (!self::ini_get_bool('allow_url_fopen'               ))                               $issues[] = 'Info:  allow_url_fopen is not On  [functionality]';
      /*PHP_INI_SYSTEM*/ if ( self::ini_get_bool('allow_url_include'))                                            $issues[] = 'Error: allow_url_include is not Off  [security]';
      /*PHP_INI_ALL   */ foreach (explode(PATH_SEPARATOR, ini_get('include_path' )) as $path) {
         if (!strLen($path)) {                                                                                    $issues[] = 'Warn:  include_path contains empty path: "'.ini_get('include_path').'"  [functionality]';
            break;
      }}


      // (2) error handling
      // ------------------                                                                                       /* E_STRICT =  2048 =    100000000000            */
      /*PHP_INI_ALL   */ $current = self::ini_get_int('error_reporting');                                         /* E_ALL    = 30719 = 111011111111111  (PHP 5.3) */
      $target = E_ALL|E_STRICT;                                                                                   /* E_ALL    = 32767 = 111111111111111  (PHP 5.4) */
      if ($notCovered=($target ^ $current) & $target)                                                             $issues[] = 'Warn:  error_reporting does not cover '.DebugHelper::errorLevelToStr($notCovered).'  [code quality]';
      if (WINDOWS) {/*always development*/
         /*PHP_INI_ALL*/ if (!self::ini_get_bool('display_errors'                )) /*bool|string:stderr*/        $issues[] = 'Info:  display_errors is not On  [functionality]';
         /*PHP_INI_ALL*/ if (!self::ini_get_bool('display_startup_errors'        ))                               $issues[] = 'Info:  display_startup_errors is not On  [functionality]';
      }
      else {
         /*PHP_INI_ALL*/ if ( self::ini_get_bool('display_errors'                )) /*bool|string:stderr*/        $issues[] = 'Warn:  display_errors is not Off  [security]';
         /*PHP_INI_ALL*/ if ( self::ini_get_bool('display_startup_errors'        ))                               $issues[] = 'Warn:  display_startup_errors is not Off  [security]';
      }
      /*PHP_INI_ALL   */ if ( self::ini_get_bool('ignore_repeated_errors'        ))                               $issues[] = 'Info:  ignore_repeated_errors is not Off  [resources]';
      /*PHP_INI_ALL   */ if ( self::ini_get_bool('ignore_repeated_source'        ))                               $issues[] = 'Info:  ignore_repeated_source is not Off  [resources]';
      /*PHP_INI_ALL   */ if (!self::ini_get_bool('track_errors'                  ))                               $issues[] = 'Info:  track_errors is not On  [functionality]';
      /*PHP_INI_ALL   */ if ( self::ini_get_bool('html_errors'                   ))                               $issues[] = 'Warn:  html_errors is not Off  [functionality]';
      /*PHP_INI_ALL   */ if (!self::ini_get_bool('log_errors'                    ))                               $issues[] = 'Error: log_errors is not On  [code quality]';
      /*PHP_INI_ALL   */ $bytes = self::ini_get_bytes('log_errors_max_len'       );
         if      ($bytes===null || $bytes < 0)                                                                    $issues[] = 'Error: log_errors_max_len is invalid: '.ini_get('log_errors_max_len');
         else if ($bytes != 0) /*'log_errors_max_len' doesn't affect the function error_log()*/                   $issues[] = 'Warn:  log_errors_max_len is not 0: '.ini_get('log_errors_max_len').'  [functionality]';
      /*PHP_INI_ALL   */ $errorLog = ini_get('error_log');
      if (!empty($errorLog) && $errorLog!='syslog') {
         if (is_file($errorLog)) {
            $hFile = @fOpen($errorLog, 'ab');         // try to open
            if (is_resource($hFile)) fClose($hFile);
            else                                                                                                  $issues[] = 'Error: error_log "'.$errorLog.'" is not writable  [infrastructure]';
         }
         else {
            $hFile = @fOpen($errorLog, 'wb');         // try to create
            if (is_resource($hFile)) fClose($hFile);
            else                                                                                                  $issues[] = 'Error: error_log "'.$errorLog.'" is not writable  [infrastructure]';
            is_file($errorLog) && @unlink($errorLog);
         }
      }


      // (3) input sanitizing
      // --------------------
      if (PHP_VERSION_ID < 50400) {
         /*PHP_INI_ALL   */ if (self::ini_get_bool('magic_quotes_sybase'  )) /*overrides 'magic_quotes_gpc'*/     $issues[] = 'Error: magic_quotes_sybase is not Off  [input]';
         /*PHP_INI_PERDIR*/ else if (self::ini_get_bool('magic_quotes_gpc'))                                      $issues[] = 'Error: magic_quotes_gpc is not Off  [input]';
         /*PHP_INI_ALL   */ if (self::ini_get_bool('magic_quotes_runtime' ))                                      $issues[] = 'Error: magic_quotes_runtime is not Off  [input]';
      }
      /*PHP_INI_SYSTEM*/ if ( self::ini_get_bool('sql.safe_mode'          ))                                      $issues[] = 'Warn:  sql.safe_mode is not Off  [functionality]';


      // (4) request & HTML handling
      // ---------------------------
      /*PHP_INI_PERDIR*/ $order = ini_get('request_order'); /*overrides order of GPC in 'variables_order'*/
      if (empty($order)) {
         /*PHP_INI_PERDIR*/ $order = ini_get('variables_order');
         $newOrder = '';
         $len      = strLen($order);
         for ($i=0; $i < $len; $i++) {
            if (in_array($char=$order[$i], ['G','P','C']))
               $newOrder .= $char;
         }
         $order = $newOrder;
      }                  if ($order != 'GP')                                                                      $issues[] = 'Error: request_order is not "GP": "'.$order.'"  [functionality]';
      /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('always_populate_raw_post_data' ) && PHP_VERSION_ID <  70000)    $issues[] = 'Info:  always_populate_raw_post_data is not Off  [performance]';
      /*PHP_INI_ALL   */ if (       ini_get     ('arg_separator.output'          ) != '&amp;')                    $issues[] = 'Info:  arg_separator.output is not "&amp;": "'.ini_get('arg_separator.output').'"  [functionality]';
      /*PHP_INI_ALL   */ if (!self::ini_get_bool('ignore_user_abort'             ))                               $issues[] = 'Warn:  ignore_user_abort is not On  [functionality]';
      /*PHP_INI_SYSTEM*/ if ( self::ini_get_bool('file_uploads'                  )) {                             $issues[] = 'Info:  file_uploads is not Off  [security]';
         // TODO: check "upload_tmp_dir"
      }
      /*PHP_INI_ALL   */ if (            ini_get('default_mimetype'              )  != 'text/html')               $issues[] = 'Info:  default_mimetype is not "text/html": "'.ini_get('default_mimetype').'"  [functionality]';
      /*PHP_INI_ALL   */ if ( strToLower(ini_get('default_charset'               )) != 'utf-8')                   $issues[] = 'Info:  default_charset is not "UTF-8": "'.ini_get('default_charset').'"  [functionality]';
      /*PHP_INI_ALL   */ if ( self::ini_get_bool('implicit_flush'                ) && !CLI/*hardcoded*/)          $issues[] = 'Warn:  implicit_flush is not Off  [performance]';
      /*PHP_INI_PERDIR*/ $buffer = self::ini_get_bytes('output_buffering'        );
      if (!CLI) {
         if      ($buffer===null || $buffer < 0)                                                                  $issues[] = 'Error: output_buffering is invalid: '.ini_get('output_buffering');
         else if (!$buffer)                                                                                       $issues[] = 'Info:  output_buffering is not enabled  [performance]';
      }
      // TODO: /*PHP_INI_ALL*/ "zlib.output_compression"














      // (5) session related
      // -------------------
      /*PHP_INI_ALL   */ if (ini_get('session.save_handler') != 'files')                                                  $issues[] = 'Warn:  session.save_handler is not "files": "'.ini_get('session.save_handler').'"';
      // TODO: check "session.save_path"
      /*PHP_INI_ALL   */ if (ini_get('session.serialize_handler') != 'php')                                               $issues[] = 'Warn:  session.serialize_handler is not "php": "'.ini_get('session.serialize_handler').'"';
      /*PHP_INI_PERDIR*/ if (ini_get('session.auto_start'))                                                               $issues[] = 'Warn:  session.auto_start is not Off';
      /*PHP_INI_ALL   */ if (!ini_get('session.use_cookies'))                                                             $issues[] = 'Warn:  session.use_cookies is not On' ;
      /*PHP_INI_ALL   */ if (!ini_get('session.cookie_httponly'))                                                         $issues[] = 'Warn:  session.cookie_httponly is not On';
      /*PHP_INI_ALL   */ if (ini_get('session.use_trans_sid'))                                                            $issues[] = 'Warn:  session.use_trans_sid is not Off';
      /*PHP_INI_ALL   */ if (ini_get('session.bug_compat_42'))                                       /*removed since 5.4*/$issues[] = 'Warn:  session.bug_compat_42 is not Off';
      /*PHP_INI_ALL   */ if (ini_get('session.bug_compat_42') && !ini_get('session.bug_compat_warn'))/*removed since 5.4*/$issues[] = 'Warn:  session.bug_compat_warn is not On';
      /*PHP_INI_ALL   */ if (ini_get('session.referer_check') != '')                                                      $issues[] = 'Warn:  session.referer_check is not "": "'.ini_get('session.referer_check').'"';
      /*PHP_INI_ALL   */ if (ini_get('url_rewriter.tags') != 'a=href,area=href,frame=src,iframe=src,form=,fieldset=')     $issues[] = 'Warn:  url_rewriter.tags is not "a=href,area=href,frame=src,iframe=src,form=,fieldset=": "'.ini_get('url_rewriter.tags').'"';


      // (6) mail related
      // ----------------
      /*PHP_INI_ALL   */ //sendmail_from
      if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isSet($_SERVER['SERVER_ADMIN']))          $issues[] = 'Warn:  Windows - neither sendmail_path nor sendmail_from are set';
      /*PHP_INI_SYSTEM*/ if (!WINDOWS && !ini_get('sendmail_path'))                                                       $issues[] = 'Warn:  sendmail_path is not set';
      /*PHP_INI_PERDIR*/ if (ini_get('mail.add_x_header'))                                                                $issues[] = 'Warn:  mail.add_x_header is not Off';


      // (7) extensions
      // --------------
      /*PHP_INI_SYSTEM*/ if (ini_get('enable_dl'))                                                                        $issues[] = 'Warn:  enable_dl is not Off';
      if (!extension_loaded('ctype'))                                                                                     $issues[] = 'Warn:  ctype extension is not loaded';
      if (!extension_loaded('curl'))                                                                                      $issues[] = 'Warn:  curl extension is not loaded';
      if (!extension_loaded('iconv'))                                                                                     $issues[] = 'Warn:  iconv extension is not loaded';
      if (!extension_loaded('json'))                                                                                      $issues[] = 'Warn:  JSON extension is not loaded';
      if (!extension_loaded('mysql'))                                                                                     $issues[] = 'Warn:  MySQL extension is not loaded';
      if (!extension_loaded('mysqli'))                                                                                    $issues[] = 'Warn:  MySQLi extension is not loaded';
      if (!WINDOWS && !extension_loaded('sysvsem'))                                                                       $issues[] = 'Warn:  System-V Semaphore extension is not loaded';


      // (8) Opcode cache
      // ----------------
      //if (extension_loaded('apc')) {
      //   if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7')                                                 $issues[] = 'Warn:  You are running a buggy APC version (a version < 3.1.3 or >= 3.1.7 is recommended): '.phpVersion('apc');
      //   /*PHP_INI_SYSTEM*/ if (!ini_get('apc.enabled'))                                                                  $issues[] = 'Warn:  apc.enabled is not On';                   // warning "Potential cache slam averted for key '...'" http://bugs.php.net/bug.php?id=58832
      //   /*PHP_INI_SYSTEM*/ if ( ini_get('apc.report_autofilter'))                                                        $issues[] = 'Warn:  apc.report_autofilter is not Off';
      //
      //   if (WINDOWS) {       // development
      //      /*PHP_INI_SYSTEM*/ if     (ini_get('apc.stat'))                                                               $issues[] = 'Warn:  apc.stat is not Off';
      //      /*PHP_INI_ALL   */ elseif (ini_get('apc.cache_by_default'))                                                   $issues[] = 'Warn:  apc.cache_by_default is not Off';         // "On" may crash some Windows APC versions (apc-error: cannot redeclare class ***)
      //   }                                                                                                                                                                              // Windows: if apc.stat="Off" this option MUST be "Off"
      //   else {               // production
      //      /*PHP_INI_ALL   */ if (!ini_get('apc.cache_by_default'))                                                      $issues[] = 'Warn:  apc.cache_by_default is not On';
      //      /*PHP_INI_SYSTEM*/ if ( ini_get('apc.stat'))                                                                  $issues[] = 'Warn:  apc.stat is not Off';                     // we want to cache fs-stat calls
      //      /*PHP_INI_SYSTEM*/ if (!ini_get('apc.write_lock'))                                                            $issues[] = 'Warn:  apc.write_lock is not On';                // "Off" for perfomance; file modifications in production shall be disabled
      //
      //      if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7') {
      //         /*PHP_INI_SYSTEM*/ if (ini_get('apc.include_once_override'))                                               $issues[] = 'Warn:  apc.include_once_override is not Off';    // never use slow include_once()/require_once()
      //      }
      //      /*PHP_INI_SYSTEM*/ elseif (!ini_get('apc.include_once_override'))                                             $issues[] = 'Warn:  apc.include_once_override is not On';
      //   }
      //}
      //elseif (extension_loaded('zend opcache')) {
      //   /*PHP_INI_ALL   */ if (!ini_get('opcache.enable'))                                                               $issues[] = 'Warn:  opcache.enable is not On';
      //}
      //else                                                                                                                $issues[] = 'Warn:  Opcode cache not found';





      // (9) show issues or confirm if none are found
      if ($issues) echoPre('PHP configuration issues:'.NL.'-------------------------'.NL.join(NL, $issues));
      else         echoPre('PHP configuration OK');


      // (10) call phpInfo() if we run via a web server
      !CLI && phpInfo();
   }


   /**
    * Return the value of a php.ini option as a boolean.
    *
    * @param  string $option
    *
    * @return bool
    */
   private static function ini_get_bool($option) {
      $value = ini_get($option);

      switch (strToLower($value)) {
         case 'on'   :
         case 'true' :
         case 'yes'  : return true;
         case 'off'  :
         case 'false':
         case 'no'   :
         case 'none' : return false;
      }
      return (bool)(int)$value;
   }


   /**
    * Return the value of a php.ini option as an integer.
    *
    * @param  string $option
    *
    * @return int
    */
   private static function ini_get_int($option) {
      return (int)ini_get($option);
   }


   /**
    * Return the value of a php.ini option as a byte value.
    *
    * @param  string $option
    *
    * @return int
    */
   private static function ini_get_bytes($option) {
      $sValue = $value = ini_get($option);

      if (!strLen($value))     return null;
      if (ctype_digit($value)) return (int)$value;

      $sign = 1;
      if (strStartsWith($value, '-')) {
         $sign   = -1;
         $sValue = strRight($value, -1);
         if (!strLen($sValue))     return null;
         if (ctype_digit($sValue)) return $sign * (int)$sValue;
      }

      $factor = 1;
      switch (strToLower(strRight($sValue, 1))) {
         case 'k':
            $factor = 1024;
            break;
         case 'm':
            $factor = 1024 * 1024;
            break;
         case 'g':
            $factor = 1024 * 1024 * 1024;
            break;
         default:
            return null;
      }

      $sValue = strLeft($sValue, -1);
      if (!strLen($sValue))     return null;
      if (ctype_digit($sValue)) return $sign * (int)$sValue * $factor;
      return null;
   }
}