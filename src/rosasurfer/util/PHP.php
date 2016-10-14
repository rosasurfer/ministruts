<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;
use rosasurfer\debug\DebugHelper;
use rosasurfer\exception\IllegalTypeException;

use function rosasurfer\echoPre;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
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
   public static function shell_exec($cmd) {
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
    * Check PHP settings and call phpInfo().
    */
   public static function phpInfo() {
      $issues = [];

      if (PHP_VERSION_ID < 50421)                                                                     $issues[] = 'Warning: You are running a buggy PHP version: '.PHP_VERSION;
      if (!ini_get('short_open_tag'))                                                                 $issues[] = 'Warning: short_open_tag is not On';
      if (ini_get('expose_php'))                                                                      $issues[] = 'Warning: expose_php is not Off';
      if (ini_get('register_globals'))                                                                $issues[] = 'Warning: register_globals is not Off';
      if (ini_get('register_long_arrays'))                                                            $issues[] = 'Warning: register_long_arrays is not Off';
      if (!CLI && ini_get('register_argc_argv'))                                                      $issues[] = 'Warning: register_argc_argv is not Off';                                   // since v5.4 hardcoded to On for the CLI SAPI
      if (ini_get('variables_order') != 'GPCS')                                                       $issues[] = 'Warning: variables_order is not "GPCS": "'.ini_get('variables_order').'"';
      if (ini_get('request_order') != 'GP')                                                           $issues[] = 'Warning: request_order is not "GP": "'.ini_get('request_order').'"';
      if (ini_get('always_populate_raw_post_data'))                                                   $issues[] = 'Warning: always_populate_raw_post_data is not Off';
      if (!ini_get('auto_globals_jit'))                                                               $issues[] = 'Warning: auto_globals_jit is not On';
      if (ini_get('define_syslog_variables'))                                                         $issues[] = 'Warning: define_syslog_variables is not Off';
      if (ini_get('arg_separator.output') != '&amp;')                                                 $issues[] = 'Warning: arg_separator.output is not "&amp;": "'.ini_get('arg_separator.output').'"';
      if (ini_get('allow_url_include'))                                                               $issues[] = 'Warning: allow_url_include is not Off';
      if (!CLI && (int)ini_get('max_execution_time') != 30)                                           $issues[] = 'Warning: max_execution_time is not 30: '.ini_get('max_execution_time');    // since v5.4 hardcoded to 0 for the CLI SAPI
      if ((int)ini_get('default_socket_timeout') != 60)                                               $issues[] = 'Warning: default_socket_timeout is not 60: '.ini_get('default_socket_timeout');
      if (!CLI && ini_get('implicit_flush'))                                                          $issues[] = 'Warning: implicit_flush is not Off';                                       // since v5.4 hardcoded to On for the CLI SAPI
      if (ini_get('allow_call_time_pass_reference') && PHP_VERSION_ID < 50400) /*removed as of v5.4*/ $issues[] = 'Warning: allow_call_time_pass_reference is not Off';
      if (!ini_get('ignore_user_abort'))                                                              $issues[] = 'Warning: ignore_user_abort is not On';
      if (ini_get('session.save_handler') != 'files')                                                 $issues[] = 'Warning: session.save_handler is not "files": "'.ini_get('session.save_handler').'"';
      if (ini_get('session.serialize_handler') != 'php')                                              $issues[] = 'Warning: session.serialize_handler is not "php": "'.ini_get('session.serialize_handler').'"';
      if (ini_get('session.auto_start'))                                                              $issues[] = 'Warning: session.auto_start is not Off';
      if (!ini_get('session.use_cookies'))                                                            $issues[] = 'Warning: session.use_cookies is not On' ;
      if (!ini_get('session.cookie_httponly'))                                                        $issues[] = 'Warning: session.cookie_httponly is not On';
      if (ini_get('session.use_trans_sid'))                                                           $issues[] = 'Warning: session.use_trans_sid is not Off';
      if (ini_get('url_rewriter.tags') != 'a=href,area=href,frame=src,iframe=src,form=,fieldset=')    $issues[] = 'Warning: url_rewriter.tags is not "a=href,area=href,frame=src,iframe=src,form=,fieldset=": "'.ini_get('url_rewriter.tags').'"';
      if (ini_get('session.bug_compat_42'))                                                           $issues[] = 'Warning: session.bug_compat_42 is not Off';
      if (ini_get('session.bug_compat_42') && !ini_get('session.bug_compat_warn'))                    $issues[] = 'Warning: session.bug_compat_warn is not On';
      if (ini_get('session.referer_check') != '')                                                     $issues[] = 'Warning: session.referer_check is not "": "'.ini_get('session.referer_check').'"';
      if (ini_get('sql.safe_mode'))                                                                   $issues[] = 'Warning: sql.safe_mode is not Off';
      if (ini_get('magic_quotes_gpc'))                                      /*removed as of v5.4*/    $issues[] = 'Warning: magic_quotes_gpc is not Off';
      if (ini_get('magic_quotes_runtime'))                                  /*removed as of v5.4*/    $issues[] = 'Warning: magic_quotes_runtime is not Off';
      if (ini_get('magic_quotes_sybase'))                                                             $issues[] = 'Warning: magic_quotes_sybase is not Off';

      $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
      for ($i=0; $i < sizeOf($paths); ) {
         if (trim($paths[$i++]) == '')                                                                $issues[] = 'Warning: include_path contains empty path on position '.$i;
      }
      if (ini_get('default_mimetype') != 'text/html')                                                 $issues[] = 'Warning: default_mimetype is not "text/html": "'.ini_get('default_mimetype').'"';
      if (strToLower(ini_get('default_charset')) != 'utf-8')                                          $issues[] = 'Warning: default_charset is not "UTF-8": "'.ini_get('default_charset').'"';
      if (ini_get('file_uploads'))                                                                    $issues[] = 'Warning: file_uploads is not Off';

      if (ini_get('asp_tags'))                                                                        $issues[] = 'Warning: asp_tags is not Off';
      if (!ini_get('y2k_compliance') && PHP_VERSION_ID < 50400)             /*removed as of v5.4*/    $issues[] = 'Warning: y2k_compliance is not On';
      if (!strLen(ini_get('date.timezone')))                                                          $issues[] = 'Warning: date.timezone is not set';

      $current = (int) ini_get('error_reporting');
      $soll    = E_ALL & ~E_DEPRECATED | E_STRICT;
      if ($current & $soll != $soll)                                                                  $issues[] = 'Warning: error_reporting is not "E_ALL | E_STRICT": "'.DebugHelper::errorLevelToStr($current).'"';
      if (ini_get('ignore_repeated_errors'))                                                          $issues[] = 'Warning: ignore_repeated_errors is not Off';
      if (ini_get('ignore_repeated_source'))                                                          $issues[] = 'Warning: ignore_repeated_source is not Off';
      if (!ini_get('log_errors'))                                                                     $issues[] = 'Warning: log_errors is not On';
      if ((int) ini_get('log_errors_max_len') != 0)                                                   $issues[] = 'Warning: log_errors_max_len is not 0: '.ini_get('log_errors_max_len');
      if (!ini_get('track_errors'))                                                                   $issues[] = 'Warning: track_errors is not On';
      if (ini_get('html_errors'))                                                                     $issues[] = 'Warning: html_errors is not Off';

      if (ini_get('enable_dl'))                                                                       $issues[] = 'Warning: enable_dl is not Off';


      // extensions
      if (!extension_loaded('ctype'))                                                                 $issues[] = 'Warning: ctype extension is not loaded';
      if (!extension_loaded('curl'))                                                                  $issues[] = 'Warning: curl extension is not loaded';
      if (!extension_loaded('iconv'))                                                                 $issues[] = 'Warning: iconv extension is not loaded';
      if (!extension_loaded('json'))                                                                  $issues[] = 'Warning: JSON extension is not loaded';
      if (!extension_loaded('mysql'))                                                                 $issues[] = 'Warning: MySQL extension is not loaded';
      if (!extension_loaded('mysqli'))                                                                $issues[] = 'Warning: MySQLi extension is not loaded';
      if (!WINDOWS && !extension_loaded('sysvsem'))                                                   $issues[] = 'Warning: System-V Semaphore extension is not loaded';


      // Opcode cache
      if (extension_loaded('apc')) {
         if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7')                             $issues[] = 'Warning: You are running a buggy APC version (a version < 3.1.3 or >= 3.1.7 is recommended): '.phpVersion('apc');
         if (!ini_get('apc.enabled'))                                                                 $issues[] = 'Warning: apc.enabled is not On';                   // warning "Potential cache slam averted for key '...'" http://bugs.php.net/bug.php?id=58832
         if ( ini_get('apc.report_autofilter'))                                                       $issues[] = 'Warning: apc.report_autofilter is not Off';

         if (WINDOWS) {       // development
            if     (ini_get('apc.stat'))                                                              $issues[] = 'Warning: apc.stat is not Off';
            elseif (ini_get('apc.cache_by_default'))                                                  $issues[] = 'Warning: apc.cache_by_default is not Off';         // "On" may crash some Windows APC versions (apc-error: cannot redeclare class ***)
         }                                                                                                                                                                             // Windows: if apc.stat="Off" this option MUST be "Off"
         else {               // production
            if (!ini_get('apc.cache_by_default'))                                                     $issues[] = 'Warning: apc.cache_by_default is not On';
            if ( ini_get('apc.stat'))                                                                 $issues[] = 'Warning: apc.stat is not Off';                     // we want to cache fs-stat calls
            if (!ini_get('apc.write_lock'))                                                           $issues[] = 'Warning: apc.write_lock is not On';                // "Off" for perfomance; file modifications in production shall be disabled

            if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7') {
               if (ini_get('apc.include_once_override'))                                              $issues[] = 'Warning: apc.include_once_override is not Off';    // never use slow include_once()/require_once()
            }
            elseif (!ini_get('apc.include_once_override'))                                            $issues[] = 'Warning: apc.include_once_override is not On';
         }
      }
      elseif (extension_loaded('zend opcache')) {
         if (!ini_get('opcache.enable'))                                                              $issues[] = 'Warning: opcache.enable is not On';
      }
      else                                                                                            $issues[] = 'Warning: Opcode cache not found';


      // mail
      if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isSet($_SERVER['SERVER_ADMIN']))
                                                                                                      $issues[] = 'Warning: Windows - neither sendmail_path nor sendmail_from are set';
      if (!WINDOWS && !ini_get('sendmail_path'))                                                      $issues[] = 'Warning: sendmail_path is not set';
      if (!CLI && !isSet($_SERVER['SERVER_ADMIN']))                                                   $issues[] = 'Warning: email address $_SERVER["SERVER_ADMIN"] is not set';
      if (ini_get('mail.add_x_header'))                                                               $issues[] = 'Warning: mail.add_x_header is not Off';


      // error handling
      if (CLI || LOCALHOST) {
         if (!ini_get('display_errors'))                                                              $issues[] = 'Warning: display_errors is not On';
         if (!ini_get('display_startup_errors'))                                                      $issues[] = 'Warning: display_startup_errors is not On';
         if (!CLI && (int) ini_get('output_buffering') != 0)                                          $issues[] = 'Warning: output_buffering is enabled: '.ini_get('output_buffering');  // since v5.4 hardcoded to Off for the CLI SAPI
      }
      else {
         if (ini_get('display_errors'))                                                               $issues[] = 'Warning: display_errors is not Off';
         if (ini_get('display_startup_errors'))                                                       $issues[] = 'Warning: display_startup_errors is not Off';
         if (!CLI && (int) ini_get('output_buffering') == 0)                                          $issues[] = 'Warning: output_buffering is not enabled: '.ini_get('output_buffering');
      }


      // confirm if no issues found
      if ($issues) {
         echoPre(join(NL, $issues));
      }
      else {
         echoPre('Configuration OK');
      }


      // call phpInfo() if script runs on web server
      !CLI && phpInfo();
   }
}
