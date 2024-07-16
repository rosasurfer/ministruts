<?php
namespace rosasurfer\core\exception;

use rosasurfer\core\ObjectTrait;
use rosasurfer\core\debug\DebugHelper;
use rosasurfer\di\DiAwareTrait;
use rosasurfer\core\exception\RosasurferExceptionInterface as IRosasurferException;


/**
 * Base class for all "rosasurfer" exceptions.
 */
class RosasurferException extends \Exception implements IRosasurferException {

    use RosasurferExceptionTrait, ObjectTrait, DiAwareTrait;


    /**
     * Create a new instance. Parameters are identical to the built-in PHP {@link \Exception} and passed on.
     *
     * @param  string                $message [optional] - exception description
     * @param  int                   $code    [optional] - exception identifier, typically an application id
     * @param  \Exception|\Throwable $cause   [optional] - another exception (PHP5) or throwable (PHP7) causing this exception
     */
    public function __construct($message=null, $code=null, $cause = null) {
        parent::__construct($message, $code, $cause);
    }


    /**
     * @return array
     */
    public function getBetterTrace() {
        $betterTrace = $this->betterTrace;

        if (!$betterTrace) {
            // transform the original stacktrace into a better one
            $betterTrace = DebugHelper::fixTrace($this->getTrace(), $this->getFile(), $this->getLine());

            /*
            // if the exception was thrown in a magic "__set()" shift frames until we reach the erroneous assignment
            while (strtolower($trace[0]['function']) == '__set') {
                \array_shift($trace);
            }

            // if the exception was thrown in a magic "__call()" shift frames until we reach the erroneous call
            if (strtolower($trace[0]['function']) == '__call') {
                while (strtolower($trace[0]['function']) == '__call') {
                    \array_shift($trace);
                }
                \array_shift($trace);                              // that's one level more than for "__set()"
            }
            */

            // store the new stacktrace
            $this->betterTrace = $betterTrace;
        }
        return $betterTrace;
    }
}
