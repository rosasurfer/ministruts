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

      // core configuration
      // ------------------
      /*PHP_INI_PERDIR*/ if (!ini_get('short_open_tag'))                                                                  $issues[] = 'Warn: short_open_tag is not On';
      /*PHP_INI_PERDIR*/ if ( ini_get('asp_tags'))                                                                        $issues[] = 'Warn: asp_tags is not Off';
      /*PHP_INI_ALL   */ if ( ini_get('max_execution_time') != '30' && !CLI)                                              $issues[] = 'Warn: max_execution_time is not 30: '.ini_get('max_execution_time');    // since v5.4 hardcoded to 0 for the CLI SAPI
      /*PHP_INI_ALL   */ if ( ini_get('default_socket_timeout') != '60')                                                  $issues[] = 'Warn: default_socket_timeout is not 60: '.ini_get('default_socket_timeout');
      /*PHP_INI_ONLY  */ if ( ini_get('expose_php'))                                                                      $issues[] = 'Warn: expose_php is not Off';
      /*PHP_INI_PERDIR*/ if ( ini_get('register_globals'))                                           /*removed since 5.4*/$issues[] = 'Warn: register_globals is not Off';
      /*PHP_INI_PERDIR*/ if ( ini_get('register_long_arrays'))                                       /*removed since 5.4*/$issues[] = 'Warn: register_long_arrays is not Off';
      /*PHP_INI_PERDIR*/ if ( ini_get('register_argc_argv') && !CLI)                                                      $issues[] = 'Warn: register_argc_argv is not Off';                                   // since v5.4 hardcoded to On for the CLI SAPI
      /*PHP_INI_PERDIR*/ if (!ini_get('auto_globals_jit'))                                                                $issues[] = 'Warn: auto_globals_jit is not On';
      /*PHP_INI_ALL   */ if ( ini_get('define_syslog_variables'))                                    /*removed since 5.4*/$issues[] = 'Warn: define_syslog_variables is not Off';
      /*PHP_INI_ALL   */ if ( ini_get('implicit_flush') && !CLI)                                                          $issues[] = 'Warn: implicit_flush is not Off';                                       // since v5.4 hardcoded to On for the CLI SAPI
      /*PHP_INI_PERDIR*/ if ( ini_get('allow_call_time_pass_reference'))                             /*removed since 5.4*/$issues[] = 'Warn: allow_call_time_pass_reference is not Off';
      /*PHP_INI_ALL   */ if (!ini_get('y2k_compliance'))                                             /*removed since 5.4*/$issues[] = 'Warn: y2k_compliance is not On';
      /*PHP_INI_ALL   */ if ( ini_get('date.timezone') == '')                                                             $issues[] = 'Warn: date.timezone is not set';
      /*PHP_INI_SYSTEM*/ if ( ini_get('allow_url_include'))                                                               $issues[] = 'Warn: allow_url_include is not Off';
      /*PHP_INI_ALL   */ $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
      for ($i=0; $i < sizeOf($paths); ) {
         if (trim($paths[$i++]) == '')                                                                                    $issues[] = 'Warn: include_path contains empty path on position '.$i;
      }
      /*PHP_INI_SYSTEM*/ if (ini_get('safe_mode') && PHP_VERSION_ID < 50400)                         /*removed since 5.4*/$issues[] = 1|echoPre('Warn: safe_mode is not Off');
      /*PHP_INI_ALL   */ if (ini_get('auto_detect_line_endings'))                                                         $issues[] = 1|echoPre('Warn: auto_detect_line_endings is not Off');
      /*PHP_INI_SYSTEM*/ if (ini_get('allow_url_fopen'))                                                                  $issues[] = 1|echoPre('Warn: allow_url_fopen is not Off');
      /*PHP_INI_ALL   */ if (ini_get('memory_limit'))                                                                     $issues[] = 1|echoPre('Warn: memory_limit is not Off');


      // error handling
      // --------------
      if (CLI || LOCALHOST) {
         /*PHP_INI_ALL   */ if (!ini_get('display_errors'))                                                               $issues[] = 'Warn: display_errors is not On';
         /*PHP_INI_ALL   */ if (!ini_get('display_startup_errors'))                                                       $issues[] = 'Warn: display_startup_errors is not On';
      }
      else {
         /*PHP_INI_ALL   */ if (ini_get('display_errors'))                                                                $issues[] = 'Warn: display_errors is not Off';
         /*PHP_INI_ALL   */ if (ini_get('display_startup_errors'))                                                        $issues[] = 'Warn: display_startup_errors is not Off';
      }
      /*PHP_INI_ALL   */ $current = (int) ini_get('error_reporting');
      $target = E_ALL & ~E_DEPRECATED | E_STRICT;
      if ($current & $target != $target)                                                                                  $issues[] = 'Warn: error_reporting is not "E_ALL | E_STRICT": "'.DebugHelper::errorLevelToStr($current).'"';
      /*PHP_INI_ALL   */ if (ini_get('ignore_repeated_errors'))                                                           $issues[] = 'Warn: ignore_repeated_errors is not Off';
      /*PHP_INI_ALL   */ if (ini_get('ignore_repeated_source'))                                                           $issues[] = 'Warn: ignore_repeated_source is not Off';
      /*PHP_INI_ALL   */ if (!ini_get('log_errors'))                                                                      $issues[] = 'Warn: log_errors is not On';
      /*PHP_INI_ALL   */ if ((int) ini_get('log_errors_max_len') != 0)                                                    $issues[] = 'Warn: log_errors_max_len is not 0: '.ini_get('log_errors_max_len');
      /*PHP_INI_ALL   */ if (!ini_get('track_errors'))                                                                    $issues[] = 'Warn: track_errors is not On';
      /*PHP_INI_ALL   */ if (ini_get('html_errors'))                                                                      $issues[] = 'Warn: html_errors is not Off';


      // input sanitizing
      // ----------------
      /*PHP_INI_PERDIR*/ if (ini_get('magic_quotes_gpc'))                                            /*removed since 5.4*/$issues[] = 'Warn: magic_quotes_gpc is not Off';
      /*PHP_INI_ALL   */ if (ini_get('magic_quotes_runtime'))                                        /*removed since 5.4*/$issues[] = 'Warn: magic_quotes_runtime is not Off';
      /*PHP_INI_ALL   */ if (ini_get('magic_quotes_sybase'))                                         /*removed since 5.4*/$issues[] = 'Warn: magic_quotes_sybase is not Off';
      /*PHP_INI_SYSTEM*/ if (ini_get('sql.safe_mode'))                                                                    $issues[] = 'Warn: sql.safe_mode is not Off';


      // request & HTML handling
      // -----------------------
      /*PHP_INI_PERDIR*/ if (ini_get('variables_order') != 'GPCS')                                                        $issues[] = 'Warn: variables_order is not "GPCS": "'.ini_get('variables_order').'"';
      /*PHP_INI_PERDIR*/ if (ini_get('request_order') != 'GP')                                                            $issues[] = 'Warn: request_order is not "GP": "'.ini_get('request_order').'"';
      /*PHP_INI_PERDIR*/ if (ini_get('always_populate_raw_post_data'))                                                    $issues[] = 'Warn: always_populate_raw_post_data is not Off';
      /*PHP_INI_ALL   */ if (ini_get('arg_separator.output') != '&amp;')                                                  $issues[] = 'Warn: arg_separator.output is not "&amp;": "'.ini_get('arg_separator.output').'"';
      /*PHP_INI_ALL   */ if (!ini_get('ignore_user_abort'))                                                               $issues[] = 'Warn: ignore_user_abort is not On';
      /*PHP_INI_ALL   */ if (ini_get('default_mimetype') != 'text/html')                                                  $issues[] = 'Warn: default_mimetype is not "text/html": "'.ini_get('default_mimetype').'"';
      /*PHP_INI_ALL   */ if (strToLower(ini_get('default_charset')) != 'utf-8')                                           $issues[] = 'Warn: default_charset is not "UTF-8": "'.ini_get('default_charset').'"';
      /*PHP_INI_SYSTEM*/ if (ini_get('file_uploads'))                                                                     $issues[] = 'Warn: file_uploads is not Off';
      /*PHP_INI_PERDIR*/ if (!CLI && (int) ini_get('output_buffering') != 0)                                              $issues[] = 'Warn: output_buffering is enabled: '.ini_get('output_buffering');  // since v5.4 hardcoded to Off for the CLI SAPI
      /*PHP_INI_ALL   */ //zlib.output_compression = Off


      // session related
      // ---------------
      /*PHP_INI_ALL   */ if (ini_get('session.save_handler') != 'files')                                                  $issues[] = 'Warn: session.save_handler is not "files": "'.ini_get('session.save_handler').'"';
      /*PHP_INI_ALL   */ if (ini_get('session.serialize_handler') != 'php')                                               $issues[] = 'Warn: session.serialize_handler is not "php": "'.ini_get('session.serialize_handler').'"';
      /*PHP_INI_PERDIR*/ if (ini_get('session.auto_start'))                                                               $issues[] = 'Warn: session.auto_start is not Off';
      /*PHP_INI_ALL   */ if (!ini_get('session.use_cookies'))                                                             $issues[] = 'Warn: session.use_cookies is not On' ;
      /*PHP_INI_ALL   */ if (!ini_get('session.cookie_httponly'))                                                         $issues[] = 'Warn: session.cookie_httponly is not On';
      /*PHP_INI_ALL   */ if (ini_get('session.use_trans_sid'))                                                            $issues[] = 'Warn: session.use_trans_sid is not Off';
      /*PHP_INI_ALL   */ if (ini_get('session.bug_compat_42'))                                       /*removed since 5.4*/$issues[] = 'Warn: session.bug_compat_42 is not Off';
      /*PHP_INI_ALL   */ if (ini_get('session.bug_compat_42') && !ini_get('session.bug_compat_warn'))/*removed since 5.4*/$issues[] = 'Warn: session.bug_compat_warn is not On';
      /*PHP_INI_ALL   */ if (ini_get('session.referer_check') != '')                                                      $issues[] = 'Warn: session.referer_check is not "": "'.ini_get('session.referer_check').'"';
      /*PHP_INI_ALL   */ if (ini_get('url_rewriter.tags') != 'a=href,area=href,frame=src,iframe=src,form=,fieldset=')     $issues[] = 'Warn: url_rewriter.tags is not "a=href,area=href,frame=src,iframe=src,form=,fieldset=": "'.ini_get('url_rewriter.tags').'"';


      // mail related
      // ------------
      /*PHP_INI_ALL   */ //sendmail_from
      if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isSet($_SERVER['SERVER_ADMIN']))          $issues[] = 'Warn: Windows - neither sendmail_path nor sendmail_from are set';
      /*PHP_INI_SYSTEM*/ if (!WINDOWS && !ini_get('sendmail_path'))                                                       $issues[] = 'Warn: sendmail_path is not set';
      /*PHP_INI_PERDIR*/ if (ini_get('mail.add_x_header'))                                                                $issues[] = 'Warn: mail.add_x_header is not Off';


      // extensions
      // ----------
      /*PHP_INI_SYSTEM*/ if (ini_get('enable_dl'))                                                                        $issues[] = 'Warn: enable_dl is not Off';
      if (!extension_loaded('ctype'))                                                                                     $issues[] = 'Warn: ctype extension is not loaded';
      if (!extension_loaded('curl'))                                                                                      $issues[] = 'Warn: curl extension is not loaded';
      if (!extension_loaded('iconv'))                                                                                     $issues[] = 'Warn: iconv extension is not loaded';
      if (!extension_loaded('json'))                                                                                      $issues[] = 'Warn: JSON extension is not loaded';
      if (!extension_loaded('mysql'))                                                                                     $issues[] = 'Warn: MySQL extension is not loaded';
      if (!extension_loaded('mysqli'))                                                                                    $issues[] = 'Warn: MySQLi extension is not loaded';
      if (!WINDOWS && !extension_loaded('sysvsem'))                                                                       $issues[] = 'Warn: System-V Semaphore extension is not loaded';


      // Opcode cache
      // ------------
      if (extension_loaded('apc')) {
         if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7')                                                 $issues[] = 'Warn: You are running a buggy APC version (a version < 3.1.3 or >= 3.1.7 is recommended): '.phpVersion('apc');
         /*PHP_INI_SYSTEM*/ if (!ini_get('apc.enabled'))                                                                  $issues[] = 'Warn: apc.enabled is not On';                   // warning "Potential cache slam averted for key '...'" http://bugs.php.net/bug.php?id=58832
         /*PHP_INI_SYSTEM*/ if ( ini_get('apc.report_autofilter'))                                                        $issues[] = 'Warn: apc.report_autofilter is not Off';

         if (WINDOWS) {       // development
            /*PHP_INI_SYSTEM*/ if     (ini_get('apc.stat'))                                                               $issues[] = 'Warn: apc.stat is not Off';
            /*PHP_INI_ALL   */ elseif (ini_get('apc.cache_by_default'))                                                   $issues[] = 'Warn: apc.cache_by_default is not Off';         // "On" may crash some Windows APC versions (apc-error: cannot redeclare class ***)
         }                                                                                                                                                                             // Windows: if apc.stat="Off" this option MUST be "Off"
         else {               // production
            /*PHP_INI_ALL   */ if (!ini_get('apc.cache_by_default'))                                                      $issues[] = 'Warn: apc.cache_by_default is not On';
            /*PHP_INI_SYSTEM*/ if ( ini_get('apc.stat'))                                                                  $issues[] = 'Warn: apc.stat is not Off';                     // we want to cache fs-stat calls
            /*PHP_INI_SYSTEM*/ if (!ini_get('apc.write_lock'))                                                            $issues[] = 'Warn: apc.write_lock is not On';                // "Off" for perfomance; file modifications in production shall be disabled

            if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7') {
               /*PHP_INI_SYSTEM*/ if (ini_get('apc.include_once_override'))                                               $issues[] = 'Warn: apc.include_once_override is not Off';    // never use slow include_once()/require_once()
            }
            /*PHP_INI_SYSTEM*/ elseif (!ini_get('apc.include_once_override'))                                             $issues[] = 'Warn: apc.include_once_override is not On';
         }
      }
      elseif (extension_loaded('zend opcache')) {
         /*PHP_INI_ALL   */ if (!ini_get('opcache.enable'))                                                               $issues[] = 'Warn: opcache.enable is not On';
      }
      else                                                                                                                $issues[] = 'Warn: Opcode cache not found';





      // show issues or confirm if none are found
      if ($issues) echoPre(join(NL, $issues));
      else         echoPre('Configuration OK');


      // call phpInfo() if script runs via web server
      !CLI && phpInfo();
   }
}
