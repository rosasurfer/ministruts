<?php
namespace rosasurfer\debug;

use rosasurfer\core\StaticClass;

use rosasurfer\exception\IllegalTypeException;
use rosasurfer\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\exception\error\PHPError;

use function rosasurfer\normalizeEOL;
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
     * @param  array  $trace - regular PHP stacktrace
     * @param  string $file  - name of the file where the stacktrace was generated (optional)
     * @param  int    $line  - line of the file where the stacktrace was generated (optional)
     *
     * @return array - new fixed stacktrace
     *
     * @example
     * original stacktrace:
     * <pre>
     *   require_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
     *   include_once()  # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
     *   include()       # line 26, file: /var/www/phalcon/vokuro/public/index.php
     *   {main}
     * </pre>
     *
     * new stacktrace:
     * <pre>
     *   require_once()             [php]
     *   include_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
     *   include()       # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
     *   {main}          # line 26, file: /var/www/phalcon/vokuro/public/index.php
     * </pre>
     */
    public static function fixTrace(array $trace, $file='unknown', $line=0) {
        // check if the stacktrace is already fixed
        if ($trace && isSet($trace[0]['fixed']))
            return $trace;

        // Fix an incomplete frame[0][line] if parameters are provided and $file matches (e.g. with \SimpleXMLElement).
        if ($file!='unknown' && $line) {
            if (isSet($trace[0]['file']) && $trace[0]['file']==$file) {
                if (isSet($trace[0]['line']) && $trace[0]['line']===0) {
                    $trace[0]['line'] = $line;
                }
            }
        }

        // Add a frame for the main script to the bottom (end of array).
        $trace[] = ['function' => '{main}'];

        // Move FILE and LINE fields down (to the end) by one position.
        for ($i=sizeOf($trace); $i--;) {
            if (isSet($trace[$i-1]['file'])) $trace[$i]['file'] = $trace[$i-1]['file'];
            else                       unset($trace[$i]['file']);

            if (isSet($trace[$i-1]['line'])) $trace[$i]['line'] = $trace[$i-1]['line'];
            else                       unset($trace[$i]['line']);

            $trace[$i]['fixed'] = true;
            /*
            if (isSet($trace[$i]['args'])) {                   // skip content of large vars
                foreach ($trace[$i]['args'] as &$arg) {
                    if     (is_object($arg)) $arg = get_class($arg);
                    elseif (is_array ($arg)) $arg = 'Array()';
                } unset($arg);
            }
            */
        }

        // Add location details from parameters to frame[0] only if they differ from the old values (now in frame[1])
        if (!isSet($trace[1]['file']) || !isSet($trace[1]['line']) || $trace[1]['file']!=$file || $trace[1]['line']!=$line) {
            $trace[0]['file'] = $file;                          // test with:
            $trace[0]['line'] = $line;                          // \SQLite3::enableExceptions(true|false);
        }                                                       // \SQLite3::exec($invalid_sql);
        else {
            unset($trace[0]['file'], $trace[0]['line']);        // otherwise delete them
        }

        // Remove the last frame (the one we added for the main script) if it now points to an unknown location (PHP core).
        $size = sizeOf($trace);
        if (!isSet($trace[$size-1]['file'])) {
            array_pop($trace);
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
     * @param  array  $trace  - stacktrace
     * @param  string $indent - indent the formatted lines by this value (default: empty string)
     *
     * @return string
     */
    public static function formatTrace(array $trace, $indent='') {
        $result = '';

        $size = sizeOf($trace);
        $callLen = $lineLen = 0;

        for ($i=0; $i < $size; $i++) {               // align FILE and LINE
            $frame =& $trace[$i];

            $call = self::getFQFunctionName($frame, $nsLowerCase=true);
            $call!='{main}' && $call!='{closure}' && $call.='()';
            $callLen = max($callLen, strLen($call));
            $frame['call'] = $call;

            $frame['line'] = isSet($frame['line']) ? ' # line '.$frame['line'].',' : '';
            $lineLen = max($lineLen, strLen($frame['line']));

            if (isSet($frame['file']))                 $frame['file'] = ' file: '.$frame['file'];
            elseif (strStartsWith($call, 'phalcon\\')) $frame['file'] = ' [php-phalcon]';
            else                                       $frame['file'] = ' [php]';
        }

        for ($i=0; $i < $size; $i++) {
            $result .= $indent.str_pad($trace[$i]['call'], $callLen).' '.str_pad($trace[$i]['line'], $lineLen).$trace[$i]['file'].NL;
        }

        return $result;
    }


    /**
     * Return the fully qualified function or method name of a stacktrace's frame.
     *
     * @param  array $frame       - frame
     * @param  bool  $nsLowerCase - whether or not the namespace part of the name to return in lower case (default: no)
     *
     * @return string - fully qualified name (without trailing parentheses)
     */
    public static function getFQFunctionName(array $frame, $nsLowerCase=false) {
        $class = $function = '';

        if (isSet($frame['function'])) {
            $function = $frame['function'];

            if (isSet($frame['class'])) {
                $class = $frame['class'];
                if ($nsLowerCase && is_int($pos=strRPos($class, '\\')))
                    $class = strToLower(subStr($class, 0, $pos)).subStr($class, $pos);
                $class = $class.$frame['type'];
            }
            elseif ($nsLowerCase && is_int($pos=strRPos($function, '\\'))) {
                $function = strToLower(subStr($function, 0, $pos)).subStr($function, $pos);
            }
        }
        return $class.$function;
    }


    /**
     * Return a more readable version of an exception's message.
     *
     * @param  \Exception $exception - any exception (not only RosasurferExceptions)
     * @param  string     $indent    - indent lines by this value (default: empty string)
     *
     * @return string - message
     */
    public static function composeBetterMessage(\Exception $exception, $indent='') {
        if ($exception instanceof PHPError) {
            $result    = $exception->getSimpleType();
        }
        else {
            $class     = get_class($exception);
            $namespace = strToLower(strLeftTo($class, '\\', -1, true, ''));
            $baseName  = strRightFrom($class, '\\', -1, false, $class);
            $result    = $indent.$namespace.$baseName;

            if ($exception instanceof \ErrorException)                                  // A PHP error exception not created
                $result .= '('.self::errorLevelToStr($exception->getSeverity()).')';    // by the framework.
        }
        $message = $exception->getMessage();

        if (strLen($indent)) {
            $lines = explode(NL, normalizeEOL($message));                               // indent multiline messages
            $eom = '';
            if (strEndsWith($message, NL)) {
                array_pop($lines);
                $eom = NL;
            }
            $message = join(NL.$indent, $lines).$eom;
        }

        $result .= (strLen($message) ? ': ':'').$message;
        return $result;
    }


    /**
     * Return a more readable version of an exception's stacktrace. The representation also contains informations about
     * nested exceptions.
     *
     * @param  \Exception $exception - any exception (not only RosasurferExceptions)
     * @param  string     $indent    - indent the resulting lines by this value (default: empty string)
     *
     * @return string - readable stacktrace
     */
    public static function getBetterTraceAsString(\Exception $exception, $indent='') {
        if ($exception instanceof IRosasurferException) $trace = $exception->getBetterTrace();
        else                                            $trace = self::fixTrace($exception->getTrace(), $exception->getFile(), $exception->getLine());
        $result = self::formatTrace($trace, $indent);

        if ($cause=$exception->getPrevious()) {
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
        if (!is_int($level)) throw new IllegalTypeException('Illegal type of parameter $level: '.getType($level));

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

        if     (!$level)                  $levels = ['0'];      //  zero
        else if ($level & E_ALL == E_ALL) $levels = ['E_ALL'];  // 32767
        else {
            foreach ($levels as $key => $value) {
                if ($level & $key) continue;
                unset($levels[$key]);
            }
        }
        return join('|', $levels);
    }
}
