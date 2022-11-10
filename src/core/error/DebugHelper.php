<?php
namespace rosasurfer\core\error;

use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;

use function rosasurfer\strEndsWith;
use function rosasurfer\strRightFrom;
use function rosasurfer\strStartsWith;

use const rosasurfer\NL;


/**
 * Debug helper.
 */
class DebugHelper extends StaticClass {

    /**
     * Return a formatted and human-readable version of a stacktrace.
     *
     * @param  array  $trace             - stacktrace
     * @param  string $indent [optional] - indent the formatted lines by this value (default: empty string)
     *
     * @return string
     */
    public static function formatTrace(array $trace, $indent = '') {
        $config  = self::di('config');
        $appRoot = $config ? $config['app.dir.root'] : null;
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
        else                                            $trace = ErrorHandler::adjustTrace($exception->getTrace(), $exception->getFile(), $exception->getLine());
        $result = self::formatTrace($trace, $indent);

        if ($cause = $exception->getPrevious()) {
            // recursively add stacktraces of nested exceptions
            $message = trim(ErrorHandler::composeBetterMessage($cause, $indent));
            $result .= NL.$indent.'caused by'.NL.$indent.$message.NL.NL;
            $result .= self::{__FUNCTION__}($cause, $indent);                 // recursion
        }
        return $result;
    }
}
