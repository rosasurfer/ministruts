<?php
use rosasurfer\debug\DebugHelper;

use function rosasurfer\_true;
use function rosasurfer\echoPre;

use const rosasurfer\CLI;
use const rosasurfer\LOCALHOST;
use const rosasurfer\WINDOWS;


$warning = false;


if (PHP_VERSION_ID < 50421)                                                                     $warning = _true(echoPre('Warning: You are running a buggy PHP version: '.PHP_VERSION));
if (!ini_get('short_open_tag'))                                                                 $warning = _true(echoPre('Warning: short_open_tag is not On'));
if (ini_get('expose_php'))                                                                      $warning = _true(echoPre('Warning: expose_php is not Off'));
if (ini_get('register_globals'))                                                                $warning = _true(echoPre('Warning: register_globals is not Off'));
if (ini_get('register_long_arrays'))                                                            $warning = _true(echoPre('Warning: register_long_arrays is not Off'));
if (!CLI && ini_get('register_argc_argv'))                                                      $warning = _true(echoPre('Warning: register_argc_argv is not Off'));                                   // since v5.4 hardcoded to On for the CLI SAPI
if (ini_get('variables_order') != 'GPCS')                                                       $warning = _true(echoPre('Warning: variables_order is not "GPCS": "'.ini_get('variables_order').'"'));
if (ini_get('request_order') != 'GP')                                                           $warning = _true(echoPre('Warning: request_order is not "GP": "'.ini_get('request_order').'"'));
if (ini_get('always_populate_raw_post_data'))                                                   $warning = _true(echoPre('Warning: always_populate_raw_post_data is not Off'));
if (!ini_get('auto_globals_jit'))                                                               $warning = _true(echoPre('Warning: auto_globals_jit is not On'));
if (ini_get('define_syslog_variables'))                                                         $warning = _true(echoPre('Warning: define_syslog_variables is not Off'));
if (ini_get('arg_separator.output') != '&amp;')                                                 $warning = _true(echoPre('Warning: arg_separator.output is not "&amp;": "'.ini_get('arg_separator.output').'"'));
if (ini_get('allow_url_include'))                                                               $warning = _true(echoPre('Warning: allow_url_include is not Off'));
if (!CLI && (int)ini_get('max_execution_time') != 30)                                           $warning = _true(echoPre('Warning: max_execution_time is not 30: '.ini_get('max_execution_time')));    // since v5.4 hardcoded to 0 for the CLI SAPI
if ((int)ini_get('default_socket_timeout') != 60)                                               $warning = _true(echoPre('Warning: default_socket_timeout is not 60: '.ini_get('default_socket_timeout')));
if (!CLI && ini_get('implicit_flush'))                                                          $warning = _true(echoPre('Warning: implicit_flush is not Off'));                                       // since v5.4 hardcoded to On for the CLI SAPI
if (ini_get('allow_call_time_pass_reference') && PHP_VERSION_ID < 50400) /*removed as of v5.4*/ $warning = _true(echoPre('Warning: allow_call_time_pass_reference is not Off'));
if (!ini_get('ignore_user_abort'))                                                              $warning = _true(echoPre('Warning: ignore_user_abort is not On'));
if (ini_get('session.save_handler') != 'files')                                                 $warning = _true(echoPre('Warning: session.save_handler is not "files": "'.ini_get('session.save_handler').'"'));
if (ini_get('session.serialize_handler') != 'php')                                              $warning = _true(echoPre('Warning: session.serialize_handler is not "php": "'.ini_get('session.serialize_handler').'"'));
if (ini_get('session.auto_start'))                                                              $warning = _true(echoPre('Warning: session.auto_start is not Off'));
if (!ini_get('session.use_cookies'))                                                            $warning = _true(echoPre('Warning: session.use_cookies is not On' ));
if (!ini_get('session.cookie_httponly'))                                                        $warning = _true(echoPre('Warning: session.cookie_httponly is not On'));
if (ini_get('session.use_trans_sid'))                                                           $warning = _true(echoPre('Warning: session.use_trans_sid is not Off'));
if (ini_get('url_rewriter.tags') != 'a=href,area=href,frame=src,iframe=src,form=,fieldset=')    $warning = _true(echoPre('Warning: url_rewriter.tags is not "a=href,area=href,frame=src,iframe=src,form=,fieldset=": "'.ini_get('url_rewriter.tags').'"'));
if (ini_get('session.bug_compat_42'))                                                           $warning = _true(echoPre('Warning: session.bug_compat_42 is not Off'));
if (ini_get('session.bug_compat_42') && !ini_get('session.bug_compat_warn'))                    $warning = _true(echoPre('Warning: session.bug_compat_warn is not On'));
if (ini_get('session.referer_check') != '')                                                     $warning = _true(echoPre('Warning: session.referer_check is not "": "'.ini_get('session.referer_check').'"'));
if (ini_get('sql.safe_mode'))                                                                   $warning = _true(echoPre('Warning: sql.safe_mode is not Off'));
if (ini_get('magic_quotes_gpc'))                                      /*removed as of v5.4*/    $warning = _true(echoPre('Warning: magic_quotes_gpc is not Off'));
if (ini_get('magic_quotes_runtime'))                                  /*removed as of v5.4*/    $warning = _true(echoPre('Warning: magic_quotes_runtime is not Off'));
if (ini_get('magic_quotes_sybase'))                                                             $warning = _true(echoPre('Warning: magic_quotes_sybase is not Off'));

$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
for ($i=0; $i < sizeOf($paths); ) {
   if (trim($paths[$i++]) == '')                                                                $warning = _true(echoPre('Warning: include_path contains empty path on position '.$i));
}
if (ini_get('default_mimetype') != 'text/html')                                                 $warning = _true(echoPre('Warning: default_mimetype is not "text/html": "'.ini_get('default_mimetype').'"'));
if (strToLower(ini_get('default_charset')) != 'utf-8')                                          $warning = _true(echoPre('Warning: default_charset is not "UTF-8": "'.ini_get('default_charset').'"'));
if (ini_get('file_uploads'))                                                                    $warning = _true(echoPre('Warning: file_uploads is not Off'));

if (ini_get('asp_tags'))                                                                        $warning = _true(echoPre('Warning: asp_tags is not Off'));
if (!ini_get('y2k_compliance') && PHP_VERSION_ID < 50400)             /*removed as of v5.4*/    $warning = _true(echoPre('Warning: y2k_compliance is not On'));
if (!strLen(ini_get('date.timezone')))                                                          $warning = _true(echoPre('Warning: date.timezone is not set'));

$current = (int) ini_get('error_reporting');
$soll    = E_ALL & ~E_DEPRECATED | E_STRICT;
if ($current & $soll != $soll)                                                                  $warning = _true(echoPre('Warning: error_reporting is not "E_ALL | E_STRICT": "'.DebugHelper::errorLevelToStr($current).'"'));
if (ini_get('ignore_repeated_errors'))                                                          $warning = _true(echoPre('Warning: ignore_repeated_errors is not Off'));
if (ini_get('ignore_repeated_source'))                                                          $warning = _true(echoPre('Warning: ignore_repeated_source is not Off'));
if (!ini_get('log_errors'))                                                                     $warning = _true(echoPre('Warning: log_errors is not On'));
if ((int) ini_get('log_errors_max_len') != 0)                                                   $warning = _true(echoPre('Warning: log_errors_max_len is not 0: '.ini_get('log_errors_max_len')));
if (!ini_get('track_errors'))                                                                   $warning = _true(echoPre('Warning: track_errors is not On'));
if (ini_get('html_errors'))                                                                     $warning = _true(echoPre('Warning: html_errors is not Off'));

if (ini_get('enable_dl'))                                                                       $warning = _true(echoPre('Warning: enable_dl is not Off'));


// Extensions
if (!extension_loaded('ctype'))                                                                 $warning = _true(echoPre('Warning: ctype extension is not loaded'));
if (!extension_loaded('curl'))                                                                  $warning = _true(echoPre('Warning: curl extension is not loaded'));
if (!extension_loaded('iconv'))                                                                 $warning = _true(echoPre('Warning: iconv extension is not loaded'));
if (!extension_loaded('json'))                                                                  $warning = _true(echoPre('Warning: JSON extension is not loaded'));
if (!extension_loaded('mysql'))                                                                 $warning = _true(echoPre('Warning: MySQL extension is not loaded'));
if (!extension_loaded('mysqli'))                                                                $warning = _true(echoPre('Warning: MySQLi extension is not loaded'));
if (!WINDOWS && !extension_loaded('sysvsem'))                                                   $warning = _true(echoPre('Warning: System-V Semaphore extension is not loaded'));


// Opcode-Cache
if (extension_loaded('apc')) {
   if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7')                             $warning = _true(echoPre('Warning: You are running a buggy APC version (a version < 3.1.3 or >= 3.1.7 is recommended): '.phpVersion('apc')));
   if (!ini_get('apc.enabled'))                                                                 $warning = _true(echoPre('Warning: apc.enabled is not On'));                   // warning "Potential cache slam averted for key '...'" http://bugs.php.net/bug.php?id=58832
   if ( ini_get('apc.report_autofilter'))                                                       $warning = _true(echoPre('Warning: apc.report_autofilter is not Off'));

   if (WINDOWS) {       // development
      if     (ini_get('apc.stat'))                                                              $warning = _true(echoPre('Warning: apc.stat is not Off'));
      elseif (ini_get('apc.cache_by_default'))                                                  $warning = _true(echoPre('Warning: apc.cache_by_default is not Off'));         // "On" may crash some Windows APC versions (apc-error: cannot redeclare class ***)
   }                                                                                                                                                                             // Windows: if apc.stat="Off" this option MUST be "Off"
   else {               // production
      if (!ini_get('apc.cache_by_default'))                                                     $warning = _true(echoPre('Warning: apc.cache_by_default is not On'));
      if ( ini_get('apc.stat'))                                                                 $warning = _true(echoPre('Warning: apc.stat is not Off'));                     // we want to cache fs-stat calls
      if (!ini_get('apc.write_lock'))                                                           $warning = _true(echoPre('Warning: apc.write_lock is not On'));                // "Off" for perfomance; file modifications in production shall be disabled

      if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7') {
         if (ini_get('apc.include_once_override'))                                              $warning = _true(echoPre('Warning: apc.include_once_override is not Off'));    // never use slow include_once()/require_once()
      }
      elseif (!ini_get('apc.include_once_override'))                                            $warning = _true(echoPre('Warning: apc.include_once_override is not On'));
   }
}
elseif (extension_loaded('zend opcache')) {
   if (!ini_get('opcache.enable'))                                                              $warning = _true(echoPre('Warning: opcache.enable is not On'));
}
else                                                                                            $warning = _true(echoPre('Warning: Opcode cache not found'));


// Mail
if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isSet($_SERVER['SERVER_ADMIN']))
                                                                                                $warning = _true(echoPre('Warning: Windows - neither sendmail_path nor sendmail_from are set'));
if (!WINDOWS && !ini_get('sendmail_path'))                                                      $warning = _true(echoPre('Warning: sendmail_path is not set'));
if (!CLI && !isSet($_SERVER['SERVER_ADMIN']))                                                   $warning = _true(echoPre('Warning: email address $_SERVER["SERVER_ADMIN"] is not set'));
if (ini_get('mail.add_x_header'))                                                               $warning = _true(echoPre('Warning: mail.add_x_header is not Off'));


// Error display
if (CLI || LOCALHOST) {
   if (!ini_get('display_errors'))                                                              $warning = _true(echoPre('Warning: display_errors is not On'));
   if (!ini_get('display_startup_errors'))                                                      $warning = _true(echoPre('Warning: display_startup_errors is not On'));
   if (!CLI && (int) ini_get('output_buffering') != 0)                                          $warning = _true(echoPre('Warning: output_buffering is enabled: '.ini_get('output_buffering')));  // since v5.4 hardcoded to Off for the CLI SAPI
}
else {
   if (ini_get('display_errors'))                                                               $warning = _true(echoPre('Warning: display_errors is not Off'));
   if (ini_get('display_startup_errors'))                                                       $warning = _true(echoPre('Warning: display_startup_errors is not Off'));
   if (!CLI && (int) ini_get('output_buffering') == 0)                                          $warning = _true(echoPre('Warning: output_buffering is not enabled: '.ini_get('output_buffering')));
}


// confirm if no issues found
if (!$warning)
   echo 'Configuration OK';
echo CLI ? "\n":'<p>';


// call phpInfo() only if script runs on web server
!CLI && phpInfo();
