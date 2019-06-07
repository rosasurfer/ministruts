<?php
namespace rosasurfer\core\debug;

use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\core\exception\error\PHPError;

use function rosasurfer\normalizeEOL;
use function rosasurfer\simpleClassName;
use function rosasurfer\strEndsWith;
use function rosasurfer\strLeftTo;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\NL;


/**
 * Debug helper.
 */
class DebugHelper extends StaticClass {


    /**
     * Take a regular PHP stacktrace and create a fixed and more readable Java-like one.
     *
     * @param  array  $trace           - regular PHP stacktrace
     * @param  string $file [optional] - name of the file where the stacktrace was generated
     * @param  int    $line [optional] - line of the file where the stacktrace was generated
     *
     * @return array - new fixed stacktrace
     *
     * @example
     * original stacktrace:
     * <pre>
     *  require_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
     *  include_once()  # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
     *  include()       # line 26, file: /var/www/phalcon/vokuro/public/index.php
     *  {main}
     * </pre>
     *
     * new stacktrace:
     * <pre>
     *  require_once()             [php]
     *  include_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
     *  include()       # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
     *  {main}          # line 26, file: /var/www/phalcon/vokuro/public/index.php
     * </pre>
     */
    public static function fixTrace(array $trace, $file='unknown', $line=0) {
        // check if the stacktrace is already fixed
        if ($trace && isset($trace[0]['fixed']))
            return $trace;

        // Fix an incomplete frame[0][line] if parameters are provided and $file matches (e.g. with \SimpleXMLElement).
        if ($file!='unknown' && $line) {
            if (isset($trace[0]['file']) && $trace[0]['file']==$file) {
                if (isset($trace[0]['line']) && $trace[0]['line']===0) {
                    $trace[0]['line'] = $line;
                }
            }
        }

        // Add a frame for the main script to the bottom (end of array).
        $trace[] = ['function' => '{main}'];

        // Move FILE and LINE fields down (to the end) by one position.
        for ($i=sizeof($trace); $i--;) {
            if (isset($trace[$i-1]['file'])) $trace[$i]['file'] = $trace[$i-1]['file'];
            else                       unset($trace[$i]['file']);

            if (isset($trace[$i-1]['line'])) $trace[$i]['line'] = $trace[$i-1]['line'];
            else                       unset($trace[$i]['line']);

            $trace[$i]['fixed'] = true;
        }

        // Add location details from parameters to frame[0] only if they differ from the old values (now in frame[1])
        if (!isset($trace[1]['file']) || !isset($trace[1]['line']) || $trace[1]['file']!=$file || $trace[1]['line']!=$line) {
            $trace[0]['file'] = $file;                          // test with:
            $trace[0]['line'] = $line;                          // \SQLite3::enableExceptions(true|false);
        }                                                       // \SQLite3::exec($invalid_sql);
        else {
            unset($trace[0]['file'], $trace[0]['line']);        // otherwise delete them
        }

        // Remove the last frame (the one we added for the main script) if it now points to an unknown location (PHP core).
        $size = sizeof($trace);
        if (!isset($trace[$size-1]['file'])) {
            \array_pop($trace);
        }
        return $trace;

        /**
         * TODO: fix wrong stack frames originating from calls to virtual static functions
         *
         * phalcon\mvc\Model::__callStatic()                  [php-phalcon]
         * vokuro\models\Users::findFirstByEmail() # line 27, file: F:\Projekte\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
         * vokuro\auth\Auth->check()               # line 27, file: F:\Projekte\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
         */
    }


    /**
     * Return a formatted and human-readable version of a stacktrace.
     *
     * @param  array  $trace             - stacktrace
     * @param  string $indent [optional] - indent the formatted lines by this value (default: empty string)
     *
     * @return string
     */
    public static function formatTrace(array $trace, $indent = '') {
        $appRoot = self::di('config')['app.dir.root'];
        $result  = '';

        $size = sizeof($trace);
        $callLen = $lineLen = 0;

        for ($i=0; $i < $size; $i++) {               // align FILE and LINE
            $frame = &$trace[$i];

            $call = self::getFQFunctionName($frame, $nsLowerCase=true);

            if ($call!='{main}' && !strEndsWith($call, '{closure}'))
                $call.='()';
            $callLen = max($callLen, strlen($call));
            $frame['call'] = $call;

            $frame['line'] = isset($frame['line']) ? ' # line '.$frame['line'].',' : '';
            $lineLen = max($lineLen, strlen($frame['line']));

            if (isset($frame['file']))                 $frame['file'] = ' file: '.(!$appRoot ? $frame['file'] : strRightFrom($frame['file'], $appRoot.DIRECTORY_SEPARATOR, 1, false, $frame['file']));
            elseif (strStartsWith($call, 'phalcon\\')) $frame['file'] = ' [php-phalcon]';
            else                                       $frame['file'] = ' [php]';
        }
        if ($appRoot) {
            $trace[] = ['call'=>'', 'line'=>'', 'file'=>' file base: '.$appRoot];
            $i++;
        }

        for ($i=0; $i < $size; $i++) {
            $result .= $indent.str_pad($trace[$i]['call'], $callLen).' '.str_pad($trace[$i]['line'], $lineLen).$trace[$i]['file'].NL;
        }

        return $result;
    }


    /**
     * Return the fully qualified function or method name of a stacktrace's frame.
     *
     * @param  array $frame                  - frame
     * @param  bool  $nsLowerCase [optional] - whether the namespace part of the name to return in lower case (default: no)
     *
     * @return string - fully qualified function or method name (without trailing parentheses)
     */
    public static function getFQFunctionName(array $frame, $nsLowerCase = false) {
        $class = $function = '';

        if (isset($frame['function'])) {
            $function = $frame['function'];

            if (isset($frame['class'])) {
                $class = $frame['class'];
                if ($nsLowerCase && is_int($pos=strrpos($class, '\\')))
                    $class = strtolower(substr($class, 0, $pos)).substr($class, $pos);
                $class = $class.$frame['type'];
            }
            elseif ($nsLowerCase && is_int($pos=strrpos($function, '\\'))) {
                $function = strtolower(substr($function, 0, $pos)).substr($function, $pos);
            }
        }
        return $class.$function;
    }


    /**
     * Return a more readable version of an exception's message.
     *
     * @param  \Exception|\Throwable $exception         - any exception (PHP5) or throwable (PHP7)
     * @param  string                $indent [optional] - indent lines by the specified value (default: no indentation)
     *
     * @return string - message
     */
    public static function composeBetterMessage($exception, $indent = '') {
        Assert::throwable($exception, '$exception');

        if ($exception instanceof PHPError) {
            $result = $exception->getSimpleType();
        }
        else {
            $class     = get_class($exception);
            $namespace = strtolower(strLeftTo($class, '\\', -1, true, ''));
            $basename  = simpleClassName($class);
            $result    = $indent.$namespace.$basename;

            if ($exception instanceof \ErrorException)                                  // A PHP error exception not created
                $result .= '('.self::errorLevelToStr($exception->getSeverity()).')';    // by the framework.
        }
        $message = $exception->getMessage();

        if (strlen($indent)) {
            $lines = explode(NL, normalizeEOL($message));                               // indent multiline messages
            $eom = '';
            if (strEndsWith($message, NL)) {
                \array_pop($lines);
                $eom = NL;
            }
            $message = join(NL.$indent, $lines).$eom;
        }

        $result .= (strlen($message) ? ': ':'').$message;
        return $result;
    }


    /**
     * Return a more readable version of an exception's stacktrace. The representation also contains information about
     * nested exceptions.
     *
     * @param  \Exception|\Throwable $exception         - any exception (PHP5) or throwable (PHP7)
     * @param  string                $indent [optional] - indent the resulting lines by the specified value
     *                                                    (default: no indentation)
     * @return string - readable stacktrace
     */
    public static function getBetterTraceAsString($exception, $indent = '') {
        Assert::throwable($exception, '$exception');

        if ($exception instanceof IRosasurferException) $trace = $exception->getBetterTrace();
        else                                            $trace = self::fixTrace($exception->getTrace(), $exception->getFile(), $exception->getLine());
        $result = self::formatTrace($trace, $indent);

        if ($cause = $exception->getPrevious()) {
            // recursively add stacktraces of nested exceptions
            $message = trim(self::composeBetterMessage($cause, $indent));
            $result .= NL.$indent.'caused by'.NL.$indent.$message.NL.NL;
            $result .= self::{__FUNCTION__}($cause, $indent);                 // recursion
        }
        return $result;
    }


    /**
     * Return a human-readable form of the specified error reporting level.
     *
     * @param  int $level - error reporting level
     *
     * @return string
     */
    public static function errorLevelToStr($level) {
        Assert::int($level);

        $levels = [
            E_ERROR             => 'E_ERROR',                   //     1
            E_WARNING           => 'E_WARNING',                 //     2
            E_PARSE             => 'E_PARSE',                   //     4
            E_NOTICE            => 'E_NOTICE',                  //     8
            E_CORE_ERROR        => 'E_CORE_ERROR',              //    16
            E_CORE_WARNING      => 'E_CORE_WARNING',            //    32
            E_COMPILE_ERROR     => 'E_COMPILE_ERROR',           //    64
            E_COMPILE_WARNING   => 'E_COMPILE_WARNING',         //   128
            E_USER_ERROR        => 'E_USER_ERROR',              //   256
            E_USER_WARNING      => 'E_USER_WARNING',            //   512
            E_USER_NOTICE       => 'E_USER_NOTICE',             //  1024
            E_STRICT            => 'E_STRICT',                  //  2048
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',       //  4096
            E_DEPRECATED        => 'E_DEPRECATED',              //  8192
            E_USER_DEPRECATED   => 'E_USER_DEPRECATED',         // 16384
        ];

        if      (!$level)                                                       $levels = ['0'];                        //     0
        else if (($level &  E_ALL)                  ==  E_ALL)                  $levels = ['E_ALL'];                    // 32767
        else if (($level & (E_ALL & ~E_DEPRECATED)) == (E_ALL & ~E_DEPRECATED)) $levels = ['E_ALL & ~E_DEPRECATED'];    // 24575
        else {
            foreach ($levels as $key => $value) {
                if ($level & $key) continue;
                unset($levels[$key]);
            }
        }
        return join('|', $levels);
    }
}
