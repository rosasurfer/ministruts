<?php

define('WINDOWS', (strToUpper(subStr(PHP_OS, 0, 3)) === 'WIN'));              // ob das Script unter Windows läuft
define('LOCAL'  , (@$_SERVER['REMOTE_ADDR'] == '127.0.0.1'));                 // ob das Script lokal läuft

if (PHP_VERSION < '5.2')                                                      display('Warning: PHP version is not at least 5.2.x');

if (!ini_get('short_open_tag'))                                               display('Warning: short_open_tag is not On');
if (ini_get('safe_mode'))                                                     display('Warning: safe_mode is not Off');
if (ini_get('expose_php'))                                                    display('Warning: expose_php is not Off');

if (ini_get('register_globals'))                                              display('Warning: register_globals is not Off');
if (ini_get('register_long_arrays'))                                          display('Warning: register_long_arrays is not Off');
if (ini_get('register_argc_argv'))                                            display('Warning: register_argc_argv is not Off');
if (!ini_get('auto_globals_jit'))                                             display('Warning: auto_globals_jit is not On');
if (ini_get('variables_order') != 'GPCS')                                     display('Warning: variables_order is not \'GPCS\': '.ini_get('variables_order'));
if (ini_get('always_populate_raw_post_data'))                                 display('Warning: always_populate_raw_post_data is not Off');
if (ini_get('define_syslog_variables'))                                       display('Warning: define_syslog_variables is not Off');
if (ini_get('arg_separator.output') != '&amp;')                               display('Warning: arg_separator.output is not \'&amp;amp;\': '.ini_get('arg_separator.output'));
if (ini_get('allow_url_fopen'))                                               display('Warning: allow_url_fopen is not Off');
if (ini_get('allow_url_include'))                                             display('Warning: allow_url_include is not Off');

if ((int) ini_get('max_execution_time') != 30)                                display('Warning: max_execution_time is not 30: '.ini_get('max_execution_time'));
if ((int) ini_get('default_socket_timeout') != 60)                            display('Warning: default_socket_timeout is not 60: '.ini_get('default_socket_timeout'));
if (ini_get('implicit_flush'))                                                display('Warning: implicit_flush is not Off');
if (ini_get('allow_call_time_pass_reference'))                                display('Warning: allow_call_time_pass_reference is not Off');
if (!ini_get('ignore_user_abort'))                                            display('Warning: ignore_user_abort is not On');
if (ini_get('session.save_handler') != 'files')                               display('Warning: session.save_handler is not \'files\': '.ini_get('session.save_handler'));
if (ini_get('session.save_handler') == 'files') {
   $domainRoot = realPath($_SERVER['DOCUMENT_ROOT']);
   $dirs = explode(DIRECTORY_SEPARATOR, $domainRoot);
   if (($dir=$dirs[sizeOf($dirs)-1])=='httpdocs' || $dir=='htdocs' || $dir=='www' || $dir=='wwwdocs') {
      array_pop($dirs);
      $domainRoot = join(DIRECTORY_SEPARATOR, $dirs);
   }
   if (strPos(realPath(ini_get('session.save_path')), $domainRoot) === false) display('Warning: session.save_path doesn\'t point inside the projects directory tree: '.realPath(ini_get('session.save_path')));
}
if (ini_get('session.serialize_handler') != 'php')                            display('Warning: session.serialize_handler is not \'php\': '.ini_get('session.serialize_handler'));
if (ini_get('session.auto_start'))                                            display('Warning: session.auto_start is not Off');
if (!ini_get('session.use_cookies'))                                          display('Warning: session.use_cookies is not On' );
if (ini_get('session.cookie_httponly'))                                       display('Warning: session.cookie_httponly is not Off' );
if (!ini_get('session.use_trans_sid'))                                        display('Warning: session.use_trans_sid is not On');
if (ini_get('url_rewriter.tags') != 'a=href,area=href,frame=src,iframe=src,form=,fieldset=')
                                                                              display('Warning: url_rewriter.tags is not \'a=href,area=href,frame=src,iframe=src,form=,fieldset=\': '.ini_get('url_rewriter.tags'));
if (ini_get('session.bug_compat_42'))                                         display('Warning: session.bug_compat_42 is not Off');
if (ini_get('session.bug_compat_42') && !ini_get('session.bug_compat_warn'))  display('Warning: session.bug_compat_warn is not On');
if (ini_get('session.referer_check') != '')                                   display('Warning: session.referer_check is not \'\': '.ini_get('session.referer_check'));

if (ini_get('sql.safe_mode'))                                                 display('Warning: sql.safe_mode is not Off');
if (ini_get('magic_quotes_gpc'))                                              display('Warning: magic_quotes_gpc is not Off');
if (ini_get('magic_quotes_runtime'))                                          display('Warning: magic_quotes_runtime is not Off');
if (ini_get('magic_quotes_sybase'))                                           display('Warning: magic_quotes_sybase is not Off');

$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
for ($i=0; $i < sizeOf($paths); ) if (trim($paths[$i++]) == '')               display('Warning: include_path contains empty path on position '.$i);
if (ini_get('auto_prepend_file') != '')                                       display('Warning: auto_prepend_file is not empty: '.ini_get('auto_prepend_file'));
if (ini_get('auto_append_file') != '')                                        display('Warning: auto_append_file is not empty: '.ini_get('auto_append_file'));
if (ini_get('auto_detect_line_endings'))                                      display('Warning: auto_detect_line_endings is not Off');
if (ini_get('default_mimetype') != 'text/html')                               display('Warning: default_mimetype is not \'text/html\': '.ini_get('default_mimetype'));
if (ini_get('default_charset') != 'iso-8859-1')                               display('Warning: default_charset is not \'iso-8859-1\': '.ini_get('default_charset'));
if (ini_get('file_uploads'))                                                  display('Warning: file_uploads is not Off' );

if (ini_get('asp_tags'))                                                      display('Warning: asp_tags is not Off');
if (!ini_get('y2k_compliance'))                                               display('Warning: y2k_compliance is not On');
if (strLen(ini_get('date.timezone')) == 0)                                    display('Warning: date.timezone is not set');

$current = (int) ini_get('error_reporting');
if (($current & (E_ALL | E_STRICT)) != (E_ALL | E_STRICT))                    display('Warning: error_reporting is not E_ALL | E_STRICT: '.ini_get('error_reporting'));
if (ini_get('ignore_repeated_errors'))                                        display('Warning: ignore_repeated_errors is not Off');
if (ini_get('ignore_repeated_source'))                                        display('Warning: ignore_repeated_source is not Off');
if (!ini_get('log_errors'))                                                   display('Warning: log_errors is not On' );
if ((int)ini_get('log_errors_max_len') != 0)                                  display('Warning: log_errors_max_len is not 0: '.ini_get('log_errors_max_len'));
if (ini_get('track_errors'))                                                  display('Warning: track_errors is not Off' );
if (ini_get('html_errors'))                                                   display('Warning: html_errors is not Off');

if (ini_get('enable_dl'))                                                     display('Warning: enable_dl is not Off');


// MySQL-Extensions
// ----------------
if (!extension_loaded('mysql'))                                               display('Warning: mysql extension is not loaded');
if (!extension_loaded('mysqli') && PHP_VERSION >= '5')                        display('Warning: mysqli extension is not loaded');


// Mailkonfiguration
// -----------------
if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isSet($_SERVER['SERVER_ADMIN']))
                                                                              display('Windows warning: neither sendmail_path nor sendmail_from are set');
if (!WINDOWS && !ini_get('sendmail_path'))                                    display('Warning: sendmail_path is not set');
if (isSet($_SERVER['REQUEST_METHOD']) && !isSet($_SERVER['SERVER_ADMIN']))    display('Warning: email address $_SERVER[\'SERVER_ADMIN\'] is not set');


// Opcode-Cache
// ------------
if (!extension_loaded('apc'))                                                 display('Warning: could not find an opcode cache');
if (extension_loaded('apc')) {
   if (!ini_get('apc.enabled'))                                               display('Warning: apc.enabled is not On');
   if (!ini_get('apc.stat'))                                                  display('Warning: apc.stat is not On');     // Off verursacht Fehler (Dateien werden teilweise nicht gecacht)
   if (WINDOWS && ini_get('apc.cache_by_default'))                            display('Windows warning: apc.cache_by_default is not Off');
   if (!WINDOWS && !ini_get('apc.cache_by_default'))                          display('Warning: apc.cache_by_default is not On');
}


// Entwicklungs- bzw. Produktivsystem: Fehlerausgabe etc.
// ------------------------------------------------------
if (LOCAL) {
   if (!ini_get('display_errors'))                                            display('Warning: display_errors is not On');
   if (!ini_get('display_startup_errors'))                                    display('Warning: display_startup_errors is not On');
   if ((int) ini_get('output_buffering') != 0)                                display('Warning: output_buffering is enabled: '.ini_get('output_buffering'));
}
else {
   if (ini_get('display_errors'))                                             display('Warning: display_errors is not Off');
   if (ini_get('display_startup_errors'))                                     display('Warning: display_startup_errors is not Off');
   if ((int) ini_get('output_buffering') == 0)                                display('Warning: output_buffering is not enabled: '.ini_get('output_buffering'));
}




// Bestätigung, wenn alles ok ist.
if (!isSet($errors)) {
   echo 'Configuration OK';
}
echo '<p>';


/**
 * Hilfsfunktion für die Ausgabe
 */
function display($var) {
   $GLOBALS['errors'] = true;

   $str = (is_array($var) ? print_r($var, true) : $var)."\n";

   if (isSet($_SERVER['REQUEST_METHOD'])) {
      $str = '<div align="left"><pre>'.$str.'</pre></div>';
   }
   echo $str;
}

/*
zlib.output_compression = Off
mysql.trace_mode = Off
assert.active = On
*/

phpinfo();
?>
