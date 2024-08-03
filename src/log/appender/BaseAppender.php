<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\appender;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\log\filter\ContentFilterInterface as ContentFilter;
use rosasurfer\ministruts\log\Logger;
use rosasurfer\ministruts\core\exception\InvalidValueException;


/**
 * BaseAppender
 *
 * Implements base functionality of log appenders.
 */
abstract class BaseAppender extends CObject implements AppenderInterface {

    /** @var mixed[] - configuration options */
    protected array $options;

    /** @var bool - whether the appender is enabled */
    protected bool $enabled;

    /** @var int - loglevel */
    protected int $logLevel;

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
        $this->enabled = (bool) filter_var($options['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);

        // read a configured appender loglevel
        $logLevel = 0;
        $value = $options['loglevel'] ?? '';
        if (is_string($value)) {
            $logLevel = Logger::strToLogLevel($value);
        }
        $this->logLevel = $logLevel;

        // read configured message details
        $this->traceDetails   = (bool) filter_var($options['details']['trace'  ] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->requestDetails = (bool) filter_var($options['details']['request'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->sessionDetails = (bool) filter_var($options['details']['session'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->serverDetails  = (bool) filter_var($options['details']['server' ] ?? false, FILTER_VALIDATE_BOOLEAN);

        /** @var ?string $class */
        $class = $options['filter'] ?? null;
        if (!is_null($class)) {
            if (!is_a($class, ContentFilter::class, true)) {
                throw new InvalidValueException('Invalid parameter $options[filter] (not a subclass of '.ContentFilter::class.')');
            }
            $this->filter = new $class();
        }
    }
}
