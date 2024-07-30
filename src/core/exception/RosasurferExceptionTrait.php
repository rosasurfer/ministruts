<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\error\ErrorHandler;

use const rosasurfer\ministruts\NL;


/**
 * A trait adding the behavior of a {@link RosasurferException} to any custom {@link \Throwable}.
 */
trait RosasurferExceptionTrait {

    /** @var string - better message */
    private $betterMessage;

    /** @var array<string[]> - better stacktrace */
    private $betterTrace;

    /** @var string - better stacktrace as string */
    private $betterTraceAsString;


    /**
     * Add a message to the exception's existing message. Used to enrich the exception with additional data.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function appendMessage($message) {
        if (strlen($message)) {
            $this->message = trim(trim($this->message).NL.$message);
        }
        return $this;
    }


    /**
     * Set the error code of an exception. Used to enrich the exception with additional data.
     * Ignored if the error code is already set.
     *
     * @param  int $code
     *
     * @return $this
     */
    public function setCode($code) {
        Assert::int($code);

        if (!isset($this->code)) {
            $this->code = $code;
        }
        return $this;
    }


    /**
     * Return the message of the exception in a more readable way.
     *
     * @return string
     */
    public function getBetterMessage() {
        if (!$this->betterMessage)
            $this->betterMessage = ErrorHandler::getBetterMessage($this);
        return $this->betterMessage;
    }


    /**
     * Return the stacktrace of the exception in a more readable way as a string. The returned string contains nested exceptions.
     *
     * @return string
     */
    public function getBetterTraceAsString() {
        if (!$this->betterTraceAsString)
            $this->betterTraceAsString = ErrorHandler::getBetterTraceAsString($this);
        return $this->betterTraceAsString;
    }


    /**
     * Return a string representation of the exception.
     *
     * @return string
     */
    public function __toString() {
        $value = '';
        try {
            $value = $this->getBetterMessage();
            Assert::string($value);
        }                                                                       // Ensure __toString() doesn't throw an exception as otherwise
        catch (\Throwable $ex) { ErrorHandler::handleToStringException($ex); }  // PHP < 7.4 will trigger a non-catchable fatal error.
        return $value;                                                          // @see  https://bugs.php.net/bug.php?id=53648
    }
}
