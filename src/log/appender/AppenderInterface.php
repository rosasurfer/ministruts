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


    /**
     * Return the default "enabled" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultEnabled(): bool;


    /**
     * Return the default "details.trace" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultTraceDetails(): bool;


    /**
     * Return the default "details.request" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultRequestDetails(): bool;


    /**
     * Return the default "details.session" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultSessionDetails(): bool;


    /**
     * Return the default "details.server" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultServerDetails(): bool;


    /**
     * Return the default "aggregate-messages" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultAggregation(): bool;
}
