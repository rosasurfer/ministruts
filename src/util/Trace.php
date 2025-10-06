<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\util;

use Throwable;

use rosasurfer\ministruts\Application;
use rosasurfer\ministruts\config\ConfigInterface as Config;
use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\error\ErrorHandler;
use rosasurfer\ministruts\core\exception\RosasurferException;
use rosasurfer\ministruts\log\filter\ContentFilterInterface as ContentFilter;
use rosasurfer\ministruts\phpstan\UserTypes as PHPStanUserTypes;

use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strRightFrom;
use function rosasurfer\ministruts\strStartsWith;

use const rosasurfer\ministruts\NL;

/**
 * Stacktrace related functionality
 *
 * @phpstan-import-type STACKFRAME from PHPStanUserTypes
 */
class Trace extends StaticClass {

    /**
     * Convert a PHP stacktrace to the more intuitive Java-style.
     *
     * @param  array[] $trace           - regular PHP-style stacktrace
     * @param  string  $file [optional] - name of the file where the stacktrace was created (missing in PHP traces)
     * @param  int     $line [optional] - line of the file where the stacktrace was created (missing in PHP traces)
     *
     * @return mixed[] - Java-style stacktrace
     *
     * @phpstan-param  list<STACKFRAME> $trace
     * @phpstan-return list<STACKFRAME>
     *
     * @see \rosasurfer\ministruts\phpstan\STACKFRAME
     *
     * Notes
     * -----
     * PHP style shows WHAT was called. Each frame shows the function/method that was called.
     * <pre>
     *  require_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
     *  include_once()  # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
     *  include()       # line 26, file: /var/www/phalcon/vokuro/public/index.php
     *  {main}
     * </pre>
     *
     * Java style shows WHERE a call was made. Each frame points to the location that made the call.
     * <pre>
     *  require_once()             [php]
     *  include_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
     *  include()       # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
     *  {main}          # line 26, file: /var/www/phalcon/vokuro/public/index.php
     * </pre>
     *
     *
     * PHP style (what was called)
     * ---------------------------
     * Pros:
     *  - Makes the call chain very explicit. You can clearly see "X called Y called Z".
     *  - Useful when function/method names are more important than exact line numbers.
     *  - The function name tells you what's being invoked at each step.
     * Cons:
     *  - The "shift" between function name and line number is confusing.
     *  - Less intuitive. You have to look at one line to see what was called and another line to see where it failed.
     *  - Requires more mental parsing to locate the actual problem.
     *
     *
     * Java style (where a call was made)
     * ----------------------------------
     * Pros:
     *  - More intuitive for debugging. Each line shows exactly what code executed at that location.
     *  - The top line immediately shows the exact line where the error occurred.
     *  - No mental "shift" needed, line number and context match directly.
     *  - Better for step-by-step reasoning: "this line ran, then this line ran, then this line failed".
     * Cons:
     *  - Can be slightly less clear about the call chain.
     *
     *
     * Consensus
     * ---------
     * Most developers and language designers prefer Java's style. Languages like Python, JavaScript, C#, Rust, and Go all
     * follow Java's pattern. The directness of "this is the line that executed and caused the problem" is generally considered
     * more developer-friendly and requires less cognitive overhead during debugging.
     */
    public static function convertStackTrace(array $trace, string $file = '(unknown)', int $line = 0): array {
        // check if the stacktrace is already Java-style
        if (isset($trace[0]['_javastyle'])) {
            return $trace;
        }

        // fix a zero $line[0] if $file matches (e.g. with \SimpleXMLElement)
        if ($file != '(unknown)' && $line) {
            if (($trace[0]['file'] ?? null)===$file && ($trace[0]['line'] ?? 0)===0) {
                $trace[0]['line'] = $line;
            }
        }

        // append a frame without location for the main script
        $trace[] = ['function' => '{main}'];

        // move all locations to the end by one position
        for ($i = sizeof($trace); $i--;) {
            if (isset($trace[$i-1]['file'])) $trace[$i]['file'] = $trace[$i-1]['file'];
            else                       unset($trace[$i]['file']);

            if (isset($trace[$i-1]['line'])) $trace[$i]['line'] = $trace[$i-1]['line'];
            else                       unset($trace[$i]['line']);

            $trace[$i]['_javastyle'] = 1;
        }

        // add location from parameters to first frame if it differs from the old one (now in second frame)
        if (!isset($trace[1]['file'], $trace[1]['line']) || $trace[1]['file']!=$file || $trace[1]['line']!=$line) {
            $trace[0]['file'] = $file;                          // test with:
            $trace[0]['line'] = $line;                          // SQLite3::enableExceptions(true|false);
        }                                                       // SQLite3::exec($invalid_sql);
        else {
            unset($trace[0]['file'], $trace[0]['line']);        // otherwise delete location (a call from the PHP core)
        }

        // remove the last frame again if it has no location (no main script, call from PHP core)
        $size = sizeof($trace);
        if (!isset($trace[$size-1]['file'])) {
            array_pop($trace);
        }
        return $trace;                                          // @phpstan-ignore return.type (false positive: Array might not have offset 'function')

        // TODO: fix stack traces originating from require()/require_once() errors
        // TODO: fix wrong stack frames originating from calls to virtual static functions
        //
        // phalcon\mvc\Model::__callStatic()                  [php-phalcon]
        // vokuro\models\Users::findFirstByEmail() # line 27, file: F:\Projects\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
        // vokuro\auth\Auth->check()               # line 27, file: F:\Projects\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
    }


    /**
     * Convert a PHP stacktrace to the more intuitive Java-style and return a string representation.
     * Contains infos about nested exceptions.
     *
     * @param  Throwable      $throwable         - any throwable
     * @param  string         $indent [optional] - indent the resulting lines by the specified value (default: no indentation)
     * @param  ?ContentFilter $filter [optional] - the content filter to apply (default: none)
     *
     * @return string - string representation ending with EOL
     */
    public static function convertStackTraceToString(Throwable $throwable, string $indent = '', ?ContentFilter $filter = null): string {
        $trace = self::convertStackTrace($throwable->getTrace(), $throwable->getFile(), $throwable->getLine());
        $result = self::formatStackTrace($trace, $indent);

        // recursively add stacktraces of nested exceptions
        if ($cause = $throwable->getPrevious()) {
            $message = trim(ErrorHandler::getVerboseMessage($cause, $indent, $filter));
            $result .= NL.$indent.'caused by'.NL.$indent.$message.NL.NL;
            $result .= self::convertStackTraceToString($cause, $indent, $filter);
        }
        return $result;
    }


    /**
     * Return a stack frame's full method name similar to the constant __METHOD__.
     *
     * @param  array $frame                - stack frame
     * @param  bool  $nsToLower [optional] - whether to return the namespace part in lower case (default: unmodified)
     *
     * @return string - method name (without parentheses)
     *
     * @phpstan-param STACKFRAME $frame
     *
     * @see \rosasurfer\ministruts\phpstan\STACKFRAME
     */
    public static function getStackFrameMethod(array $frame, bool $nsToLower = false): string {
        $class = '';
        $function = $frame['function'];

        if (isset($frame['class'])) {
            $class = $frame['class'];
            if ($nsToLower && is_int($pos = strrpos($class, '\\'))) {
                $class = strtolower(substr($class, 0, $pos)).substr($class, $pos);
            }
            $class .= ($frame['type'] ?? '');
        }
        elseif ($nsToLower && is_int($pos = strrpos($function, '\\'))) {
            $function = strtolower(substr($function, 0, $pos)).substr($function, $pos);
        }
        return $class.$function;
    }


    /**
     * Return a formatted version of a stacktrace.
     *
     * @param  array[] $trace             - stacktrace
     * @param  string  $indent [optional] - indent formatted lines by this value (default: no indenting)
     *
     * @return string - string representation ending with an EOL marker
     *
     * @phpstan-param list<STACKFRAME> $trace
     *
     * @see \rosasurfer\ministruts\phpstan\STACKFRAME
     */
    public static function formatStackTrace(array $trace, string $indent = ''): string {
        $appRoot = '';
        $di = Application::getDi();
        if ($di && $di->has('config')) {
            /** @var Config $config */
            $config = $di['config'];
            $appRoot = $config->getString('app.dir.root');
        }
        $result = '';
        $size = sizeof($trace);
        $callLen = $lineLen = 0;

        for ($i=0; $i < $size; $i++) {              // align FILE and LINE
            $frame = &$trace[$i];

            $call = self::getStackFrameMethod($frame, true);
            if ($call != '{main}' && !strEndsWith($call, '{closure}')) {
                $call .= '()';
            }
            $callLen = max($callLen, strlen($call));
            $frame['call'] = $call;

            $frame['line'] = isset($frame['line']) ? ' # line '.$frame['line'].',' : '';
            $lineLen = max($lineLen, strlen($frame['line']));

            if (isset($frame['file'])) {
                $frame['file'] = ' file: '.(!$appRoot ? $frame['file'] : strRightFrom($frame['file'], $appRoot.DIRECTORY_SEPARATOR, 1, false, $frame['file']));
            }
            elseif (strStartsWith($call, 'phalcon\\')) {
                $frame['file'] = ' [php-phalcon]';
            }
            else {
                $frame['file'] = ' [php]';
            }
        }

        if ($appRoot) {
            $trace[] = ['call'=>'', 'line'=>'', 'file'=>' file base: '.$appRoot];
            $i++;
        }

        for ($i=0; $i < $size; $i++) {
            $call = $trace[$i]['call'] ?? '';
            $file = $trace[$i]['file'] ?? '';
            $line = $trace[$i]['line'] ?? '';
            $result .= $indent.str_pad($call, $callLen).' '.str_pad((string)$line, $lineLen).$file.NL;
        }
        return $result;
    }


    /**
     * Shift all frames from the beginning of a stacktrace up to and including the specified file and line.
     * Effectively, this brings the stacktrace in line with the specified file location.
     *
     * @param  array[] $trace - stacktrace to process
     * @param  string  $file  - filename where an error was triggered
     * @param  int     $line  - line number where an error was triggered
     *
     * @return mixed[] - modified stacktrace
     *
     * @phpstan-param  list<STACKFRAME> $trace
     * @phpstan-return list<STACKFRAME>
     *
     * @see \rosasurfer\ministruts\phpstan\STACKFRAME
     */
    public static function unwindStackToLocation(array $trace, string $file, int $line): array {
        $result = $trace;
        $size = sizeof($trace);

        for ($i = 0; $i < $size; $i++) {
            if (isset($trace[$i]['file'], $trace[$i]['line']) && $trace[$i]['file'] == $file && $trace[$i]['line'] == $line) {
                $result = array_slice($trace, $i + 1);
                break;
            }
        }
        return $result;
    }


    /**
     * Shift all frames from the beginning of a stacktrace pointing to the specified method.
     *
     * @param  Throwable $exception - exception to modify
     * @param  string    $method    - method name
     *
     * @return int - number of removed frames
     */
    public static function unwindStackToMethod(Throwable $exception, string $method): int {
        $trace  = $exception->getTrace();
        $size   = sizeof($trace);
        $file   = $exception->getFile();
        $line   = $exception->getLine();
        $method = strtolower($method);
        $count  = 0;

        while ($size > 0) {
            if (isset($trace[0]['function'])) {
                if (strtolower($trace[0]['function']) == $method) {
                    $frame = array_shift($trace);
                    $file = $frame['file'] ?? '(unknown)';
                    $line = $frame['line'] ?? 0;
                    $size--;
                    $count++;
                    continue;
                }
            }
            break;
        }

        RosasurferException::modifyException($exception, $trace, $file, $line);
        return $count;
    }
}
