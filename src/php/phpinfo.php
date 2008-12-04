<?php
if (!defined('WINDOWS')) define('WINDOWS', (strToUpper(subStr(PHP_OS, 0, 3)) === 'WIN'));    // ob das Script unter Windows läuft
if (!defined('LOCAL'))   define('LOCAL'  , (@$_SERVER['REMOTE_ADDR'] == '127.0.0.1'));       // ob das Script lokal läuft


if (PHP_VERSION < '5.2')                                                      echoError('Warning: PHP version is older than 5.2');

if (!ini_get('short_open_tag'))                                               echoError('Warning: short_open_tag is not On');
if (ini_get('safe_mode'))                                                     echoError('Warning: safe_mode is not Off');
if (ini_get('expose_php'))                                                    echoError('Warning: expose_php is not Off');

if (ini_get('register_globals'))                                              echoError('Warning: register_globals is not Off');
if (ini_get('register_long_arrays'))                                          echoError('Warning: register_long_arrays is not Off');
if (ini_get('register_argc_argv'))                                            echoError('Warning: register_argc_argv is not Off');
if (!ini_get('auto_globals_jit'))                                             echoError('Warning: auto_globals_jit is not On');
if (ini_get('variables_order') != 'GPCS')                                     echoError('Warning: variables_order is not \'GPCS\': '.ini_get('variables_order'));
if (ini_get('always_populate_raw_post_data'))                                 echoError('Warning: always_populate_raw_post_data is not Off');
if (ini_get('define_syslog_variables'))                                       echoError('Warning: define_syslog_variables is not Off');
if (ini_get('arg_separator.output') != '&amp;')                               echoError('Warning: arg_separator.output is not \'&amp;amp;\': '.ini_get('arg_separator.output'));
if (ini_get('allow_url_fopen'))                                               echoError('Warning: allow_url_fopen is not Off');
if (ini_get('allow_url_include'))                                             echoError('Warning: allow_url_include is not Off');

if ((int) ini_get('max_execution_time') != 30)                                echoError('Warning: max_execution_time is not 30: '.ini_get('max_execution_time'));
if ((int) ini_get('memory_limit') > 64)                                       echoError('Warning: memory_limit is higher than 64 MB: '.ini_get('memory_limit').' MB');
if ((int) ini_get('default_socket_timeout') != 60)                            echoError('Warning: default_socket_timeout is not 60: '.ini_get('default_socket_timeout'));
if (ini_get('implicit_flush'))                                                echoError('Warning: implicit_flush is not Off');
if (ini_get('allow_call_time_pass_reference'))                                echoError('Warning: allow_call_time_pass_reference is not Off');
if (!ini_get('ignore_user_abort'))                                            echoError('Warning: ignore_user_abort is not On');
if (ini_get('session.save_handler') != 'files')                               echoError('Warning: session.save_handler is not \'files\': '.ini_get('session.save_handler'));
/*
if (ini_get('session.save_handler') == 'files') {
   $domainRoot = realPath($_SERVER['DOCUMENT_ROOT']);
   $dirs = explode(DIRECTORY_SEPARATOR, $domainRoot);
   if (($dir=$dirs[sizeOf($dirs)-1])=='httpdocs' || $dir=='htdocs' || $dir=='www' || $dir=='wwwdocs') {
      array_pop($dirs);
      $domainRoot = join(DIRECTORY_SEPARATOR, $dirs);
   }
   if (strPos(realPath(ini_get('session.save_path')), $domainRoot) === false) echoError('Warning: session.save_path doesn\'t point inside the projects directory tree: '.realPath(ini_get('session.save_path')));
}
*/
if (ini_get('session.serialize_handler') != 'php')                            echoError('Warning: session.serialize_handler is not \'php\': '.ini_get('session.serialize_handler'));
if (ini_get('session.auto_start'))                                            echoError('Warning: session.auto_start is not Off');
if (!ini_get('session.use_cookies'))                                          echoError('Warning: session.use_cookies is not On' );
if (ini_get('session.cookie_httponly'))                                       echoError('Warning: session.cookie_httponly is not Off' );
if (!ini_get('session.use_trans_sid'))                                        echoError('Warning: session.use_trans_sid is not On');
if (ini_get('url_rewriter.tags') != 'a=href,area=href,frame=src,iframe=src,form=,fieldset=')
                                                                              echoError('Warning: url_rewriter.tags is not \'a=href,area=href,frame=src,iframe=src,form=,fieldset=\': '.ini_get('url_rewriter.tags'));
if (ini_get('session.bug_compat_42'))                                         echoError('Warning: session.bug_compat_42 is not Off');
if (ini_get('session.bug_compat_42') && !ini_get('session.bug_compat_warn'))  echoError('Warning: session.bug_compat_warn is not On');
if (ini_get('session.referer_check') != '')                                   echoError('Warning: session.referer_check is not \'\': '.ini_get('session.referer_check'));

if (ini_get('sql.safe_mode'))                                                 echoError('Warning: sql.safe_mode is not Off');
if (ini_get('magic_quotes_gpc'))                                              echoError('Warning: magic_quotes_gpc is not Off');
if (ini_get('magic_quotes_runtime'))                                          echoError('Warning: magic_quotes_runtime is not Off');
if (ini_get('magic_quotes_sybase'))                                           echoError('Warning: magic_quotes_sybase is not Off');

$paths = explode(PATH_SEPARATOR, ini_get('include_path'));
for ($i=0; $i < sizeOf($paths); ) if (trim($paths[$i++]) == '')               echoError('Warning: include_path contains empty path on position '.$i);
if (ini_get('auto_detect_line_endings'))                                      echoError('Warning: auto_detect_line_endings is not Off');
if (ini_get('default_mimetype') != 'text/html')                               echoError('Warning: default_mimetype is not \'text/html\': '.ini_get('default_mimetype'));
if (ini_get('default_charset') != 'iso-8859-1')                               echoError('Warning: default_charset is not \'iso-8859-1\': '.ini_get('default_charset'));
if (ini_get('file_uploads'))                                                  echoError('Warning: file_uploads is not Off' );

if (ini_get('asp_tags'))                                                      echoError('Warning: asp_tags is not Off');
if (!ini_get('y2k_compliance'))                                               echoError('Warning: y2k_compliance is not On');
if (strLen(ini_get('date.timezone')) == 0)                                    echoError('Warning: date.timezone is not set');

$current = (int) ini_get('error_reporting');
if (($current & (E_ALL | E_STRICT)) != (E_ALL | E_STRICT))                    echoError('Warning: error_reporting is not E_ALL | E_STRICT: '.ini_get('error_reporting'));
if (ini_get('ignore_repeated_errors'))                                        echoError('Warning: ignore_repeated_errors is not Off');
if (ini_get('ignore_repeated_source'))                                        echoError('Warning: ignore_repeated_source is not Off');
if (!ini_get('log_errors'))                                                   echoError('Warning: log_errors is not On' );
if ((int)ini_get('log_errors_max_len') != 0)                                  echoError('Warning: log_errors_max_len is not 0: '.ini_get('log_errors_max_len'));
if (ini_get('track_errors'))                                                  echoError('Warning: track_errors is not Off' );
if (ini_get('html_errors'))                                                   echoError('Warning: html_errors is not Off');

if (ini_get('enable_dl'))                                                     echoError('Warning: enable_dl is not Off');


// MySQL-Extensions
// ----------------
if (!extension_loaded('mysql'))                                               echoError('Warning: mysql extension is not loaded');
if (!extension_loaded('mysqli') && PHP_VERSION >= '5')                        echoError('Warning: mysqli extension is not loaded');


// Mailkonfiguration
// -----------------
if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isSet($_SERVER['SERVER_ADMIN']))
                                                                              echoError('Windows warning: neither sendmail_path nor sendmail_from are set');
if (!WINDOWS && !ini_get('sendmail_path'))                                    echoError('Warning: sendmail_path is not set');
if (isSet($_SERVER['REQUEST_METHOD']) && !isSet($_SERVER['SERVER_ADMIN']))    echoError('Warning: email address $_SERVER[\'SERVER_ADMIN\'] is not set');


// Opcode-Cache
// ------------
if (!extension_loaded('apc'))                                                 echoError('Warning: could not find an opcode cache');
if (extension_loaded('apc')) {
   if (!ini_get('apc.enabled'))                                               echoError('Warning: apc.enabled is not On');
   if (!ini_get('apc.stat'))                                                  echoError('Warning: apc.stat is not On');     // Off verursacht Fehler (Dateien werden teilweise nicht gecacht)
   if (!ini_get('apc.cache_by_default'))                                      echoError('Warning: apc.cache_by_default is not On');
}


// Entwicklungs- bzw. Produktivsystem: Fehlerausgabe etc.
// ------------------------------------------------------
if (LOCAL) {
   if (!ini_get('display_errors'))                                            echoError('Warning: display_errors is not On');
   if (!ini_get('display_startup_errors'))                                    echoError('Warning: display_startup_errors is not On');
   if ((int) ini_get('output_buffering') != 0)                                echoError('Warning: output_buffering is enabled: '.ini_get('output_buffering'));
}
else {
   if (ini_get('display_errors'))                                             echoError('Warning: display_errors is not Off');
   if (ini_get('display_startup_errors'))                                     echoError('Warning: display_startup_errors is not Off');
   if ((int) ini_get('output_buffering') == 0)                                echoError('Warning: output_buffering is not enabled: '.ini_get('output_buffering'));
}




// Bestätigung, wenn alles ok ist.
if (!isSet($errors)) {
   echo 'Configuration OK';
}
echo '<p>';


function echoError($str) {
   $GLOBALS['errors'] = true;
   echo '<div align="left"><pre style="margin:0; font:normal normal 12px/normal \'Courier New\',courier,serif">'.$str.'</pre></div>';
}

/*
zlib.output_compression = Off
mysql.trace_mode = Off
assert.active = On
*/

phpinfo();
?>
