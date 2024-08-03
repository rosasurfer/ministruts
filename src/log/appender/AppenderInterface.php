<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\appender;

use rosasurfer\ministruts\log\LogMessage;


/**
 * Interface for log appenders.
 */
interface AppenderInterface {

    /**
     * Create and initialize a new instance.
     *
     * @param  mixed[] $options - configuration options
     */
    public function __construct(array $options);


    /**
     * Append a log message to the destination of the appender.
     *
     * @param  LogMessage $message
     *
     * @return bool - Whether logging should continue with the next registered appender. Returning FALSE interrupts the chain.
     */
    public function appendMessage(LogMessage $message): bool;
}
