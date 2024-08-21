<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\appender;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\log\filter\ContentFilterInterface as ContentFilter;
use rosasurfer\ministruts\log\Logger;
use rosasurfer\ministruts\core\exception\InvalidValueException;

use const rosasurfer\ministruts\CLI;


/**
 * BaseAppender
 *
 * Implements common functionality of all log appenders.
 */
abstract class BaseAppender extends CObject implements AppenderInterface {

    /** @var mixed[] - configuration options */
    protected array $options;

    /** @var bool - whether the appender is enabled */
    protected bool $enabled;

    /** @var int - the appender's loglevel (may differ from application loglevel) */
    protected int $logLevel = 0;

    /** @var bool - whether stacktrace details will be logged by this instance */
    protected bool $traceDetails = false;

    /** @var bool - whether request details will be logged by this instance */
    protected bool $requestDetails = false;

    /** @var bool - whether session details will be logged by this instance */
    protected bool $sessionDetails = false;

    /** @var bool - whether server environment details will be logged by this instance */
    protected bool $serverDetails = false;

    /** @var ?ContentFilter - a configured content filter, if any */
    protected ?ContentFilter $filter = null;


    /**
     * Constructor
     *
     * @param  mixed[] $options - appender configuration
     */
    public function __construct(array $options) {
        // read activation status
        $this->options = $options;
        $this->enabled = true;                          // TRUE if instantiated (explicit or implicit, no need to double-check)

        // read a configured appender loglevel
        $logLevel = 0;
        $value = $options['loglevel'] ?? '';
        if (is_string($value)) {
            $logLevel = Logger::strToLogLevel($value);
        }
        $this->logLevel = $logLevel;

        // read configured message details
        $this->traceDetails   = filter_var($options['details']['trace'  ] ?? static::getDefaultTraceDetails(),   FILTER_VALIDATE_BOOLEAN);
        $this->requestDetails = filter_var($options['details']['request'] ?? static::getDefaultRequestDetails(), FILTER_VALIDATE_BOOLEAN);
        $this->sessionDetails = filter_var($options['details']['session'] ?? static::getDefaultSessionDetails(), FILTER_VALIDATE_BOOLEAN);
        $this->serverDetails  = filter_var($options['details']['server' ] ?? static::getDefaultServerDetails(),  FILTER_VALIDATE_BOOLEAN);

        // read a configured content filter
        /** @var ?string $class */
        $class = $options['filter'] ?? null;
        if (isset($class)) {
            if (!is_a($class, ContentFilter::class, true)) {
                throw new InvalidValueException('Invalid parameter $options[filter] (not a subclass of '.ContentFilter::class.')');
            }
            $this->filter = new $class();
        }
    }


    /**
     * Return the default "enabled" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultEnabled(): bool {
        return false;
    }


    /**
     * Return the default "details.trace" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultTraceDetails(): bool {
        return true;
    }


    /**
     * Return the default "details.request" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultRequestDetails(): bool {
        return false;
    }


    /**
     * Return the default "details.session" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultSessionDetails(): bool {
        return false;
    }


    /**
     * Return the default "details.server" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultServerDetails(): bool {
        return false;
    }


    /**
     * Return the default "aggregate-messages" status of the appender if not explicitely configured.
     *
     * @return bool
     */
    public static function getDefaultAggregation(): bool {
        return !CLI;
    }
}
