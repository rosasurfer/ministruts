<?php
$isWarning = false;

if (!defined('WINDOWS')) define('WINDOWS', (strToUpper(subStr(PHP_OS, 0, 3)) === 'WIN'));    // ob das Script unter Windows läuft
if (!defined('LOCAL'))   define('LOCAL'  , (@$_SERVER['REMOTE_ADDR'] == '127.0.0.1'));       // ob das Script lokal läuft


if (!defined('PHPLIB_ROOT') && PHP_VERSION < '5.2.1')                         $isWarning |= warn('Warning: You are working with a buggy PHP version (at least version 5.2.1 is needed)');

if (!ini_get('short_open_tag'))                                               $isWarning |= warn('Warning: short_open_tag is not On');
if (ini_get('safe_mode'))                                                     $isWarning |= warn('Warning: safe_mode is not Off');
if (ini_get('expose_php'))                                                    $isWarning |= warn('Warning: expose_php is not Off');

if (ini_get('register_globals'))                                              $isWarning |= warn('Warning: register_globals is not Off');
if (ini_get('register_long_arrays'))                                          $isWarning |= warn('Warning: register_long_arrays is not Off');
if (ini_get('register_argc_argv'))                                            $isWarning |= warn('Warning: register_argc_argv is not Off');
if (!ini_get('auto_globals_jit'))                                             $isWarning |= warn('Warning: auto_globals_jit is not On');
if (ini_get('variables_order') != 'GPCS')                                     $isWarning |= warn('Warning: variables_order is not \'GPCS\': '.ini_get('variables_order'));
if (ini_get('always_populate_raw_post_data'))                                 $isWarning |= warn('Warning: always_populate_raw_post_data is not Off');
if (ini_get('define_syslog_variables'))                                       $isWarning |= warn('Warning: define_syslog_variables is not Off');
if (ini_get('arg_separator.output') != '&amp;')                               $isWarning |= warn('Warning: arg_separator.output is not \'&amp;amp;\': '.ini_get('arg_separator.output'));
if (ini_get('allow_url_fopen'))                                               $isWarning |= warn('Warning: allow_url_fopen is not Off');
if (ini_get('allow_url_include'))                                             $isWarning |= warn('Warning: allow_url_include is not Off');

if ((int) ini_get('max_execution_time') != 30)                                $isWarning |= warn('Warning: max_execution_time is not 30: '.ini_get('max_execution_time'));
if ((int) ini_get('default_socket_timeout') != 60)                            $isWarning |= warn('Warning: default_socket_timeout is not 60: '.ini_get('default_socket_timeout'));
if (ini_get('implicit_flush'))                                                $isWarning |= warn('Warning: implicit_flush is not Off');
if (ini_get('allow_call_time_pass_reference'))                                $isWarning |= warn('Warning: allow_call_time_pass_reference is not Off');
if (!ini_get('ignore_user_abort'))                                            $isWarning |= warn('Warning: ignore_user_abort is not On');
if (ini_get('session.save_handler') != 'files')                               $isWarning |= warn('Warning: session.save_handler is not \'files\': '.ini_get('session.save_handler'));
/*
if (ini_get('session.save_handler') == 'files') {
   $domainRoot = realPath($_SERVER['DOCUMENT_ROOT']);
   $dirs = explode(DIRECTORY_SEPARATOR, $domainRoot);
   if (($dir=$dirs[sizeOf($dirs)-1])=='httpdocs' || $dir=='htdocs' || $dir=='www' || $dir=='wwwdocs') {
      array_pop($dirs);
      $domainRoot = join(DIRECTORY_SEPARATOR, $dirs);
   }
   if (strPos(realPath(ini_get('session.save_path')), $domainRoot) === false) warn('Warning: session.save_path doesn\'t point inside the projects directory tree: '.realPath(ini_get('session.save_path')));
}
*/
if (ini_get('session.serialize_handler') != 'php')                            $isWarning |= warn('Warning: session.serialize_handler is not \'php\': '.ini_get('session.serialize_handler'));
if (ini_get('session.auto_start'))                                            $isWarning |= warn('Warning: session.auto_start is not Off');
if (!ini_get('session.use_cookies'))                                          $isWarning |= warn('Warning: session.use_cookies is not On' );
if (ini_get('session.cookie_httponly'))                                       $isWarning |= warn('Warning: session.cookie_httponly is not Off' );
if (!ini_get('session.use_trans_sid'))                                        $isWarning |= warn('Warning: session.use_trans_sid is not On');
if (ini_get('url_rewriter.tags') != 'a=href,area=href,frame=src,iframe=src,form=,fieldset=')
                                                                              $isWarning |= warn('Warning: url_rewriter.tags is not \'a=href,area=href,frame=src,iframe=src,form=,fieldset=\': '.ini_get('url_rewriter.tags'));
if (ini_get('session.bug_compat_42'))                                         $isWarning |= warn('Warning: session.bug_compat_42 is not Off');
if (ini_get('session.bug_compat_42') && !ini_get('session.bug_compat_warn'))  $isWarning |= warn('Warning: session.bug_compat_warn is not On');
if (ini_get('session.referer_check') != '')                                   $isWarning |= warn('Warning: session.referer_check is not \'\': '.ini_get('session.referer_check'));

if (ini_get('sql.safe_mode'))                                                 $isWarning |= warn('Warning: sql.safe_mode is not Off');
if (ini_get('magic_quotes_gpc'))                                              $isWarning |= warn('Warning: magic_quotes_gpc is not Off');
if (ini_get('magic_quotes_runtime'))                                          $isWarning |= warn('Warning: magic_quotes_runtime is not Off');
if (ini_get('magic_quotes_sybase'))                                           $isWarning |= warn('Warning: magic_quotes_sybase is not Off');

$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
for ($i=0; $i < sizeOf($paths); ) if (trim($paths[$i++]) == '')               $isWarning |= warn('Warning: include_path contains empty path on position '.$i);
if (ini_get('auto_detect_line_endings'))                                      $isWarning |= warn('Warning: auto_detect_line_endings is not Off');
if (ini_get('default_mimetype') != 'text/html')                               $isWarning |= warn('Warning: default_mimetype is not \'text/html\': '.ini_get('default_mimetype'));
if (ini_get('default_charset') != 'iso-8859-1')                               $isWarning |= warn('Warning: default_charset is not \'iso-8859-1\': '.ini_get('default_charset'));
if (ini_get('file_uploads'))                                                  $isWarning |= warn('Warning: file_uploads is not Off' );

if (ini_get('asp_tags'))                                                      $isWarning |= warn('Warning: asp_tags is not Off');
if (!ini_get('y2k_compliance'))                                               $isWarning |= warn('Warning: y2k_compliance is not On');
if (!strLen(ini_get('date.timezone')))                                        $isWarning |= warn('Warning: date.timezone is not set');

$current = (int) ini_get('error_reporting');
if (($current & (E_ALL | E_STRICT)) != (E_ALL | E_STRICT))                    $isWarning |= warn('Warning: error_reporting is not E_ALL | E_STRICT: '.ini_get('error_reporting'));
if (ini_get('ignore_repeated_errors'))                                        $isWarning |= warn('Warning: ignore_repeated_errors is not Off');
if (ini_get('ignore_repeated_source'))                                        $isWarning |= warn('Warning: ignore_repeated_source is not Off');
if (!ini_get('log_errors'))                                                   $isWarning |= warn('Warning: log_errors is not On');
if ((int) ini_get('log_errors_max_len') != 0)                                 $isWarning |= warn('Warning: log_errors_max_len is not 0: '.ini_get('log_errors_max_len'));
if (!ini_get('track_errors'))                                                 $isWarning |= warn('Warning: track_errors is not On');
if (ini_get('html_errors'))                                                   $isWarning |= warn('Warning: html_errors is not Off');

if (ini_get('enable_dl'))                                                     $isWarning |= warn('Warning: enable_dl is not Off');


// Extensions
// ----------
if (!extension_loaded('iconv'))                                               $isWarning |= warn('Warning: iconv extension is not loaded');
if (!extension_loaded('json'))                                                $isWarning |= warn('Warning: JSON extension is not loaded');
if (!extension_loaded('mysql'))                                               $isWarning |= warn('Warning: MySQL extension is not loaded');
if (!extension_loaded('mysqli'))                                              $isWarning |= warn('Warning: MySQLi extension is not loaded');
if (!WINDOWS && !extension_loaded('sysvsem'))                                 $isWarning |= warn('Warning: System-V Semaphore extension is not loaded');


// Opcode-Cache
// ------------
if (!extension_loaded('apc'))                                                 $isWarning |= warn('Warning: could not find an opcode cache');
if ( extension_loaded('apc')) {
   if (!ini_get('apc.enabled'))                                               $isWarning |= warn('Warning: apc.enabled is not On');
   if (WINDOWS) {       // Entwicklungsumgebung
      if      (ini_get('apc.stat'))                                           $isWarning |= warn('Warning: apc.stat is not Off');               // "On" läßt manche APC-Versionen crashen (apc-error: cannot redeclare class ***)
      else if (ini_get('apc.cache_by_default'))                               $isWarning |= warn('Warning: apc.cache_by_default is not Off');   // wenn apc.stat="off" (siehe vorheriger Test), dann *MUSS* diese Option unter Windows aus sein.
   }
   else {               // Produktionsumgebung
      if (!ini_get('apc.cache_by_default'))                                   $isWarning |= warn('Warning: apc.cache_by_default is not On');    // es soll gecacht werden
      if ( ini_get('apc.stat'))                                               $isWarning |= warn('Warning: apc.stat is not Off');               // für höchstmögliche Performance möglichst "Off" (Dateiänderungen sind live nicht möglich)
      if (!ini_get('apc.write_lock'))                                         $isWarning |= warn('Warning: apc.write_lock is not On');
      if (!ini_get('apc.report_autofilter'))                                  $isWarning |= warn('Warning: apc.report_autofilter is not On');
      if (!ini_get('apc.include_once_override'))                              $isWarning |= warn('Warning: apc.include_once_override is not On');
   }
}


// Mailkonfiguration
// -----------------
if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isSet($_SERVER['SERVER_ADMIN']))
                                                                              $isWarning |= warn('Windows warning: neither sendmail_path nor sendmail_from are set');
if (!WINDOWS && !ini_get('sendmail_path'))                                    $isWarning |= warn('Warning: sendmail_path is not set');
if (isSet($_SERVER['REQUEST_METHOD']) && !isSet($_SERVER['SERVER_ADMIN']))    $isWarning |= warn('Warning: email address $_SERVER[\'SERVER_ADMIN\'] is not set');


// Entwicklungs- bzw. Produktivsystem: Fehlerausgabe etc.
// ------------------------------------------------------
if (LOCAL) {
   if (!ini_get('display_errors'))                                            $isWarning |= warn('Warning: display_errors is not On');
   if (!ini_get('display_startup_errors'))                                    $isWarning |= warn('Warning: display_startup_errors is not On');
   if ((int) ini_get('output_buffering') != 0)                                $isWarning |= warn('Warning: output_buffering is enabled: '.ini_get('output_buffering'));
}
else {
   if (ini_get('display_errors'))                                             $isWarning |= warn('Warning: display_errors is not Off');
   if (ini_get('display_startup_errors'))                                     $isWarning |= warn('Warning: display_startup_errors is not Off');
   if ((int) ini_get('output_buffering') == 0)                                $isWarning |= warn('Warning: output_buffering is not enabled: '.ini_get('output_buffering'));
}


// Bestätigung, wenn alles ok ist.
if (!$isWarning)
   echo 'Configuration OK';
echo isSet($_SERVER['REQUEST_METHOD']) ? '<p>' : "\n";


/**
 * Hilfsfunktion zur formatierten Ausgabe.
 *
 * @param string $str - der auszugebende String
 *
 * @return bool - TRUE
 */
function warn($str) {
   if (isSet($_SERVER['REQUEST_METHOD']))
      $str = '<div align="left"><pre style="margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.htmlSpecialChars($str, ENT_QUOTES).'</pre></div>';
   $str .= "\n";

   echo $str;

   return true;
}

/*
zlib.output_compression = Off
mysql.trace_mode = Off
assert.active = On
$str = print_r(get_loaded_extensions(), true);
echo '<div align="left"><pre style="margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.htmlSpecialChars($str, ENT_QUOTES).'</pre></div>';
*/


phpinfo();
?>
