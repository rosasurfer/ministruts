<?php
namespace rosasurfer\util;

use rosasurfer\config\Config;
use rosasurfer\config\ConfigInterface as IConfig;
use rosasurfer\core\StaticClass;
use rosasurfer\debug\DebugHelper;
use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\RuntimeException;

use function rosasurfer\echoPre;
use function rosasurfer\ini_get_bool;
use function rosasurfer\ini_get_bytes;
use function rosasurfer\ini_get_int;
use function rosasurfer\php_byte_value;
use function rosasurfer\stderror;
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
     * - pOpen() suffers from the same bug
     * - passThru() does not capture STDERR
     *
     * @param  string   $cmd                 - external command to execute
     * @param  string   $stderr   [optional] - if present a variable the contents of STDERR will be written to
     * @param  int      $exitCode [optional] - if present a variable the commands's exit code will be written to
     * @param  string   $dir      [optional] - if present the initial working directory for the command
     * @param  string[] $env      [optional] - if present the environment to *replace* the current one
     * @param  array    $options  [optional] - additional options controlling runtime behaviour: <br>
     *          "stdout-passthrough" => bool:  Whether or not to additionally pass-through (print) the contents of STDOUT. <br>
     *                                         This option will not affect the return value. <br>
     *                                         (default: no) <br>
     *          "stderr-passthrough" => bool:  whether or not to additionally pass-through (print) the contents of STDERR. <br>
     *                                         This option will not affect the return value. <br>
     *                                         (default: no) <br>
     *
     * @return string - contents of STDOUT
     */
    public static function execProcess($cmd, &$stderr=null, &$exitCode=null, $dir=null, $env=null, array $options=[]) {
        if (!is_string($cmd)) throw new IllegalTypeException('Illegal type of parameter $cmd: '.getType($cmd));

        // check whether the process needs to be watched asynchronously
        $argc         = func_num_args();
        $needStderr   = ($argc > 1);
        $needExitCode = ($argc > 2);
        $stdoutPassthrough = isSet($options['stdout-passthrough']) && $options['stdout-passthrough'];
        $stderrPassthrough = isSet($options['stderr-passthrough']) && $options['stderr-passthrough'];

        if (!$needStderr && !$needExitCode && !WINDOWS)
            return \shell_exec($cmd);                       // the process doesn't need watching and we can go with shell_exec()

        // we must use proc_open()/proc_close()
        $descriptors = [                                    // "pipes" or "files":
            ($STDIN =0) => ['pipe', 'rb'],                  // ['file', '/dev/tty', 'rb'],
            ($STDOUT=1) => ['pipe', 'wb'],                  // ['file', '/dev/tty', 'wb'],
            ($STDERR=2) => ['pipe', 'wb'],                  // ['file', '/dev/tty', 'wb'],
        ];
        $pipes = [];

        try {
            $hProc = proc_open($cmd, $descriptors, $pipes, $dir, $env, ['bypass_shell'=>true]);
        }
        catch (\Exception $ex) {
            if (!$ex instanceof IRosasurferException) $ex = new RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
            $match = null;
            if (WINDOWS && preg_match('/proc_open\(\): CreateProcess failed, error code - ([0-9]+)/i', $ex->getMessage(), $match)) {
                $error = Windows::errorToString((int) $match[1]);
                if ($error != $match[1]) $ex->addMessage($match[1].': '.$error);
            }
            throw $ex->addMessage('CMD: "'.$cmd.'"');
        }

        // the process doesn't need asynchronous watching
        if (!$stdoutPassthrough && !$stderrPassthrough) {
            $stdout = stream_get_contents($pipes[$STDOUT]);
            $stderr = stream_get_contents($pipes[$STDERR]);
            fClose($pipes[$STDIN ]);                        // $pipes[0] => writeable handle connected to the child's STDIN
            fClose($pipes[$STDOUT]);                        // $pipes[1] => readable handle connected to the child's STDOUT
            fClose($pipes[$STDERR]);                        // $pipes[2] => readable handle connected to the child's STDERR
            $exitCode = proc_close($hProc);                 // we must close the pipes before proc_close() to avoid a deadlock
            return $stdout;
        }

        // the process needs to be watched asynchronously
        fClose             ($pipes[$STDIN]);
        stream_set_blocking($pipes[$STDOUT], false);
        stream_set_blocking($pipes[$STDERR], false);

        $observed = [                                                   // streams to watch
            (int)$pipes[$STDOUT] => $pipes[$STDOUT],
            (int)$pipes[$STDERR] => $pipes[$STDERR],
        ];
        $stdout = $stderr = '';                                         // stream contents
        $handlers = [                                                   // a handler for each stream
            (int)$pipes[$STDOUT] => function($line) use (&$stdout, $stdoutPassthrough) {
                $stdout .= $line; if ($stdoutPassthrough) echo $line;
            },
            (int)$pipes[$STDERR] => function($line) use (&$stderr, $stderrPassthrough) {
                $stderr .= $line; if ($stderrPassthrough) stderror($line);
            },
        ];
        $null = null;
        do {
            $readable = $observed;
            $changes = stream_select($readable, $null, $null, $seconds=0, $microseconds=200000);    // timeout = 0.2 sec
            foreach ($readable as $stream) {
                if (($line=fGets($stream)) === false) {                 // this covers fEof() too
                    fClose($stream);
                    unset($observed[(int)$stream]);                     // close and remove from observed streams
                    continue;
                }
                do {
                    $handlers[(int)$stream]($line);                     // process incoming content
                } while (!WINDOWS && ($line=fGets($stream))!==false);   // on Windows a second call blocks
            }
        } while ($observed);                                            // loop until all observable streams are closed

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
        /** @var IConfig|null $config */
        $config = Config::getDefault();
        $issues = [];

        // core configuration
        // ------------------
        if (!php_ini_loaded_file())                                                                                  $issues[] = 'Error: no "php.ini" configuration file loaded  [setup]';
        /*PHP_INI_PERDIR*/ if (      !ini_get_bool('short_open_tag'                ))                                $issues[] = 'Error: short_open_tag is not On  [security]';
        /*PHP_INI_PERDIR*/ if (       ini_get_bool('asp_tags'                      ) && PHP_VERSION_ID <  70000)     $issues[] = 'Info:  asp_tags is not Off  [standards]';
        /*PHP_INI_ONLY  */ if (       ini_get_bool('expose_php'                    ) && !CLI)                        $issues[] = 'Warn:  expose_php is not Off  [security]';
        /*PHP_INI_ALL   */ if (       ini_get_int ('max_execution_time'            ) > 30 && !CLI /*hardcoded*/)     $issues[] = 'Info:  max_execution_time is very high: '.ini_get('max_execution_time').'  [resources]';
        /*PHP_INI_ALL   */ if (       ini_get_int ('default_socket_timeout'        ) > 30   /*PHP default: 60*/)     $issues[] = 'Info:  default_socket_timeout is very high: '.ini_get('default_socket_timeout').'  [resources]';
        /*PHP_INI_ALL   */ $memoryLimit = ini_get_bytes('memory_limit');
            if      ($memoryLimit ==    -1)                                                                          $issues[] = 'Warn:  memory_limit is unlimited  [resources]';
            else if ($memoryLimit <=     0)                                                                          $issues[] = 'Error: memory_limit is invalid: '.ini_get('memory_limit');
            else if ($memoryLimit <  32*MB)                                                                          $issues[] = 'Warn:  memory_limit is very low: '.ini_get('memory_limit').'  [resources]';
            else if ($memoryLimit > 128*MB)                                                                          $issues[] = 'Info:  memory_limit is very high: '.ini_get('memory_limit').'  [resources]';

            if ($config) {
                $sWarnLimit  = $config->get('log.warn.memory_limit', '');
                $warnLimit   = php_byte_value($sWarnLimit);
                if ($warnLimit) {
                    if      ($warnLimit <             0)                                                                 $issues[] = 'Error: log.warn.memory_limit is invalid: '.$sWarnLimit.'  [configuration]';
                    else if ($warnLimit >= $memoryLimit)                                                                 $issues[] = 'Error: log.warn.memory_limit ('.$sWarnLimit.') is not lower than memory_limit ('.ini_get('memory_limit').')  [configuration]';
                    else if ($warnLimit >        128*MB)                                                                 $issues[] = 'Info:  log.warn.memory_limit ('.$sWarnLimit.') is very high (memory_limit: '.ini_get('memory_limit').')  [configuration]';
                }
            }
        /*PHP_INI_PERDIR*/ if (       ini_get_bool('register_globals'              ) && PHP_VERSION_ID <  50400)     $issues[] = 'Error: register_globals is not Off  [security]';
        /*PHP_INI_PERDIR*/ if (       ini_get_bool('register_long_arrays'          ) && PHP_VERSION_ID <  50400)     $issues[] = 'Info:  register_long_arrays is not Off  [performance]';
        /*PHP_INI_PERDIR*/ if (       ini_get_bool('register_argc_argv'            ) && !CLI      /*hardcoded*/)     $issues[] = 'Info:  register_argc_argv is not Off  [performance]';
        /*PHP_INI_PERDIR*/ if (      !ini_get_bool('auto_globals_jit'              ))                                $issues[] = 'Info:  auto_globals_jit is not On  [performance]';
        /*PHP_INI_ALL   */ if (       ini_get_bool('define_syslog_variables'       ) && PHP_VERSION_ID <  50400)     $issues[] = 'Info:  define_syslog_variables is not Off  [performance]';
        /*PHP_INI_PERDIR*/ if (       ini_get_bool('allow_call_time_pass_reference') && PHP_VERSION_ID <  50400)     $issues[] = 'Info:  allow_call_time_pass_reference is not Off  [standards]';
        /*PHP_INI_ALL   */ if (      !ini_get_bool('y2k_compliance'                ) && PHP_VERSION_ID <  50400)     $issues[] = 'Info:  y2k_compliance is not On  [standards]';
        /*PHP_INI_ALL   */ $timezone = ini_get    ('date.timezone'                 );
            if (empty($timezone) && (empty($_SERVER['TZ'])                           || PHP_VERSION_ID >= 50400))    $issues[] = 'Warn:  date.timezone is not set  [setup]';
        /*PHP_INI_SYSTEM*/ if (       ini_get_bool('safe_mode'                     ) && PHP_VERSION_ID <  50400)     $issues[] = 'Error:  safe_mode is not Off  [functionality]';
            /**
             * With 'safe_mode'=On plenty of required function parameters are ignored: e.g. http://php.net/manual/en/function.mysql-connect.php
             */
        /*PHP_INI_ALL   */ if (!empty(ini_get     ('open_basedir'                  )))                               $issues[] = 'Info:  open_basedir is not empty: "'.ini_get('open_basedir').'"  [performance]';
        /*PHP_INI_SYSTEM*/ if (      !ini_get_bool('allow_url_fopen'               ))                                $issues[] = 'Info:  allow_url_fopen is not On  [functionality]';
        /*PHP_INI_SYSTEM*/ if (       ini_get_bool('allow_url_include'))                                             $issues[] = 'Error: allow_url_include is not Off  [security]';
        /*PHP_INI_ALL   */ foreach (explode(PATH_SEPARATOR, ini_get('include_path' )) as $path) {
            if (!strLen($path)) {                                                                                    $issues[] = 'Warn:  include_path contains an empty path: "'.ini_get('include_path').'"  [setup]';
                break;
        }}

        // error handling
        // --------------
        /*PHP_INI_ALL   */ $current = ini_get_int('error_reporting');
            $target = E_ALL & ~E_DEPRECATED;
            if ($notCovered=($target ^ $current) & $target)                                                          $issues[] = 'Warn:  error_reporting does not cover '.DebugHelper::errorLevelToStr($notCovered).'  [standards]';
        if (!WINDOWS) { /* Windows is always development */
            /*PHP_INI_ALL*/ if (       ini_get_bool('display_errors'               )) /*bool|string:stderr*/         $issues[] = 'Warn:  display_errors is not Off  [security]';
            /*PHP_INI_ALL*/ if (       ini_get_bool('display_startup_errors'       ))                                $issues[] = 'Warn:  display_startup_errors is not Off  [security]';
        }
        /*PHP_INI_ALL   */ if (       ini_get_bool('ignore_repeated_errors'        ))                                $issues[] = 'Info:  ignore_repeated_errors is not Off  [resources]';
        /*PHP_INI_ALL   */ if (       ini_get_bool('ignore_repeated_source'        ))                                $issues[] = 'Info:  ignore_repeated_source is not Off  [resources]';
        /*PHP_INI_ALL   */ if (      !ini_get_bool('track_errors'                  ))                                $issues[] = 'Info:  track_errors is not On  [functionality]';
        /*PHP_INI_ALL   */ if (       ini_get_bool('html_errors'                   ))                                $issues[] = 'Warn:  html_errors is not Off  [functionality]';
        /*PHP_INI_ALL   */ if (      !ini_get_bool('log_errors'                    ))                                $issues[] = 'Error: log_errors is not On  [setup]';
        /*PHP_INI_ALL   */ $bytes = ini_get_bytes('log_errors_max_len');
            if      ($bytes <  0)   /* 'log_errors' and 'log_errors_max_len' do not affect */                        $issues[] = 'Error: log_errors_max_len is invalid: '.ini_get('log_errors_max_len');
            else if ($bytes != 0)   /* explicit calls to the function error_log()          */                        $issues[] = 'Warn:  log_errors_max_len is not 0: '.ini_get('log_errors_max_len').'  [functionality]';
        /*PHP_INI_ALL   */ $errorLog = ini_get('error_log');
            if (!empty($errorLog) && $errorLog!='syslog') {
                if (is_file($errorLog)) {
                    $hFile = @fOpen($errorLog, 'ab');         // try to open
                    if (is_resource($hFile)) fClose($hFile);
                    else                                                                                             $issues[] = 'Error: error_log "'.$errorLog.'" file is not writable  [setup]';
                }
                else {
                    $hFile = @fOpen($errorLog, 'wb');         // try to create
                    if (is_resource($hFile)) fClose($hFile);
                    else                                                                                             $issues[] = 'Error: error_log "'.$errorLog.'" directory is not writable  [setup]';
                    is_file($errorLog) && @unlink($errorLog);
                }
            }

        // input sanitizing
        // ----------------
        if (PHP_VERSION_ID < 50400) {
            /*PHP_INI_ALL   */ if      (      ini_get_bool('magic_quotes_sybase' )) /*overrides 'magic_quotes_gpc'*/ $issues[] = 'Error: magic_quotes_sybase is not Off  [standards]';
            /*PHP_INI_PERDIR*/ else if (      ini_get_bool('magic_quotes_gpc'))                                      $issues[] = 'Error: magic_quotes_gpc is not Off  [standards]';
            /*PHP_INI_ALL   */ if      (      ini_get_bool('magic_quotes_runtime'))                                  $issues[] = 'Error: magic_quotes_runtime is not Off  [standards]';
        }
        /*PHP_INI_SYSTEM*/     if      (      ini_get_bool('sql.safe_mode'       ))                                  $issues[] = 'Warn:  sql.safe_mode is not Off  [setup]';

        // request & HTML handling
        // -----------------------
        /*PHP_INI_PERDIR*/ $order = ini_get('request_order'); /*if empty automatic fall-back to GPC order in "variables_order"*/
            if (empty($order)) {
                /*PHP_INI_PERDIR*/ $order = ini_get('variables_order');
                $newOrder = '';
                $len      = strLen($order);
                for ($i=0; $i < $len; $i++) {
                    if (in_array($char=$order[$i], ['G','P','C']))
                        $newOrder .= $char;
                }
                $order = $newOrder;
            }              if ($order != 'GP')                                                                       $issues[] = 'Error: request_order is not "GP": "'.(empty(ini_get('request_order')) ? '" (empty) => variables_order:"':'').$order.'"  [standards]';
        /*PHP_INI_PERDIR*/ if (       ini_get_bool('always_populate_raw_post_data' ) && PHP_VERSION_ID <  70000)     $issues[] = 'Info:  always_populate_raw_post_data is not Off  [performance]';
        /*PHP_INI_PERDIR*/ if (      !ini_get_bool('enable_post_data_reading'      ) && PHP_VERSION_ID >= 50400)     $issues[] = 'Warn:  enable_post_data_reading is not On  [request handling]';
        /*PHP_INI_ALL   */ if (       ini_get     ('arg_separator.output'          ) != '&')                         $issues[] = 'Warn:  arg_separator.output is not "&": "'.ini_get('arg_separator.output').'"  [standards]';
        /*PHP_INI_ALL   */ if (      !ini_get_bool('ignore_user_abort'             ) && !CLI)                        $issues[] = 'Warn:  ignore_user_abort is not On  [standards]';
        /*PHP_INI_PERDIR*/ $postMaxSize = ini_get_bytes('post_max_size');
            $localMemoryLimit  = $memoryLimit;
            $globalMemoryLimit = php_byte_value(ini_get_all()['memory_limit']['global_value']);
            // The memory_limit needs to be raised accordingly before script entry, not in the script.
            // If the memory_limit is too low on script entry PHP may crash for larger requests with "Out of memory" (e.g. file uploads).
            if      ($globalMemoryLimit < $postMaxSize      )                                                        $issues[] = 'Error: global memory_limit "'.ini_get_all()['memory_limit']['global_value'].'" is too low for post_max_size "'.ini_get('post_max_size').'"  [request handling]';
            else if ($globalMemoryLimit < $postMaxSize+20*MB) /*PHP needs about 20MB for the runtime*/               $issues[] = 'Info:  global memory_limit "'.ini_get_all()['memory_limit']['global_value'].'" is very low for post_max_size "'.ini_get('post_max_size').'"  [request handling]';
            if      ($localMemoryLimit  < $postMaxSize)                                                              $issues[] = 'Error: local memory_limit "'.ini_get('memory_limit').'" is too low for post_max_size "'.ini_get('post_max_size').'"  [request handling]';
            else if ($localMemoryLimit  < $postMaxSize+20*MB)                                                        $issues[] = 'Warn:  local memory_limit "'.ini_get('memory_limit').'" is very low for post_max_size "'.ini_get('post_max_size').'"  [request handling]';
        /*PHP_INI_SYSTEM*/ if (       ini_get_bool('file_uploads'                  ) && !CLI) {                      $issues[] = 'Info:  file_uploads is not Off  [security]';
        /*PHP_INI_PERDIR*/ if (       ini_get_bytes('upload_max_filesize') >= $postMaxSize)                          $issues[] = 'Error: post_max_size "'.ini_get('post_max_size').'" is not larger than upload_max_filesize "'.ini_get('upload_max_filesize').'"  [request handling]';
        /*PHP_INI_SYSTEM*/ $dir = ini_get($name = 'upload_tmp_dir');
            $file = null;
            if (trim($dir) == '') {                                                                                  $issues[] = 'Info:  '.$name.' is not set  [setup]';
                $dir  = sys_get_temp_dir();
                $name = 'sys_get_temp_dir()';
            }
            if (!is_dir($dir))                                                                                       $issues[] = 'Error: '.$name.' "'.$dir.'" is not a valid directory  [setup]';
            else if (!($file=@tempNam($dir, 'php')) || !strStartsWith(realPath($file), realPath($dir)))              $issues[] = 'Error: '.$name.' "'.$dir.'" directory is not writable  [setup]';
            is_file($file) && @unlink($file);
        }
        /*PHP_INI_ALL   */ if (            ini_get('default_mimetype'              )  != 'text/html')                $issues[] = 'Info:  default_mimetype is not "text/html": "'.ini_get('default_mimetype').'"  [standards]';
        /*PHP_INI_ALL   */ if ( strToLower(ini_get('default_charset'               )) != 'utf-8')                    $issues[] = 'Info:  default_charset is not "UTF-8": "'.ini_get('default_charset').'"  [standards]';
        /*PHP_INI_ALL   */ if (       ini_get_bool('implicit_flush'                ) && !CLI/*hardcoded*/)           $issues[] = 'Warn:  implicit_flush is not Off  [performance]';
        /*PHP_INI_PERDIR*/ $buffer = ini_get_bytes('output_buffering');
            if (!CLI) {
                if      ($buffer < 0)                                                                                $issues[] = 'Error: output_buffering is invalid: '.ini_get('output_buffering');
                else if (!$buffer)                                                                                   $issues[] = 'Info:  output_buffering is not enabled  [performance]';
            }
        // TODO: /*PHP_INI_ALL*/ "zlib.output_compression"

        // session related settings
        // ------------------------
        /*PHP_INI_ALL   */ if (       ini_get     ('session.save_handler') != 'files')                               $issues[] = 'Info:  session.save_handler is not "files": "'.ini_get('session.save_handler').'"';
        // TODO: check "session.save_path"
        /*PHP_INI_ALL   */ if (       ini_get     ('session.serialize_handler') != 'php')                            $issues[] = 'Info:  session.serialize_handler is not "php": "'.ini_get('session.serialize_handler').'"';
        /*PHP_INI_PERDIR*/ if (       ini_get_bool('session.auto_start'))                                            $issues[] = 'Info:  session.auto_start is not Off  [performance]';
        /*
        Caution: If you turn on session.auto_start then the only way to put objects into your sessions is to load its class
                 definition using auto_prepend_file in which you load the class definition else you will have to serialize()
                 your object and unserialize() it afterwards.
        */
        if (PHP_VERSION_ID < 50400) {
            /*PHP_INI_ALL*/ if (      ini_get_bool('session.bug_compat_42')) {                                       $issues[] = 'Info:  session.bug_compat_42 is not Off';
            /*PHP_INI_ALL*/ if (     !ini_get_bool('session.bug_compat_warn'))                                       $issues[] = 'Info:  session.bug_compat_warn is not On';
        }}
        /*PHP_INI_ALL   */ if (       ini_get     ('session.referer_check') != '')                                   $issues[] = 'Warn:  session.referer_check is not "": "'.ini_get('session.referer_check').'"  [functionality]';
        /*PHP_INI_ALL   */ if (      !ini_get_bool('session.use_strict_mode') && PHP_VERSION_ID >= 50502)            $issues[] = 'Warn:  session.use_strict_mode is not On  [security]';
        /*PHP_INI_ALL   */ if (      !ini_get_bool('session.use_cookies'))                                           $issues[] = 'Warn:  session.use_cookies is not On  [security]';
        /*PHP_INI_ALL   */ if (      !ini_get_bool('session.use_only_cookies'))                                      $issues[] = 'Warn:  session.use_only_cookies is not On  [security]';
        /*PHP_INI_ALL   */ if (       ini_get_bool('session.use_trans_sid')) {
                           if (      !ini_get_bool('session.use_only_cookies'))                                      $issues[] = 'Warn:  session.use_trans_sid is On  [security]';
                           else                                                                                      $issues[] = 'Info:  session.use_trans_sid is On';
        }

        // mail related settings
        // ---------------------
        /*PHP_INI_ALL   */ //sendmail_from
        if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isSet($_SERVER['SERVER_ADMIN']))   $issues[] = 'Warn:  On Windows and neither sendmail_path nor sendmail_from are set';
        /*PHP_INI_SYSTEM*/ if (!WINDOWS && !ini_get('sendmail_path'))                                                $issues[] = 'Warn:  sendmail_path is not set';
        /*PHP_INI_PERDIR*/ if (ini_get('mail.add_x_header'))                                                         $issues[] = 'Warn:  mail.add_x_header is not Off';

        // extensions
        // ----------
        /*PHP_INI_SYSTEM*/ if (ini_get('enable_dl'))                                                                 $issues[] = 'Warn:  enable_dl is not Off';
        if (!extension_loaded('ctype'))                                                                              $issues[] = 'Info:  ctype extension is not loaded';
        if (!extension_loaded('curl'))                                                                               $issues[] = 'Info:  curl extension is not loaded';
        if (!extension_loaded('iconv'))                                                                              $issues[] = 'Info:  iconv extension is not loaded';
        if (!extension_loaded('json'))                                                                               $issues[] = 'Info:  JSON extension is not loaded';
        if (!extension_loaded('mysql'))                                                                              $issues[] = 'Info:  MySQL extension is not loaded';
        if (!extension_loaded('mysqli'))                                                                             $issues[] = 'Info:  MySQLi extension is not loaded';
        if (!extension_loaded('pcntl')   && !WINDOWS && CLI) /*never loaded in a web server context*/                $issues[] = 'Info:  PCNTL extension is not loaded';
        if (!extension_loaded('sysvsem') && !WINDOWS)                                                                $issues[] = 'Info:  System-V Semaphore extension is not loaded';

        // check Composer defined dependencies
        if ($config) {
            $appRoot = $config->get('app.dir.root');
            if (is_file($file=$appRoot.'/composer.json') && extension_loaded('json')) {
                $composer = json_decode(file_get_contents($file), true);
                if (isSet($composer['require']) && is_array($composer['require'])) {
                    foreach ($composer['require'] as $name => $version) {
                        $name = trim(strToLower($name));
                        if (in_array($name, ['php', 'php-64bit', 'hhvm']) || strContains($name, '/')) continue;
                        if (strStartsWith($name, 'ext-')) $name = strRight($name, -4);
                        if (!extension_loaded($name))                                                                    $issues[] = 'Error: '.$name.' extension is not loaded  [composer dependency]';
                    }
                }
            }
        }

        // opcode cache
        // ------------
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

        // show results followed by phpInfo()
        if (!CLI) {
            ?>
            <div align="left" style="display:initial; visibility:initial; clear:both;
                                     position:relative; z-index:4294967295; top:initial; left:initial;
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
            @phpInfo();         // PHP might trigger warnings that are already checked and displayed (e.g. "date.timezone").
        }
    }


    /**
     * Set the specified php.ini setting. Opposite to the built-in PHP function this method does not return the old value
     * but a boolean success status. Used to detect assignment errors if the access level of the specified option
     * doesn't allow a modification.
     *
     * @param  string          $option
     * @param  bool|int|string $value
     * @param  bool            $throwException [optional] - whether or not to throw an exception on errors (default: yes)
     *
     * @return bool - success status
     */
    public static function ini_set($option, $value, $throwException = true) {
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
     * Return the query string of the current URL (if any).
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
     * Return the hash string of the current URL (if any).
     *
     * @return string - hash including the hash mark or an empty string
     */
    private static function getUrlHash() {
        $queryStr = self::getUrlQueryString();
        return strRightFrom($queryStr, '#', 1, true);
    }
}
