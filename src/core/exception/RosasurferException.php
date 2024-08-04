<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

use Throwable;

use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;


/**
 * Base class for all "rosasurfer" exceptions. Provides some additional convenient helpers.
 */
class RosasurferException extends \Exception implements RosasurferExceptionInterface {

    use RosasurferExceptionTrait, ObjectTrait, DiAwareTrait;


    /**
     * Create a new instance. Parameters are identical to the built-in PHP {@link \Exception} but stronger typed.
     *
     * @param  string     $message [optional] - exception description
     * @param  int        $code    [optional] - exception identifier (typically an application error id)
     * @param  ?Throwable $cause   [optional] - another throwable causing this throwable
     */
    public function __construct(string $message='', int $code=0, ?Throwable $cause=null) {
        parent::__construct($message, $code, $cause);
    }
}
