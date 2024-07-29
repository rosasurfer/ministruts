<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;
use rosasurfer\ministruts\core\error\ErrorHandler;


/**
 * Base class for all "rosasurfer" exceptions.
 */
class RosasurferException extends \Exception implements RosasurferExceptionInterface {

    use RosasurferExceptionTrait, ObjectTrait, DiAwareTrait;


    /**
     * Create a new instance. Parameters are identical to the built-in PHP {@link \Exception} but stronger typed.
     *
     * @param  string      $message [optional] - exception description
     * @param  int         $code    [optional] - exception identifier (typically an application error id)
     * @param  ?\Throwable $cause   [optional] - another throwable causing this throwable
     */
    public function __construct($message='', $code=0, $cause=null) {
        parent::__construct($message, $code, $cause);
    }


	/**
     * Return the stack trace of the exception in a more readable way.
     *
     * @return array
     */
    public function getBetterTrace() {
        $betterTrace = $this->betterTrace;

        if (!$betterTrace) {
            // transform the original stacktrace into a better one
            $betterTrace = ErrorHandler::getBetterTrace($this->getTrace(), $this->getFile(), $this->getLine());

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

            $this->betterTrace = $betterTrace;
        }
        return $betterTrace;
    }
}
