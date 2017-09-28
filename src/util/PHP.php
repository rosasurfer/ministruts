<?php
namespace rosasurfer\util;

use rosasurfer\config\Config;
use rosasurfer\core\StaticClass;
use rosasurfer\debug\DebugHelper;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\byteValue;
use function rosasurfer\echoPre;
use function rosasurfer\strContains;
use function rosasurfer\strRight;
use function rosasurfer\strRightFrom;
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
     * Execute a process and return STDOUT. Replacement for shell_exec() wich suffers from a Windows bug where a DOS EOF
     * character (0x1A = ASCII 26) in the STDOUT stream causes further reading to stop.
     *
     * @param  string   $cmd                 - shell command to execute
     * @param  string   $stderr   [optional] - if present a variable STDERR will be written to
     * @param  int      $exitCode [optional] - if present a variable the program exit code will be written to
     * @param  string   $dir      [optional] - if present the initial working directory for the command
     * @param  string[] $env      [optional] - if present the environment to *replace* the current one
     *
     * @return string - content of STDOUT
     */
    public static function execProcess($cmd, &$stderr=null, &$exitCode=null, $dir=null, $env=null) {
        if (!is_string($cmd)) throw new IllegalTypeException('Illegal type of parameter $cmd: '.getType($cmd));
        // pOpen() suffers from the same bug

        $descriptors = [
            STDIN  => ['pipe', 'rb'],   // ['file', '/dev/tty', 'r'],
            STDOUT => ['pipe', 'wb'],   // ['file', '/dev/tty', 'w'],
            STDERR => ['pipe', 'wb'],   // ['file', '/dev/tty', 'w'],
        ];
        $pipes = [];

        $hProc = proc_open($cmd, $descriptors, $pipes, $dir, $env, ['bypass_shell'=>true]);

        $stdout = stream_get_contents($pipes[STDOUT]);  // $pipes now looks like this:
        $stderr = stream_get_contents($pipes[STDERR]);  // 0 => writeable handle connected to child stdin
        fClose($pipes[STDIN ]);                         // 1 => readable handle connected to child stdout
        fClose($pipes[STDOUT]);                         // 2 => readable handle connected to child stderr
        fClose($pipes[STDERR]);                         // pipes must be closed before proc_close() to avoid a deadlock

        $exitCode = proc_close($hProc);
        return $stdout;
    }


    /**
     * Check PHP settings, print issues and call phpInfo().
     *
     * PHP_INI_ALL    - entry can be set anywhere
     * PHP_INI_USER   - entry can be set in scripts and in .user.ini
     * PHP_INI_ONLY   - entry can be set in php.ini only
     * PHP_INI_SYSTEM - entry can be set in php.ini and in httpd.conf
     * PHP_INI_PERDIR - entry can be set in php.ini, httpd.conf, .htaccess and in .user.ini
     */
    public static function phpInfo() {
        $issues = [];


        // (1) core configuration
        // ----------------------
        if (!php_ini_loaded_file())                                                                                  $issues[] = 'Error: no "php.ini" configuration file loaded  [setup]';
        /*PHP_INI_PERDIR*/ if (!self::ini_get_bool('short_open_tag'                ))                                $issues[] = 'Error: short_open_tag is not On  [security]';
        /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('asp_tags'                      ) && PHP_VERSION_ID <  70000)     $issues[] = 'Info:  asp_tags is not Off  [standards]';
        /*PHP_INI_ONLY  */ if ( self::ini_get_bool('expose_php'                    ))                                $issues[] = 'Warn:  expose_php is not Off  [security]';
        /*PHP_INI_ALL   */ if ( self::ini_get_int ('max_execution_time'            ) > 30 && !CLI/*hardcoded*/)      $issues[] = 'Info:  max_execution_time is very high: '.ini_get('max_execution_time').'  [resources]';
        /*PHP_INI_ALL   */ if ( self::ini_get_int ('default_socket_timeout'        ) > 30  /*PHP default: 60*/)      $issues[] = 'Info:  default_socket_timeout is very high: '.ini_get('default_socket_timeout').'  [resources]';
        /*PHP_INI_ALL   */ $memoryLimit = self::ini_get_bytes('memory_limit'       );
                           $sWarnLimit  = Config::getDefault()->get('log.warn.memory_limit', '');
                           $warnLimit   = byteValue($sWarnLimit);
            if      ($memoryLimit ==     -1)                                                                         $issues[] = 'Warn:  memory_limit is unlimited  [resources]';
            else if ($memoryLimit <=      0)                                                                         $issues[] = 'Error: memory_limit is invalid: '.ini_get('memory_limit');
            else if ($memoryLimit <  8 * MB)                                                                         $issues[] = 'Info:  memory_limit is very low: '.ini_get('memory_limit').'  [resources]';
            else if ($memoryLimit > 32 * MB && ($warnLimit <= 0 || $warnLimit >= $memoryLimit))                      $issues[] = 'Info:  memory_limit is very high: '.ini_get('memory_limit').'  [resources]';
            if ($warnLimit) {
                if      ($warnLimit <             0)                                                                 $issues[] = 'Error: log.warn.memory_limit is invalid: '.$sWarnLimit.'  [configuration]';
                else if ($warnLimit >= $memoryLimit)                                                                 $issues[] = 'Error: log.warn.memory_limit ('.$sWarnLimit.') is not lower than memory_limit ('.ini_get('memory_limit').')  [configuration]';
                else if ($warnLimit <        4 * MB)                                                                 $issues[] = 'Info:  log.warn.memory_limit ('.$sWarnLimit.') is very low (memory_limit: '.ini_get('memory_limit').')  [configuration]';
                else if ($warnLimit >       64 * MB)                                                                 $issues[] = 'Info:  log.warn.memory_limit ('.$sWarnLimit.') is very high (memory_limit: '.ini_get('memory_limit').')  [configuration]';
            }
        /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('register_globals'              ) && PHP_VERSION_ID <  50400)     $issues[] = 'Error: register_globals is not Off  [security]';
        /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('register_long_arrays'          ) && PHP_VERSION_ID <  50400)     $issues[] = 'Info:  register_long_arrays is not Off  [performance]';
        /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('register_argc_argv'            ) && !CLI/*hardcoded*/)           $issues[] = 'Info:  register_argc_argv is not Off  [performance]';
        /*PHP_INI_PERDIR*/ if (!self::ini_get_bool('auto_globals_jit'              ))                                $issues[] = 'Info:  auto_globals_jit is not On  [performance]';
        /*PHP_INI_ALL   */ if ( self::ini_get_bool('define_syslog_variables'       ) && PHP_VERSION_ID <  50400)     $issues[] = 'Info:  define_syslog_variables is not Off  [performance]';
        /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('allow_call_time_pass_reference') && PHP_VERSION_ID <  50400)     $issues[] = 'Info:  allow_call_time_pass_reference is not Off  [standards]';
        /*PHP_INI_ALL   */ if (!self::ini_get_bool('y2k_compliance'                ) && PHP_VERSION_ID <  50400)     $issues[] = 'Info:  y2k_compliance is not On  [standards]';
        /*PHP_INI_ALL   */ $timezone = ini_get    ('date.timezone'                 );
            if (empty($timezone) && (!isSet($_ENV['TZ'])                             || PHP_VERSION_ID >= 50400))    $issues[] = 'Warn:  date.timezone is not set  [setup]';
        /*PHP_INI_SYSTEM*/ if ( self::ini_get_bool('safe_mode'                     ) && PHP_VERSION_ID <  50400)     $issues[] = 'Error:  safe_mode is not Off  [functionality]';
            /**
             * With 'safe_mode'=On plenty of required function parameters are ignored: e.g. http://php.net/manual/en/function.mysql-connect.php
             */
        /*PHP_INI_ALL   */ if (!empty(ini_get     ('open_basedir'                  )))                               $issues[] = 'Info:  open_basedir is not empty: "'.ini_get('open_basedir').'"  [performance]';
        /*PHP_INI_ALL   */ if (!self::ini_get_bool('auto_detect_line_endings'      ))                                $issues[] = 'Info:  auto_detect_line_endings is not On  [funtionality]';
        /*PHP_INI_SYSTEM*/ if (!self::ini_get_bool('allow_url_fopen'               ))                                $issues[] = 'Info:  allow_url_fopen is not On  [functionality]';
        /*PHP_INI_SYSTEM*/ if ( self::ini_get_bool('allow_url_include'))                                             $issues[] = 'Error: allow_url_include is not Off  [security]';
        /*PHP_INI_ALL   */ foreach (explode(PATH_SEPARATOR, ini_get('include_path' )) as $path) {
            if (!strLen($path)) {                                                                                    $issues[] = 'Warn:  include_path contains an empty path: "'.ini_get('include_path').'"  [setup]';
                break;
        }}


        // (2) error handling
        // ------------------                                                                                        /* E_STRICT =  2048 =    100000000000            */
        /*PHP_INI_ALL   */ $current = self::ini_get_int('error_reporting');                                          /* E_ALL    = 30719 = 111011111111111  (PHP 5.3) */
        $target = (E_ALL|E_STRICT) & ~E_DEPRECATED;                                                                  /* E_ALL    = 32767 = 111111111111111  (PHP 5.4) */
        if ($notCovered=($target ^ $current) & $target)                                                              $issues[] = 'Warn:  error_reporting does not cover '.DebugHelper::errorLevelToStr($notCovered).'  [standards]';
        if (WINDOWS) {/*always development*/
            /*PHP_INI_ALL*/ if (!self::ini_get_bool('display_errors'                )) /*bool|string:stderr*/        $issues[] = 'Info:  display_errors is not On  [setup]';
            /*PHP_INI_ALL*/ if (!self::ini_get_bool('display_startup_errors'        ))                               $issues[] = 'Info:  display_startup_errors is not On  [setup]';
        }
        else {
            /*PHP_INI_ALL*/ if ( self::ini_get_bool('display_errors'                )) /*bool|string:stderr*/        $issues[] = 'Warn:  display_errors is not Off  [security]';
            /*PHP_INI_ALL*/ if ( self::ini_get_bool('display_startup_errors'        ))                               $issues[] = 'Warn:  display_startup_errors is not Off  [security]';
        }
        /*PHP_INI_ALL   */ if ( self::ini_get_bool('ignore_repeated_errors'        ))                                $issues[] = 'Info:  ignore_repeated_errors is not Off  [resources]';
        /*PHP_INI_ALL   */ if ( self::ini_get_bool('ignore_repeated_source'        ))                                $issues[] = 'Info:  ignore_repeated_source is not Off  [resources]';
        /*PHP_INI_ALL   */ if (!self::ini_get_bool('track_errors'                  ))                                $issues[] = 'Info:  track_errors is not On  [functionality]';
        /*PHP_INI_ALL   */ if ( self::ini_get_bool('html_errors'                   ))                                $issues[] = 'Warn:  html_errors is not Off  [functionality]';
        /*PHP_INI_ALL   */ if (!self::ini_get_bool('log_errors'                    ))                                $issues[] = 'Error: log_errors is not On  [setup]';
        /*PHP_INI_ALL   */ $bytes = self::ini_get_bytes('log_errors_max_len'       );
            if      ($bytes <  0)   /* 'log_errors' and 'log_errors_max_len' do not affect */                        $issues[] = 'Error: log_errors_max_len is invalid: '.ini_get('log_errors_max_len');
            else if ($bytes != 0)   /* explicit calls to the function error_log()          */                        $issues[] = 'Warn:  log_errors_max_len is not 0: '.ini_get('log_errors_max_len').'  [functionality]';
        /*PHP_INI_ALL   */ $errorLog = ini_get('error_log');
        if (!empty($errorLog) && $errorLog!='syslog') {
            if (is_file($errorLog)) {
                $hFile = @fOpen($errorLog, 'ab');         // try to open
                if (is_resource($hFile)) fClose($hFile);
                else                                                                                                 $issues[] = 'Error: error_log "'.$errorLog.'" file is not writable  [setup]';
            }
            else {
                $hFile = @fOpen($errorLog, 'wb');         // try to create
                if (is_resource($hFile)) fClose($hFile);
                else                                                                                                 $issues[] = 'Error: error_log "'.$errorLog.'" directory is not writable  [setup]';
                is_file($errorLog) && @unlink($errorLog);
            }
        }


        // (3) input sanitizing
        // --------------------
        if (PHP_VERSION_ID < 50400) {
            /*PHP_INI_ALL   */ if      (self::ini_get_bool('magic_quotes_sybase' )) /*overrides 'magic_quotes_gpc'*/ $issues[] = 'Error: magic_quotes_sybase is not Off  [standards]';
            /*PHP_INI_PERDIR*/ else if (self::ini_get_bool('magic_quotes_gpc'))                                      $issues[] = 'Error: magic_quotes_gpc is not Off  [standards]';
            /*PHP_INI_ALL   */ if      (self::ini_get_bool('magic_quotes_runtime'))                                  $issues[] = 'Error: magic_quotes_runtime is not Off  [standards]';
        }
        /*PHP_INI_SYSTEM*/    if      (self::ini_get_bool('sql.safe_mode'       ))                                   $issues[] = 'Warn:  sql.safe_mode is not Off  [setup]';


        // (4) request & HTML handling
        // ---------------------------
        /*PHP_INI_PERDIR*/ $order = ini_get('request_order'); /*if empty fall-back to order of GPC in 'variables_order'*/
        if (empty($order)) {
            /*PHP_INI_PERDIR*/ $order = ini_get('variables_order');
            $newOrder = '';
            $len      = strLen($order);
            for ($i=0; $i < $len; $i++) {
                if (in_array($char=$order[$i], ['G','P','C']))
                    $newOrder .= $char;
            }
            $order = $newOrder;
        }                  if ($order != 'GP')                                                                       $issues[] = 'Error: request_order is not "GP": "'.(empty(ini_get('request_order')) ? '" (empty) => variables_order:"':'').$order.'"  [standards]';
        /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('always_populate_raw_post_data' ) && PHP_VERSION_ID <  70000)     $issues[] = 'Info:  always_populate_raw_post_data is not Off  [performance]';
        /*PHP_INI_ALL   */ if (       ini_get     ('arg_separator.output'          ) != '&')                         $issues[] = 'Warn:  arg_separator.output is not "&": "'.ini_get('arg_separator.output').'"  [standards]';
        /*PHP_INI_ALL   */ if (!self::ini_get_bool('ignore_user_abort'             ))                                $issues[] = 'Warn:  ignore_user_abort is not On  [standards]';
        /*PHP_INI_SYSTEM*/ if ( self::ini_get_bool('file_uploads'                  )) {                              $issues[] = 'Info:  file_uploads is not Off  [security]';
            // TODO: check "upload_tmp_dir"
        }
        /*PHP_INI_ALL   */ if (            ini_get('default_mimetype'              )  != 'text/html')                $issues[] = 'Info:  default_mimetype is not "text/html": "'.ini_get('default_mimetype').'"  [standards]';
        /*PHP_INI_ALL   */ if ( strToLower(ini_get('default_charset'               )) != 'utf-8')                    $issues[] = 'Info:  default_charset is not "UTF-8": "'.ini_get('default_charset').'"  [standards]';
        /*PHP_INI_ALL   */ if ( self::ini_get_bool('implicit_flush'                ) && !CLI/*hardcoded*/)           $issues[] = 'Warn:  implicit_flush is not Off  [performance]';
        /*PHP_INI_PERDIR*/ $buffer = self::ini_get_bytes('output_buffering'        );
        if (!CLI) {
            if      ($buffer < 0)                                                                                    $issues[] = 'Error: output_buffering is invalid: '.ini_get('output_buffering');
            else if (!$buffer)                                                                                       $issues[] = 'Info:  output_buffering is not enabled  [performance]';
        }
        // TODO: /*PHP_INI_ALL*/ "zlib.output_compression"


        // (5) session related
        // -------------------
        /*PHP_INI_ALL   */ if (       ini_get     ('session.save_handler') != 'files')                               $issues[] = 'Info:  session.save_handler is not "files": "'.ini_get('session.save_handler').'"';
        // TODO: check "session.save_path"
        /*PHP_INI_ALL   */ if (       ini_get     ('session.serialize_handler') != 'php')                            $issues[] = 'Info:  session.serialize_handler is not "php": "'.ini_get('session.serialize_handler').'"';
        /*PHP_INI_PERDIR*/ if ( self::ini_get_bool('session.auto_start'))                                            $issues[] = 'Info:  session.auto_start is not Off  [performance]';
        /*
        Caution: If you turn on session.auto_start then the only way to put objects into your sessions is to load its class
                 definition using auto_prepend_file in which you load the class definition else you will have to serialize()
                 your object and unserialize() it afterwards.
        */
        if (PHP_VERSION_ID < 50400) {
            /*PHP_INI_ALL*/ if ( self::ini_get_bool('session.bug_compat_42')) {                                      $issues[] = 'Info:  session.bug_compat_42 is not Off';
            /*PHP_INI_ALL*/ if (!self::ini_get_bool('session.bug_compat_warn'))                                      $issues[] = 'Info:  session.bug_compat_warn is not On';
        }}
        /*PHP_INI_ALL   */ if (       ini_get     ('session.referer_check') != '')                                   $issues[] = 'Warn:  session.referer_check is not "": "'.ini_get('session.referer_check').'"  [functionality]';
        /*PHP_INI_ALL   */ if (!self::ini_get_bool('session.use_strict_mode') && PHP_VERSION_ID >= 50502)            $issues[] = 'Warn:  session.use_strict_mode is not On  [security]';
        /*PHP_INI_ALL   */ if (!self::ini_get_bool('session.use_cookies'))                                           $issues[] = 'Warn:  session.use_cookies is not On  [security]';
        /*PHP_INI_ALL   */ if (!self::ini_get_bool('session.use_only_cookies'))                                      $issues[] = 'Warn:  session.use_only_cookies is not On  [security]';
        /*PHP_INI_ALL   */ if ( self::ini_get_bool('session.use_trans_sid')) {
                           if (!self::ini_get_bool('session.use_only_cookies'))                                      $issues[] = 'Warn:  session.use_trans_sid is On  [security]';
                           else                                                                                      $issues[] = 'Info:  session.use_trans_sid is On';
        }





        // (6) mail related
        // ----------------
        /*PHP_INI_ALL   */ //sendmail_from
        if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isSet($_SERVER['SERVER_ADMIN']))   $issues[] = 'Warn:  On Windows and neither sendmail_path nor sendmail_from are set';
        /*PHP_INI_SYSTEM*/ if (!WINDOWS && !ini_get('sendmail_path'))                                                $issues[] = 'Warn:  sendmail_path is not set';
        /*PHP_INI_PERDIR*/ if (ini_get('mail.add_x_header'))                                                         $issues[] = 'Warn:  mail.add_x_header is not Off';


        // (7) extensions
        // --------------
        /*PHP_INI_SYSTEM*/ if (ini_get('enable_dl'))                                                                 $issues[] = 'Warn:  enable_dl is not Off';
        if (!extension_loaded('ctype'))                                                                              $issues[] = 'Info:  ctype extension is not loaded';
        if (!extension_loaded('curl'))                                                                               $issues[] = 'Info:  curl extension is not loaded';
        if (!extension_loaded('iconv'))                                                                              $issues[] = 'Info:  iconv extension is not loaded';
        if (!extension_loaded('json'))                                                                               $issues[] = 'Info:  JSON extension is not loaded';
        if (!extension_loaded('mysql'))                                                                              $issues[] = 'Info:  MySQL extension is not loaded';
        if (!extension_loaded('mysqli'))                                                                             $issues[] = 'Info:  MySQLi extension is not loaded';
        if (!WINDOWS && !extension_loaded('sysvsem'))                                                                $issues[] = 'Info:  System-V Semaphore extension is not loaded';

        // check Composer defined requirements
        $appRoot = Config::getDefault()->get('app.dir.root');
        if (is_file($file=$appRoot.'/composer.json') && extension_loaded('json')) {
            $composer = json_decode(file_get_contents($file), true);
            if (isSet($composer['require']) && is_array($composer['require'])) {
                foreach ($composer['require'] as $name => $version) {
                    $name = trim(strToLower($name));
                    if (in_array($name, ['php', 'php-64bit', 'hhvm']) || strContains($name, '/')) continue;
                    if (strStartsWith($name, 'ext-')) $name = strRight($name, -4);
                    if (!extension_loaded($name))                                                                    $issues[] = 'Warn:  '.$name.' extension is not loaded';
                }
            }
        }


        // (8) Opcode cache
        // ----------------
        if (extension_loaded('apc')) {
            //if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7')                                       $issues[] = 'Warn:  You are running a buggy APC version (a version < 3.1.3 or >= 3.1.7 is recommended): '.phpVersion('apc');
            ///*PHP_INI_SYSTEM*/ if (!ini_get('apc.enabled'))                                                        $issues[] = 'Warn:  apc.enabled is not On [performance]';      // warning "Potential cache slam averted for key '...'" http://bugs.php.net/bug.php?id=58832
            ///*PHP_INI_SYSTEM*/ if ( ini_get('apc.report_autofilter'))                                              $issues[] = 'Warn:  apc.report_autofilter is not Off';
            //
            //if (WINDOWS) {       // development
            //    /*PHP_INI_SYSTEM*/ if     (ini_get('apc.stat'))                                                    $issues[] = 'Warn:  apc.stat is not Off';
            //    /*PHP_INI_ALL   */ elseif (ini_get('apc.cache_by_default'))                                        $issues[] = 'Warn:  apc.cache_by_default is not Off';          // "On" may crash some Windows APC versions (apc-error: cannot redeclare class ***)
            //}                                                                                                                                                                     // Windows: if apc.stat="Off" this option MUST be "Off"
            //else {               // production
            //    /*PHP_INI_ALL   */ if (!ini_get('apc.cache_by_default'))                                           $issues[] = 'Warn:  apc.cache_by_default is not On';
            //    /*PHP_INI_SYSTEM*/ if ( ini_get('apc.stat'))                                                       $issues[] = 'Warn:  apc.stat is not Off';                      // we want to cache fs-stat calls
            //    /*PHP_INI_SYSTEM*/ if (!ini_get('apc.write_lock'))                                                 $issues[] = 'Warn:  apc.write_lock is not On';                 // "Off" for perfomance; file modifications in production shall be disabled
            //
            //    if (phpVersion('apc') >= '3.1.3' && phpVersion('apc') < '3.1.7') {
            //        /*PHP_INI_SYSTEM*/ if (ini_get('apc.include_once_override'))                                   $issues[] = 'Warn:  apc.include_once_override is not Off';     // never use slow include_once()/require_once()
            //    }
            //    /*PHP_INI_SYSTEM*/ elseif (!ini_get('apc.include_once_override'))                                  $issues[] = 'Warn:  apc.include_once_override is not On';
            //}
        }
        elseif (extension_loaded('zend opcache')) {
            /*PHP_INI_ALL   */ if (!ini_get('opcache.enable'))                                                       $issues[] = 'Warn:  opcache.enable is not On [performance]';
        }
        else                                                                                                         $issues[] = 'Warn:  No opcode cache found [performance]';


        // (9) break out of unfortunate HTML tags and show results followed by phpInfo()
        if (!CLI) {
            ?>
            <div align="left" style="display:initial; visibility:initial; clear:both;
                                     position:relative; z-index:65535; top:initial; left:initial;
                                     width:initial; height:initial;
                                     margin:0; padding:4px;
                                     font:normal normal 12px/normal arial,helvetica,sans-serif;
                                     color:black; background-color:white">
            <?php
        }

        // show issues or confirm if none are found
        if ($issues) echoPre('PHP configuration issues:'.NL.'-------------------------'.NL.join(NL, $issues));
        else         echoPre('PHP configuration OK');

        // call phpInfo() if on a web server
        if (!CLI) {
            $get = $_GET;
            $isConfig = isSet($get['__config__']);
            unset($get['__phpinfo__'], $get['__config__']);

            // before/after link
            if ($isConfig) $queryStr = http_build_query($get + ['__phpinfo__'=>'',                  ], null, '&amp;');
            else           $queryStr = http_build_query($get + ['__config__' =>'', '__phpinfo__'=>''], null, '&amp;');
            ?>
            <div style="clear:both; text-align:center; margin:0 0 15px 0; padding:20px 0 0 0; font-size:12px; font-weight:bold; font-family:sans-serif">
                <a href="?<?=$queryStr?>" style="display:inline-block; min-width:220px; min-height:15px; margin:0 10px; padding:10px 0; background-color:#ccf; color:#222; border:1px outset #666; white-space:nowrap">
                   <?=$isConfig ? 'Hide':'Show'?> Application Configuration
                </a>
            </div>
            <?php
            echo NL;
            phpInfo();
        }
    }


    /**
     * Return the value of a php.ini option as a boolean.
     *
     * NOTE: Never use ini_get() to read a php.ini bool value as it will return the plain string passed to ini_set().
     *
     * @param  string $option
     *
     * @return bool
     */
    public static function ini_get_bool($option) {
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
     * NOTE: Never use ini_get() to read a php.ini integer value as it will return the plain string passed to ini_set().
     *
     * @param  string $option
     *
     * @return int
     */
    public static function ini_get_int($option) {
        return (int)ini_get($option);
    }


    /**
     * Return the value of a php.ini option as a byte value.
     *
     * NOTE: Never use ini_get() to read a php.ini byte value as it will return the plain string passed to ini_set().
     *
     * @param  string $option
     *
     * @return int
     */
    public static function ini_get_bytes($option) {
        return byteValue(ini_get($option));
    }


    /**
     * Set the specified php.ini setting. Opposite to the built-in PHP function this method does not return the previously
     * set option value but a boolean success status. Used to detect assignment errors if the access level of the specified
     * option is not PHP_INI_ALL or PHP_INI_USER or the option is locked by the server configuration
     * (php_admin_value/php_admin_flag).
     *
     * @param  string          $option
     * @param  bool|int|string $value
     * @param  bool            $throwException [optional] - whether or not to throw an exception on errors (default: yes)
     *
     * @return bool - success status
     */
    public static function ini_set($option, $value, $throwException=true) {
        if (is_bool($value))
            $value = (int) $value;

        $oldValue = ini_set($option, $value);
        if ($oldValue !== false)
            return true;

        $oldValue = ini_get($option);       // ini_set() caused an error
        $newValue = (string) $value;

        if ($oldValue == $newValue)         // the error can be ignored
            return true;

        if ($throwException) throw new RuntimeException('Cannot set php.ini option "'.$option.'" (former value="'.$oldValue.'")');
        return false;
    }


    /**
     * Return the query string of the current url (if any).
     *
     * @return string
     */
    private static function getUrlQueryString() {
        // The variable $_SERVER['QUERY_STRING'] is set by the server and can differ, e.g. it might hold additional
        // parameters or it might be empty (nginx).

        if (isSet($_SERVER['QUERY_STRING']) && strLen($_SERVER['QUERY_STRING'])) {
            $query = $_SERVER['QUERY_STRING'];
        }
        else {
            $query = strRightFrom($_SERVER['REQUEST_URI'], '?');
        }
        return $query;
    }


    /**
     * Return the hash string of the current url (if any).
     *
     * @return string - hash including the hash mark or an empty string
     */
    private static function getUrlHash() {
        $queryStr = self::getUrlQueryString();
        return strRightFrom($queryStr, '#', 1, true);
    }
}
