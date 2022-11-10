<?php
namespace rosasurfer\core\error;

use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;

use function rosasurfer\strEndsWith;

use const rosasurfer\NL;


/**
 * Debug helper.
 */
class DebugHelper extends StaticClass {


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
        $result = ErrorHandler::formatTrace($trace, $indent);

        if ($cause = $exception->getPrevious()) {
            // recursively add stacktraces of nested exceptions
            $message = trim(ErrorHandler::composeBetterMessage($cause, $indent));
            $result .= NL.$indent.'caused by'.NL.$indent.$message.NL.NL;
            $result .= self::{__FUNCTION__}($cause, $indent);                 // recursion
        }
        return $result;
    }
}
