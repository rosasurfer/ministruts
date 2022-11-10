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
     * Take a regular PHP stacktrace and adjust it to be more readable.
     *
     * @param  array  $trace           - regular PHP stacktrace
     * @param  string $file [optional] - name of the file where the stacktrace was generated
     * @param  int    $line [optional] - line of the file where the stacktrace was generated
     *
     * @return array - adjusted stacktrace
     *
     * @example
     * before:
     * <pre>
     *  require_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
     *  include_once()  # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
     *  include()       # line 26, file: /var/www/phalcon/vokuro/public/index.php
     *  {main}
     * </pre>
     *
     * after:
     * <pre>
     *  require_once()             [php]
     *  include_once()  # line 5,  file: /var/www/phalcon/vokuro/vendor/autoload.php
     *  include()       # line 21, file: /var/www/phalcon/vokuro/app/config/loader.php
     *  {main}          # line 26, file: /var/www/phalcon/vokuro/public/index.php
     * </pre>
     */
    public static function adjustTrace(array $trace, $file='unknown', $line=0) {
        // check if the stacktrace is already adjusted
        if ($trace && isset($trace[0]['__ministruts_adjusted__']))
            return $trace;

        // fix an incomplete frame[0][line] if parameters are provided and $file matches (e.g. with \SimpleXMLElement)
        if ($file!='unknown' && $line) {
            if (isset($trace[0]['file']) && $trace[0]['file']==$file) {
                if (isset($trace[0]['line']) && $trace[0]['line']===0) {
                    $trace[0]['line'] = $line;
                }
            }
        }

        // append a frame for the main script
        $trace[] = ['function' => '{main}'];

        // move fields FILE and LINE to the end by one position
        for ($i=sizeof($trace); $i--;) {
            if (isset($trace[$i-1]['file'])) $trace[$i]['file'] = $trace[$i-1]['file'];
            else                       unset($trace[$i]['file']);

            if (isset($trace[$i-1]['line'])) $trace[$i]['line'] = $trace[$i-1]['line'];
            else                       unset($trace[$i]['line']);

            $trace[$i]['__ministruts_adjusted__'] = true;
        }

        // add location details from parameters to frame[0] only if they differ from the old values (now in frame[1])
        if (!isset($trace[1]['file']) || !isset($trace[1]['line']) || $trace[1]['file']!=$file || $trace[1]['line']!=$line) {
            $trace[0]['file'] = $file;                          // test with:
            $trace[0]['line'] = $line;                          // \SQLite3::enableExceptions(true|false);
        }                                                       // \SQLite3::exec($invalid_sql);
        else {
            unset($trace[0]['file'], $trace[0]['line']);        // otherwise delete them
        }

        // remove the last frame (the one appended for the main script) if it now points to an unknown location (PHP core).
        $size = sizeof($trace);
        !isset($trace[$size-1]['file']) && \array_pop($trace);

        return $trace;

        // TODO: fix wrong stack frames originating from calls to virtual static functions
        //
        // phalcon\mvc\Model::__callStatic()                  [php-phalcon]
        // vokuro\models\Users::findFirstByEmail() # line 27, file: F:\Projekte\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
        // vokuro\auth\Auth->check()               # line 27, file: F:\Projekte\phalcon\sample-apps\vokuro\app\library\Auth\Auth.php
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
        else                                            $trace = self::adjustTrace($exception->getTrace(), $exception->getFile(), $exception->getLine());
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
