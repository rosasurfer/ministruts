#!/usr/bin/php -Cq
<?php

// -- begin of function definitions ---------------------------------------------------------------------------------------------

if (!function_exists('echoPre')) {
   /**
    * Hilfsfunktion zur formatierten Ausgabe einer Variable.
    *
    * @param mixed $var - die auszugebende Variable
    */
   function echoPre($var) {
      if (is_object($var) && method_exists($var, '__toString')) {
         $str = $var->__toString();
      }
      elseif (is_object($var) || is_array($var)) {
         $str = print_r($var, true);
      }
      else {
         $str = (string) $var;
      }

      if (isSet($_SERVER['REQUEST_METHOD']))
         $str = '<div align="left"><pre style="margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.htmlSpecialChars($str, ENT_QUOTES).'</pre></div>';
      $str .= "\n";

      echo $str;
   }
}


!defined('E_RECOVERABLE_ERROR') && define('E_RECOVERABLE_ERROR',  4096);   // since PHP 5.2.0
!defined('E_DEPRECATED'       ) && define('E_DEPRECATED'       ,  8192);   // since PHP 5.3.0
!defined('E_USER_DEPRECATED'  ) && define('E_USER_DEPRECATED'  , 16384);   // since PHP 5.3.0


if (!function_exists('errorLevelToStr')) {
   /**
    * Gibt einen Errorlevel in lesbarer Form zurück.
    *
    * @param int $level - Errorlevel, ohne Angabe wird der aktuelle Errorlevel des laufenden Scriptes ausgewertet.
    *
    * @return string
    */
   function errorLevelToStr($level=null) {
      if (func_num_args() && !is_int($level)) throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));

      $levels = array();
      if (!$level)
         $level = error_reporting();

      if ($level & E_ERROR            ) $levels[] = 'E_ERROR';
      if ($level & E_WARNING          ) $levels[] = 'E_WARNING';
      if ($level & E_PARSE            ) $levels[] = 'E_PARSE';
      if ($level & E_NOTICE           ) $levels[] = 'E_NOTICE';
      if ($level & E_DEPRECATED       ) $levels[] = 'E_DEPRECATED';
      if ($level & E_CORE_ERROR       ) $levels[] = 'E_CORE_ERROR';
      if ($level & E_CORE_WARNING     ) $levels[] = 'E_CORE_WARNING';
      if ($level & E_COMPILE_ERROR    ) $levels[] = 'E_COMPILE_ERROR';
      if ($level & E_COMPILE_WARNING  ) $levels[] = 'E_COMPILE_WARNING';
      if ($level & E_USER_ERROR       ) $levels[] = 'E_USER_ERROR';
      if ($level & E_USER_WARNING     ) $levels[] = 'E_USER_WARNING';
      if ($level & E_USER_NOTICE      ) $levels[] = 'E_USER_NOTICE';
      if ($level & E_USER_DEPRECATED  ) $levels[] = 'E_USER_DEPRECATED';
      if ($level & E_RECOVERABLE_ERROR) $levels[] = 'E_RECOVERABLE_ERROR';
      if ($level & E_ALL              ) $levels[] = 'E_ALL';
      if ($level & E_STRICT           ) $levels[] = 'E_STRICT';

      return join('|', $levels);
   }
}


// -- end of function definitions -----------------------------------------------------------------------------------------------


$isWarning = 0;

if (!defined('WINDOWS')) define('WINDOWS', (strToUpper(subStr(PHP_OS, 0, 3)) === 'WIN'));    // ob das Script unter Windows läuft
if (!defined('LOCAL'))   define('LOCAL'  , (@$_SERVER['REMOTE_ADDR'] == '127.0.0.1'));       // ob das Script lokal läuft


if (       PHP_VERSION < '5.2.1')                                            $isWarning = 1|echoPre('Warning: You are running a buggy PHP version, a version >= 5.2.1 is recommended.');
if (strPos(PHP_VERSION,  '5.3.')===0)                                        $isWarning = 1|echoPre('Warning: You are running a buggy PHP version, a version != 5.3 is recommended (see bug 47987).');
if (strPos(PHP_VERSION,  '5.4.')===0 && PHP_VERSION < '5.4.21')              $isWarning = 1|echoPre('Warning: You are running a buggy PHP version, a version >= 5.4.21 is recommended (see bug 47987).');

if (!ini_get('short_open_tag'))                                              $isWarning = 1|echoPre('Warning: short_open_tag is not On');
if (ini_get('safe_mode'))                                                    $isWarning = 1|echoPre('Warning: safe_mode is not Off');
if (ini_get('expose_php'))                                                   $isWarning = 1|echoPre('Warning: expose_php is not Off');

if (ini_get('register_globals'))                                             $isWarning = 1|echoPre('Warning: register_globals is not Off');
if (ini_get('register_long_arrays'))                                         $isWarning = 1|echoPre('Warning: register_long_arrays is not Off');
if (ini_get('register_argc_argv'))                                           $isWarning = 1|echoPre('Warning: register_argc_argv is not Off');
if (!ini_get('auto_globals_jit'))                                            $isWarning = 1|echoPre('Warning: auto_globals_jit is not On');
if (ini_get('variables_order') != 'GPCS')                                    $isWarning = 1|echoPre('Warning: variables_order is not "GPCS": "'.ini_get('variables_order').'"');
if (ini_get('always_populate_raw_post_data'))                                $isWarning = 1|echoPre('Warning: always_populate_raw_post_data is not Off');
if (ini_get('define_syslog_variables'))                                      $isWarning = 1|echoPre('Warning: define_syslog_variables is not Off');
if (ini_get('arg_separator.output') != '&amp;')                              $isWarning = 1|echoPre('Warning: arg_separator.output is not "&amp;": "'.ini_get('arg_separator.output').'"');
if (ini_get('allow_url_fopen'))                                              $isWarning = 1|echoPre('Warning: allow_url_fopen is not Off');
if (ini_get('allow_url_include'))                                            $isWarning = 1|echoPre('Warning: allow_url_include is not Off');

if ((int) ini_get('max_execution_time') != 30)                               $isWarning = 1|echoPre('Warning: max_execution_time is not 30: '.ini_get('max_execution_time'));
if ((int) ini_get('default_socket_timeout') != 60)                           $isWarning = 1|echoPre('Warning: default_socket_timeout is not 60: '.ini_get('default_socket_timeout'));
if (ini_get('implicit_flush'))                                               $isWarning = 1|echoPre('Warning: implicit_flush is not Off');
if (ini_get('allow_call_time_pass_reference'))                               $isWarning = 1|echoPre('Warning: allow_call_time_pass_reference is not Off');
if (!ini_get('ignore_user_abort'))                                           $isWarning = 1|echoPre('Warning: ignore_user_abort is not On');
if (ini_get('session.save_handler') != 'files')                              $isWarning = 1|echoPre('Warning: session.save_handler is not "files": "'.ini_get('session.save_handler').'"');
/*
if (ini_get('session.save_handler') == 'files') {
   $domainRoot = realPath($_SERVER['DOCUMENT_ROOT']);
   $dirs = explode(DIRECTORY_SEPARATOR, $domainRoot);
   if (($dir=$dirs[sizeOf($dirs)-1])=='httpdocs' || $dir=='htdocs' || $dir=='www' || $dir=='wwwdocs') {
      array_pop($dirs);
      $domainRoot = join(DIRECTORY_SEPARATOR, $dirs);
   }
   if (strPos(realPath(ini_get('session.save_path')), $domainRoot) === false) echoPre('Warning: session.save_path doesn\'t point inside the projects directory tree: '.realPath(ini_get('session.save_path')));
}
*/
if (ini_get('session.serialize_handler') != 'php')                           $isWarning = 1|echoPre('Warning: session.serialize_handler is not "php": "'.ini_get('session.serialize_handler').'"');
if (ini_get('session.auto_start'))                                           $isWarning = 1|echoPre('Warning: session.auto_start is not Off');
if (!ini_get('session.use_cookies'))                                         $isWarning = 1|echoPre('Warning: session.use_cookies is not On' );
if (ini_get('session.cookie_httponly'))                                      $isWarning = 1|echoPre('Warning: session.cookie_httponly is not Off' );
if (!ini_get('session.use_trans_sid'))                                       $isWarning = 1|echoPre('Warning: session.use_trans_sid is not On');
if (ini_get('url_rewriter.tags') != 'a=href,area=href,frame=src,iframe=src,form=,fieldset=')
                                                                             $isWarning = 1|echoPre('Warning: url_rewriter.tags is not "a=href,area=href,frame=src,iframe=src,form=,fieldset=": "'.ini_get('url_rewriter.tags').'"');
if (ini_get('session.bug_compat_42'))                                        $isWarning = 1|echoPre('Warning: session.bug_compat_42 is not Off');
if (ini_get('session.bug_compat_42') && !ini_get('session.bug_compat_warn')) $isWarning = 1|echoPre('Warning: session.bug_compat_warn is not On');
if (ini_get('session.referer_check') != '')                                  $isWarning = 1|echoPre('Warning: session.referer_check is not "": "'.ini_get('session.referer_check').'"');

if (ini_get('sql.safe_mode'))                                                $isWarning = 1|echoPre('Warning: sql.safe_mode is not Off');
if (ini_get('magic_quotes_gpc'))                                             $isWarning = 1|echoPre('Warning: magic_quotes_gpc is not Off');
if (ini_get('magic_quotes_runtime'))                                         $isWarning = 1|echoPre('Warning: magic_quotes_runtime is not Off');
if (ini_get('magic_quotes_sybase'))                                          $isWarning = 1|echoPre('Warning: magic_quotes_sybase is not Off');

$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
for ($i=0; $i < sizeOf($paths); ) if (trim($paths[$i++]) == '')              $isWarning = 1|echoPre('Warning: include_path contains empty path on position '.$i);
if (ini_get('auto_detect_line_endings'))                                     $isWarning = 1|echoPre('Warning: auto_detect_line_endings is not Off');
if (ini_get('default_mimetype') != 'text/html')                              $isWarning = 1|echoPre('Warning: default_mimetype is not "text/html": "'.ini_get('default_mimetype').'"');
if (ini_get('default_charset') != 'iso-8859-1')                              $isWarning = 1|echoPre('Warning: default_charset is not "iso-8859-1": "'.ini_get('default_charset').'"');
if (ini_get('file_uploads'))                                                 $isWarning = 1|echoPre('Warning: file_uploads is not Off' );

if (ini_get('asp_tags'))                                                     $isWarning = 1|echoPre('Warning: asp_tags is not Off');
if (!ini_get('y2k_compliance'))                                              $isWarning = 1|echoPre('Warning: y2k_compliance is not On');
if (!strLen(ini_get('date.timezone')))                                       $isWarning = 1|echoPre('Warning: date.timezone is not set');

$current = (int) ini_get('error_reporting');
$soll    = E_ALL & ~E_DEPRECATED | E_STRICT;
if ($current & $soll != $soll)                                               $isWarning = 1|echoPre('Warning: error_reporting is not "E_ALL | E_STRICT": "'.errorLevelToStr($current).'"');
if (ini_get('ignore_repeated_errors'))                                       $isWarning = 1|echoPre('Warning: ignore_repeated_errors is not Off');
if (ini_get('ignore_repeated_source'))                                       $isWarning = 1|echoPre('Warning: ignore_repeated_source is not Off');
if (!ini_get('log_errors'))                                                  $isWarning = 1|echoPre('Warning: log_errors is not On');
if ((int) ini_get('log_errors_max_len') != 0)                                $isWarning = 1|echoPre('Warning: log_errors_max_len is not 0: '.ini_get('log_errors_max_len'));
if (!ini_get('track_errors'))                                                $isWarning = 1|echoPre('Warning: track_errors is not On (needed for correct request decoding)');
if (ini_get('html_errors'))                                                  $isWarning = 1|echoPre('Warning: html_errors is not Off');

if (ini_get('enable_dl'))                                                    $isWarning = 1|echoPre('Warning: enable_dl is not Off');


// Extensions
// ----------
if (!extension_loaded('ctype'))                                              $isWarning = 1|echoPre('Warning: ctype extension is not loaded');
if (!extension_loaded('curl'))                                               $isWarning = 1|echoPre('Warning: curl extension is not loaded');
if (!extension_loaded('iconv'))                                              $isWarning = 1|echoPre('Warning: iconv extension is not loaded');
if (!extension_loaded('json'))                                               $isWarning = 1|echoPre('Warning: JSON extension is not loaded');
if (!extension_loaded('mysql'))                                              $isWarning = 1|echoPre('Warning: MySQL extension is not loaded');
if (!extension_loaded('mysqli'))                                             $isWarning = 1|echoPre('Warning: MySQLi extension is not loaded');
if (!WINDOWS && !extension_loaded('sysvsem'))                                $isWarning = 1|echoPre('Warning: System-V Semaphore extension is not loaded');


// Opcode-Cache
// ------------
if (!extension_loaded('apc'))                                                $isWarning = 1|echoPre('Warning: could not find APC opcode cache');
if ( extension_loaded('apc')) {
   if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7')          $isWarning = 1|echoPre('Warning: You are running a buggy APC version (a version < 3.1.3 or >= 3.1.7 is recommended).');
   if (!ini_get('apc.enabled'))                                              $isWarning = 1|echoPre('Warning: apc.enabled is not On');                   // warning "Potential cache slam averted for key '...'" http://bugs.php.net/bug.php?id=58832
   if ( ini_get('apc.report_autofilter'))                                    $isWarning = 1|echoPre('Warning: apc.report_autofilter is not Off');

   if (WINDOWS) {       // Entwicklungsumgebung
      if      (ini_get('apc.stat'))                                          $isWarning = 1|echoPre('Warning: apc.stat is not Off');
      else if (ini_get('apc.cache_by_default'))                              $isWarning = 1|echoPre('Warning: apc.cache_by_default is not Off');         // "On" läßt manche Windows-APC-Versionen crashen (apc-error: cannot redeclare class ***)
   }                                                                                                                                                     // wenn apc.stat="off" (siehe vorheriger Test), dann *MUSS* diese Option unter Windows aus sein.
   else {               // Produktionsumgebung
      if (!ini_get('apc.cache_by_default'))                                  $isWarning = 1|echoPre('Warning: apc.cache_by_default is not On');
      if ( ini_get('apc.stat'))                                              $isWarning = 1|echoPre('Warning: apc.stat is not Off');                     // es soll gecacht werden
      if (!ini_get('apc.write_lock'))                                        $isWarning = 1|echoPre('Warning: apc.write_lock is not On');                // für beste Performance möglichst "Off" (Dateiänderungen sollen live nicht möglich sein)

      if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7') {
         if (ini_get('apc.include_once_override'))                           $isWarning = 1|echoPre('Warning: apc.include_once_override is not Off');
      }                                                                                                                                                  // include_once()/require_once() sollten möglichst nicht verwendet werden
      else if (!ini_get('apc.include_once_override'))                        $isWarning = 1|echoPre('Warning: apc.include_once_override is not On');
   }
}


// Mailkonfiguration
// -----------------
if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isSet($_SERVER['SERVER_ADMIN']))
                                                                             $isWarning = 1|echoPre('Windows warning: neither sendmail_path nor sendmail_from are set');
if (!WINDOWS && !ini_get('sendmail_path'))                                   $isWarning = 1|echoPre('Warning: sendmail_path is not set');
if (isSet($_SERVER['REQUEST_METHOD']) && !isSet($_SERVER['SERVER_ADMIN']))   $isWarning = 1|echoPre('Warning: email address $_SERVER["SERVER_ADMIN"] is not set');


// Fehleranzeige etc. auf Entwicklungs- bzw. Produktivsystem
// ---------------------------------------------------------
if (LOCAL) {
   if (!ini_get('display_errors'))                                           $isWarning = 1|echoPre('Warning: display_errors is not On');
   if (!ini_get('display_startup_errors'))                                   $isWarning = 1|echoPre('Warning: display_startup_errors is not On');
   if ((int) ini_get('output_buffering') != 0)                               $isWarning = 1|echoPre('Warning: output_buffering is enabled: '.ini_get('output_buffering'));
}
else {
   if (ini_get('display_errors'))                                            $isWarning = 1|echoPre('Warning: display_errors is not Off');
   if (ini_get('display_startup_errors'))                                    $isWarning = 1|echoPre('Warning: display_startup_errors is not Off');
   if ((int) ini_get('output_buffering') == 0)                               $isWarning = 1|echoPre('Warning: output_buffering is not enabled: '.ini_get('output_buffering'));
}


// Bestätigung, wenn alles ok ist
if (!$isWarning)
   echo 'Configuration OK';
echo isSet($_SERVER['REQUEST_METHOD']) ? '<p>' : "\n";


/*
zlib.output_compression = Off
mysql.trace_mode = Off
assert.active = On
echoPre(get_loaded_extensions());
*/


// phpInfo() nur, wenn das Script von einem Webserver ausgeführt wird
isSet($_SERVER['REQUEST_METHOD']) && phpInfo();
?>
