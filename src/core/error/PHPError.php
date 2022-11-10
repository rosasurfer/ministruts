<?php
namespace rosasurfer\core\error;

use rosasurfer\core\ObjectTrait;
use rosasurfer\core\di\DiAwareTrait;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;
use rosasurfer\core\exception\RosasurferExceptionTrait;


/**
 * An exception representing a PHP error.
 */
class PHPError extends \ErrorException implements IRosasurferException {

    use RosasurferExceptionTrait, ObjectTrait, DiAwareTrait;


    /**
     * Create a new instance. Parameters are identical to the built-in PHP {@link \ErrorException} but stronger typed.
     *
     * @param  string $message  - error description
     * @param  int    $code     - error identifier, usually an application id
     * @param  int    $severity - PHP error reporting level of the error
     * @param  string $file     - the name of the file where the error occurred
     * @param  int    $line     - the line number in the file where the error occurred
     */
    public function __construct($message, $code, $severity, $file, $line) {
        parent::__construct($message, $code, $severity, $file, $line, $cause=null);
    }


    /**
     * @return array
     */
    public function getBetterTrace() {
        $trace = $this->betterTrace;

        if (!$trace) {
            // transform the original stacktrace into a better one
            $trace = ErrorHandler::adjustTrace($this->getTrace(), $this->getFile(), $this->getLine());

            // drop the first frame if the exception was created in the registered error handler
            if (DebugHelper::getFQFunctionName($trace[0]) == ErrorHandler::class.'::handleError') {
                \array_shift($trace);

                // if the error was triggered by include/require/_once: fix the next frame, it's wrong
                if (sizeof($trace) > 1) {
                    $function = DebugHelper::getFQFunctionName($trace[0]);
                    if ($function=='include' || $function=='include_once' || $function=='require' || $function=='require_once') {
                        if (isset($trace[0]['file']) && isset($trace[1]['file'])) {
                            if ($trace[0]['file'] == $trace[1]['file']) {
                                if (isset($trace[0]['line']) && isset($trace[1]['line'])) {
                                    if ($trace[0]['line'] == $trace[1]['line']) {
                                        unset($trace[0]['file'], $trace[0]['line']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
            // store the new stacktrace
            $this->betterTrace = $trace;
        }
        return $trace;
    }


    /**
     * Return the error type of this PHP error.
     *
     * @return string
     */
    public function getErrorType() {
        return 'PHP Error';
    }
}
