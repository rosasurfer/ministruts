<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\util;

use Throwable;

use rosasurfer\ministruts\config\ConfigInterface as Config;
use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\ministruts\core\exception\RuntimeException;

use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\ini_get_bool;
use function rosasurfer\ministruts\ini_get_bytes;
use function rosasurfer\ministruts\ini_get_int;
use function rosasurfer\ministruts\php_byte_value;
use function rosasurfer\ministruts\realpath;
use function rosasurfer\ministruts\stderr;
use function rosasurfer\ministruts\strContains;
use function rosasurfer\ministruts\strRight;
use function rosasurfer\ministruts\strStartsWith;

use const rosasurfer\ministruts\CLI;
use const rosasurfer\ministruts\MB;
use const rosasurfer\ministruts\NL;
use const rosasurfer\ministruts\WINDOWS;


/**
 * PHP core related functionality
 */
class PHP extends StaticClass {


    /**
     * Trigger execution of the garbage collector.
     *
     * @return void
     */
    public static function collectGarbage(): void {
        $wasEnabled = gc_enabled();
        !$wasEnabled && gc_enable();

        gc_collect_cycles();

        !$wasEnabled && gc_disable();
    }


    /**
     * Execute a process and return STDOUT.
     *
     * Replacement for shell_exec() wich suffers from a Windows bug where a DOS EOF character (0x1A = ASCII 26)
     * in the STDOUT stream causes further reading to stop.
     *
     * - pOpen() suffers from the same bug
     * - passThru() does not capture STDERR
     *
     * @param  string        $cmd                 - external command to execute
     * @param  ?string       $stderr   [optional] - if present a variable the contents of STDERR will be written to
     * @param  ?int          $exitCode [optional] - if present a variable the commands's exit code will be written to
     * @param  ?string       $dir      [optional] - if present the initial working directory for the command
     * @param  string[]|null $env      [optional] - if present the environment to *replace* the current one
     * @param  bool[]        $options  [optional] - additional options controlling runtime behavior:                      <br>
     *                  key "stdout-passthrough":   Whether to additionally pass-through (print) the contents of STDOUT.  <br>
     *                                              This option will not affect the return value (default: no)            <br>
     *                  key "stderr-passthrough":   Whether to additionally pass-through (print) the contents of STDERR.  <br>
     *                                              This option will not affect the return value (default: no)            <br>
     *
     * @return ?string - content of STDOUT or NULL if the process didn't produce any output
     */
    public static function execProcess(string $cmd, ?string &$stderr=null, ?int &$exitCode=null, ?string $dir=null, ?array $env=null, array $options=[]): ?string {
        // check whether the process needs to be watched asynchronously
        $argc         = func_num_args();
        $needStderr   = ($argc > 1);
        $needExitCode = ($argc > 2);
        $stdoutPassthrough = isset($options['stdout-passthrough']) && $options['stdout-passthrough'];
        $stderrPassthrough = isset($options['stderr-passthrough']) && $options['stderr-passthrough'];

        if (!$needStderr && !$needExitCode && !WINDOWS) {
            return shell_exec($cmd);                                    // the process doesn't need watching and we can go with shell_exec()
        }

        // we must use proc_open()/proc_close()
        $descriptors = [                                                // "pipes" or "files":
            ($STDIN =0) => ['pipe', 'rb'],                              // ['file', '/dev/tty', 'rb'],
            ($STDOUT=1) => ['pipe', 'wb'],                              // ['file', '/dev/tty', 'wb'],
            ($STDERR=2) => ['pipe', 'wb'],                              // ['file', '/dev/tty', 'wb'],
        ];
        $pipes = [];

        try {
            $hProc = proc_open($cmd, $descriptors, $pipes, $dir, $env, ['bypass_shell'=>true]);
        }
        catch (Throwable $ex) {
            if (!$ex instanceof IRosasurferException) $ex = new RuntimeException($ex->getMessage(), $ex->getCode(), $ex);

            $match = null;
            if (WINDOWS && preg_match('/proc_open\(\): CreateProcess failed, error code - ([0-9]+)/i', $ex->getMessage(), $match)) {
                $error = Windows::errorToString((int) $match[1]);
                if ($error != $match[1]) $ex->appendMessage($match[1].': '.$error);
            }
            throw $ex->appendMessage("CMD: \"$cmd\"");
        }

        // if the process doesn't need asynchronous watching
        if (!$stdoutPassthrough && !$stderrPassthrough) {
            $stdout = stream_get_contents($pipes[$STDOUT]);
            $stderr = stream_get_contents($pipes[$STDERR]);
            fclose($pipes[$STDIN ]);                                    // $pipes[0] => writeable handle connected to the child's STDIN
            fclose($pipes[$STDOUT]);                                    // $pipes[1] => readable handle connected to the child's STDOUT
            fclose($pipes[$STDERR]);                                    // $pipes[2] => readable handle connected to the child's STDERR
            $exitCode = proc_close($hProc);                             // we must close the pipes before proc_close() to avoid a deadlock
            return $stdout;
        }

        // the process needs to be watched asynchronously
        fclose             ($pipes[$STDIN]);
        stream_set_blocking($pipes[$STDOUT], false);
        stream_set_blocking($pipes[$STDERR], false);

        $observed = [                                                   // streams to watch
            (int)$pipes[$STDOUT] => $pipes[$STDOUT],
            (int)$pipes[$STDERR] => $pipes[$STDERR],
        ];
        $stdout = $stderr = '';                                         // stream contents
        $handlers = [                                                   // a handler for each stream
            (int)$pipes[$STDOUT] => function($line) use (&$stdout, $stdoutPassthrough): void {
                $stdout .= $line; if ($stdoutPassthrough) echo $line;
            },
            (int)$pipes[$STDERR] => function($line) use (&$stderr, $stderrPassthrough): void {
                $stderr .= $line; if ($stderrPassthrough) stderr($line);
            },
        ];
        $null = null;
        do {
            $readable = $observed;
            stream_select($readable, $null, $null, 0, 200000);          // timeout = 0.2 sec
            foreach ($readable as $stream) {
                if (($line=fgets($stream)) === false) {                 // this covers fEof() too
                    fclose($stream);
                    unset($observed[(int)$stream]);                     // close and remove from observed streams
                    continue;
                }
                do {
                    $handlers[(int)$stream]($line);                     // process incoming content
                } while (!WINDOWS && ($line=fgets($stream)) !== false); // on Windows a second call blocks
            }
        } while ($observed);                                            // loop until all observable streams are closed

        $exitCode = proc_close($hProc);
        return $stdout;

        // @see  https://symfony.com/doc/current/components/process.html#getting-real-time-process-output
        // @see  https://gonzalo123.com/2012/10/08/how-to-send-the-output-of-symfonys-process-component-to-a-node-js-server-in-real-time-with-socket-io/
    }


    /**
     * Check PHP settings, print issues and call phpinfo().
     *
     * @return void
     *
     * <pre>
     *  PHP_INI_ALL    - entry can be set anywhere
     *  PHP_INI_USER   - entry can be set via ini_set() and in .user.ini
     *  PHP_INI_ONLY   - entry can be set in php.ini only
     *  PHP_INI_SYSTEM - entry can be set in php.ini and in httpd.conf
     *  PHP_INI_PERDIR - entry can be set in php.ini, httpd.conf, .htaccess and in .user.ini
     * </pre>
     */
    public static function phpinfo(): void {
        /** @var Config $config */
        $config = self::di('config');
        $issues = [];

        // core configuration
        // ------------------
        if (!php_ini_loaded_file())                                                                                $issues[] = 'Error: no "php.ini" configuration file loaded [setup]';
        /*PHP_INI_PERDIR*/ if (ini_get_bool('short_open_tag'))                                                     $issues[] = 'Warn:  short_open_tag is not Off [XML compatibility]';
        /*PHP_INI_ONLY  */ if (ini_get_bool('expose_php') && !CLI)                                                 $issues[] = 'Warn:  expose_php is not Off [security]';
        /*PHP_INI_ALL   */ if (ini_get_int('max_execution_time') > 30 && !CLI /*hardcoded*/)                       $issues[] = 'Info:  max_execution_time is very high: '.ini_get('max_execution_time').' [setup]';
        /*PHP_INI_ALL   */ if (ini_get_int('default_socket_timeout') > 30 /*PHP default: 60*/)                     $issues[] = 'Info:  default_socket_timeout is very high: '.ini_get('default_socket_timeout').' [setup]';
        /*PHP_INI_ALL   */ $memoryLimit = ini_get_bytes('memory_limit');
            if     ($memoryLimit ==    -1)                                                                         $issues[] = 'Warn:  memory_limit is unlimited [resources]';
            elseif ($memoryLimit <=     0)                                                                         $issues[] = 'Error: memory_limit is invalid: '.ini_get('memory_limit');
            elseif ($memoryLimit <  32*MB)                                                                         $issues[] = 'Warn:  memory_limit is very low: '.ini_get('memory_limit').' [resources]';
            elseif ($memoryLimit > 128*MB)                                                                         $issues[] = 'Info:  memory_limit is very high: '.ini_get('memory_limit').' [resources]';

            $sWarnLimit = $config->get('log.warn.memory_limit', '');
            $warnLimit = php_byte_value($sWarnLimit);
            if ($warnLimit) {
                if     ($warnLimit <             0)                                                                $issues[] = 'Error: log.warn.memory_limit is invalid: '.$sWarnLimit.' [configuration]';
                elseif ($warnLimit >= $memoryLimit)                                                                $issues[] = 'Error: log.warn.memory_limit ('.$sWarnLimit.') is not lower than memory_limit ('.ini_get('memory_limit').') [configuration]';
                elseif ($warnLimit >        128*MB)                                                                $issues[] = 'Info:  log.warn.memory_limit ('.$sWarnLimit.') is very high (memory_limit: '.ini_get('memory_limit').') [configuration]';
            }
        /*PHP_INI_PERDIR*/ if ( ini_get_bool('register_argc_argv') && !CLI /*hardcoded*/)                          $issues[] = 'Info:  register_argc_argv is not Off [performance]';
        /*PHP_INI_PERDIR*/ if (!ini_get_bool('auto_globals_jit'))                                                  $issues[] = 'Info:  auto_globals_jit is not On [performance]';
        /*PHP_INI_ALL   */ if (!empty(ini_get('open_basedir')))                                                    $issues[] = 'Info:  open_basedir is not empty: "'.ini_get('open_basedir').'" [performance]';
        /*PHP_INI_SYSTEM*/ if (!ini_get_bool('allow_url_fopen'))                                                   $issues[] = 'Info:  allow_url_fopen is not On [functionality]';
        /*PHP_INI_SYSTEM*/ if ( ini_get_bool('allow_url_include'))                                                 $issues[] = 'Error: allow_url_include is not Off [security]';
        /*PHP_INI_ALL   */ foreach (explode(PATH_SEPARATOR, ini_get('include_path') ?: '') as $i => $path) {
                               if (!strlen($path))                                                                 $issues[] = 'Warn:  include_path['.$i.'] contains an empty path: "'.ini_get('include_path').'" [setup]';
                           }
        // error handling
        // --------------
        /*PHP_INI_ALL   */ $current = ini_get_int('error_reporting');
            $target = E_ALL & ~E_DEPRECATED;
            if ($notCovered = ($target ^ $current) & $target)                                                      $issues[] = 'Warn:  error_reporting does not cover '.ErrorHandler::errorLevelToStr($notCovered).' [standards]';
        if (!WINDOWS) { /* Windows is always development */
            /*PHP_INI_ALL*/ if (ini_get_bool('display_errors'        )) /*bool|string:stderr*/                     $issues[] = 'Warn:  display_errors is not Off [security]';
            /*PHP_INI_ALL*/ if (ini_get_bool('display_startup_errors'))                                            $issues[] = 'Warn:  display_startup_errors is not Off [security]';
        }
        /*PHP_INI_ALL   */ if ( ini_get_bool('ignore_repeated_errors'))                                            $issues[] = 'Info:  ignore_repeated_errors is not Off [resources]';
        /*PHP_INI_ALL   */ if ( ini_get_bool('ignore_repeated_source'))                                            $issues[] = 'Info:  ignore_repeated_source is not Off [resources]';
        /*PHP_INI_ALL   */ if ( ini_get_bool('html_errors'           ))                                            $issues[] = 'Info:  html_errors is not Off  [setup]';
        /*PHP_INI_ALL   */ if (!ini_get_bool('log_errors'            ))                                            $issues[] = 'Error: log_errors is not On [setup]';
        /*PHP_INI_ALL   */ $bytes = ini_get_bytes('log_errors_max_len');
            if     ($bytes <  0) /* 'log_errors' and 'log_errors_max_len' do not affect */                         $issues[] = 'Error: log_errors_max_len is invalid: '.ini_get('log_errors_max_len');
            elseif ($bytes != 0) /* explicit calls to the function error_log()          */                         $issues[] = 'Warn:  log_errors_max_len is not 0: '.ini_get('log_errors_max_len').' [functionality]';
        /*PHP_INI_ALL   */ $errorLog = ini_get('error_log');
            if (!empty($errorLog) && $errorLog!='syslog') {
                if (is_file($errorLog)) {
                    $hFile = @fopen($errorLog, 'ab');         // try to open
                    if (is_resource($hFile)) fclose($hFile);
                    else                                                                                           $issues[] = 'Error: error_log "'.$errorLog.'" file is not writable [setup]';
                }
                else {
                    $hFile = @fopen($errorLog, 'wb');         // try to create
                    if (is_resource($hFile)) fclose($hFile);
                    else                                                                                           $issues[] = 'Error: error_log "'.$errorLog.'" directory is not writable [setup]';
                    is_file($errorLog) && @unlink($errorLog);
                }
            }

        // input sanitizing
        // ----------------
        /*PHP_INI_SYSTEM*/ if (ini_get_bool('sql.safe_mode'))                                                      $issues[] = 'Warn:  sql.safe_mode is not Off [setup]';

        // request & HTML handling
        // -----------------------
        /*PHP_INI_PERDIR*/ $order = ini_get('request_order') ?: ''; /*if empty automatic fall-back to GPC order in "variables_order"*/
            if (empty($order)) {
                /*PHP_INI_PERDIR*/ $order = ini_get('variables_order') ?: '';
                $newOrder = '';
                $len = strlen($order);
                for ($i=0; $i < $len; $i++) {
                    if (in_array($char=$order[$i], ['G', 'P', 'C'])) {
                        $newOrder .= $char;
                    }
                }
                $order = $newOrder;
            }              if ($order != 'GP')                                                                     $issues[] = 'Error: request_order is not "GP": "'.(empty(ini_get('request_order')) ? '" (empty) => variables_order:"':'').$order.'" [standards]';
        /*PHP_INI_PERDIR*/ if (!ini_get_bool('enable_post_data_reading'))                                          $issues[] = 'Warn:  enable_post_data_reading is not On [request handling]';
        /*PHP_INI_ALL   */ if ( ini_get     ('arg_separator.output') != '&')                                       $issues[] = 'Warn:  arg_separator.output is not "&": "'.ini_get('arg_separator.output').'" [standards]';
        /*PHP_INI_ALL   */ if (!ini_get_bool('ignore_user_abort') && !CLI)                                         $issues[] = 'Warn:  ignore_user_abort is not On  [setup]';
        /*PHP_INI_PERDIR*/ $postMaxSize = ini_get_bytes('post_max_size');
            $localMemoryLimit = $memoryLimit;
            $globalMemoryLimit = php_byte_value(ini_get_all()['memory_limit']['global_value']);
            // The memory_limit needs to be raised before script entry, not in the script.
            // Otherwise PHP may crash with "Out of memory" on script entry if the request is large enough.
            if     ($localMemoryLimit < $postMaxSize)                                                              $issues[] = 'Error: local memory_limit "'.ini_get('memory_limit').'" is too low for post_max_size "'.ini_get('post_max_size').'" [configuration]';
            elseif ($localMemoryLimit < $postMaxSize + 20*MB) /*PHP needs about 20MB for the runtime*/             $issues[] = 'Warn:  local memory_limit "'.ini_get('memory_limit').'" is very low for post_max_size "'.ini_get('post_max_size').'" (PHP needs about 20MB) [configuration]';
            elseif ($globalMemoryLimit < $postMaxSize)                                                             $issues[] = 'Info:  make sure memory_limit "'.ini_get('memory_limit').'" is raised before script entry as global memory_limit "'.ini_get_all()['memory_limit']['global_value'].'" is too low for post_max_size "'.ini_get('post_max_size').'" [configuration]';
        /*PHP_INI_SYSTEM*/ if (ini_get_bool('file_uploads') && !CLI) {                                             $issues[] = 'Info:  file_uploads is not Off [security]';
        /*PHP_INI_PERDIR*/ if (ini_get_bytes('upload_max_filesize') >= $postMaxSize)                               $issues[] = 'Error: post_max_size "'.ini_get('post_max_size').'" is not larger than upload_max_filesize "'.ini_get('upload_max_filesize').'" [request handling]';
        /*PHP_INI_SYSTEM*/ $dir = ini_get($name = 'upload_tmp_dir') ?: '';
            $file = null;
            if (!strlen(trim($dir))) {                                                                             $issues[] = 'Info:  '.$name.' is not set [setup]';
                $dir = sys_get_temp_dir();
                $name = 'sys_get_temp_dir()';
            }
            if (!is_dir($dir))                                                                                     $issues[] = 'Error: '.$name.' "'.$dir.'" is not a valid directory [setup]';
            elseif (!($file = @tempnam($dir, 'php')) || !strStartsWith(realpath($file), realpath($dir)))           $issues[] = 'Error: '.$name.' "'.$dir.'" directory is not writable [setup]';
            $file && is_file($file) && @unlink($file);
        }
        /*PHP_INI_ALL   */ if (ini_get('default_mimetype') != 'text/html')                                         $issues[] = 'Info:  default_mimetype is not "text/html": "'.ini_get('default_mimetype').'" [standards]';
        /*PHP_INI_ALL   */ if (strtolower(ini_get('default_charset') ?: '') != 'utf-8')                            $issues[] = 'Info:  default_charset is not "UTF-8": "'.ini_get('default_charset').'" [standards]';
        /*PHP_INI_ALL   */ if (ini_get_bool('implicit_flush') && !CLI/*hardcoded*/)                                $issues[] = 'Warn:  implicit_flush is not Off [performance]';
        /*PHP_INI_PERDIR*/ $buffer = ini_get_bytes('output_buffering');
            if (!CLI) {
                if ($buffer < 0)                                                                                   $issues[] = 'Error: output_buffering is invalid: '.ini_get('output_buffering');
                elseif (!$buffer)                                                                                  $issues[] = 'Info:  output_buffering is not enabled [performance]';
            }
        // TODO: /*PHP_INI_ALL*/ "zlib.output_compression"

        // session related settings
        // ------------------------
        /*PHP_INI_ALL   */ if (ini_get('session.save_handler') != 'files')                                         $issues[] = 'Info:  session.save_handler is not "files": "'.ini_get('session.save_handler').'"';
        // TODO: check "session.save_path"
        /*PHP_INI_ALL   */ if (ini_get('session.serialize_handler') != 'php')                                      $issues[] = 'Info:  session.serialize_handler is not "php": "'.ini_get('session.serialize_handler').'"';
        /*PHP_INI_PERDIR*/ if (ini_get_bool('session.auto_start'))                                                 $issues[] = 'Info:  session.auto_start is not Off [performance]';
        /*
        Caution: If you turn on session.auto_start then the only way to put objects into your sessions is to load its class
                 definition using auto_prepend_file in which you load the class definition else you will have to serialize()
                 your object and unserialize() it afterwards.
        */
        /*PHP_INI_ALL   */ if ( ini_get     ('session.referer_check') != '')                                       $issues[] = 'Warn:  session.referer_check is not "": "'.ini_get('session.referer_check').'" [functionality]';
        /*PHP_INI_ALL   */ if (!ini_get_bool('session.use_strict_mode'))                                           $issues[] = 'Warn:  session.use_strict_mode is not On [security]';
        /*PHP_INI_ALL   */ if (!ini_get_bool('session.use_cookies'))                                               $issues[] = 'Warn:  session.use_cookies is not On [security]';
        /*PHP_INI_ALL   */ if (!ini_get_bool('session.use_only_cookies'))                                          $issues[] = 'Warn:  session.use_only_cookies is not On [security]';
        /*PHP_INI_ALL   */ if ( ini_get_bool('session.use_trans_sid'))                                             $issues[] = 'Warn:  session.use_trans_sid is On [security]';

        // mail related settings
        // ---------------------
        /*PHP_INI_ALL   */ //sendmail_from
        if (WINDOWS && !ini_get('sendmail_path') && !ini_get('sendmail_from') && !isset($_SERVER['SERVER_ADMIN'])) $issues[] = 'Warn:  On Windows and neither sendmail_path nor sendmail_from are set';
        /*PHP_INI_SYSTEM*/ if (!WINDOWS && !ini_get('sendmail_path'))                                              $issues[] = 'Warn:  sendmail_path is not set  [setup]';
        /*PHP_INI_PERDIR*/ if (ini_get('mail.add_x_header'))                                                       $issues[] = 'Warn:  mail.add_x_header is not Off  [security]';

        // extensions
        // ----------
        /*PHP_INI_SYSTEM*/ if (ini_get('enable_dl'))                                                               $issues[] = 'Warn:  enable_dl is not Off  [setup]';
        if (!extension_loaded('ctype'))                                                                            $issues[] = 'Warn:  ctype extension is not loaded [functionality]';
        if (!extension_loaded('json'))                                                                             $issues[] = 'Warn:  JSON extension is not loaded [functionality]';
        if (!extension_loaded('iconv'))                                                                            $issues[] = 'Info:  iconv extension is not loaded [functionality]';
        if (!extension_loaded('pcntl') && !WINDOWS && CLI) /*never loaded in a web server context*/                $issues[] = 'Info:  PCNTL extension is not loaded [functionality]';
        if (!extension_loaded('sysvsem') && !WINDOWS)                                                              $issues[] = 'Info:  System-V Semaphore extension is not loaded [functionality]';

        // check Composer defined dependencies
        $appRoot = $config['app.dir.root'];
        if (is_file($file=$appRoot.'/composer.json') && extension_loaded('json')) {
            $composer = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            if (isset($composer['require']) && is_array($composer['require'])) {
                foreach ($composer['require'] as $name => $v) {
                    $name = trim(strtolower($name));
                    if (in_array($name, ['php', 'php-64bit', 'hhvm']) || strContains($name, '/')) continue;
                    if (strStartsWith($name, 'ext-')) $name = strRight($name, -4);
                    if (!extension_loaded($name))                                                                  $issues[] = 'Error: '.$name.' extension is not loaded [Composer project requirement]';
                }
            }
        }

        // opcode cache
        // ------------
        if (extension_loaded('zend opcache')) {
            /*PHP_INI_ALL*/ if (!ini_get('opcache.enable'))                                                        $issues[] = 'Info:  opcache.enable is not On [performance]';
        }
        else                                                                                                       $issues[] = 'Info:  No opcode cache found [performance]';

        // show results followed by phpinfo()
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
        if ($issues) echof('PHP configuration issues:'.NL.'-------------------------'.NL.join(NL, $issues));
        else         echof('PHP configuration OK');

        // call phpinfo() if on a web server
        if (!CLI) {
            $get = $_GET;
            $isConfig = isset($get['__config__']);
            unset($get['__phpinfo__'], $get['__config__']);

            // before/after link
            if ($isConfig) $queryStr = http_build_query($get + ['__phpinfo__'=>'',                  ], '', '&amp;');
            else           $queryStr = http_build_query($get + ['__config__' =>'', '__phpinfo__'=>''], '', '&amp;');
            ?>
            <div style="clear:both; text-align:center; margin:0 0 15px 0; padding:20px 0 0 0; font-size:12px; font-weight:bold; font-family:sans-serif">
                <a href="?<?=$queryStr?>" style="display:inline-block; min-width:220px; min-height:15px; margin:0 10px; padding:10px 0; background-color:#ccf; color:#222; border:1px outset #666; white-space:nowrap">
                   <?=$isConfig ? 'Hide':'Show'?> Application Configuration
                </a>
            </div>
            <?php
            echo NL;
            phpinfo();
        }
    }


    /**
     * Set the specified "php.ini" setting.
     *
     * Unlike the built-in PHP function ini_set() this method does not return the old value but a boolean success status.
     * Used to detect assignment errors if the access level of the specified option doesn't allow a modification.
     *
     * @param  string          $option
     * @param  bool|int|string $value
     * @param  bool            $throw [optional] - whether to throw exceptions on assignment errors (default: yes)
     *
     * @return bool - success status
     */
    public static function ini_set(string $option, $value, bool $throw = true): bool {
        if (is_bool($value)) $value = (int) $value;
        $newValue = (string) $value;

        $oldValue = ini_set($option, $newValue);
        if ($oldValue !== false) {
            return true;
        }
        $oldValue = ini_get($option);       // ini_set() caused an error

        if ($oldValue == $newValue) {       // the error can be ignored
            return true;
        }
        if ($throw) throw new RuntimeException('Cannot set php.ini option "'.$option.'" (current value: "'.$oldValue.'")');
        return false;
    }
}
