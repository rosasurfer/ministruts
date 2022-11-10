<?php
namespace rosasurfer\core\exception;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\error\DebugHelper;
use rosasurfer\core\error\ErrorHandler;

use const rosasurfer\NL;


/**
 * A trait adding the behavior of a {@link RosasurferException} to any custom {@link \Exception} or {@link \Throwable}.
 */
trait RosasurferExceptionTrait {


    /** @var string - better message */
    private $betterMessage;

    /** @var array - better stacktrace */
    private $betterTrace;

    /** @var string - better stacktrace as string */
    private $betterTraceAsString;


    /**
     * Add a message to the exception's existing message. Used during up-bubbling to add additional infos to an exception's
     * original message.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function addMessage($message) {
        if (strlen($message)) {
            $this->message = trim(trim($this->message).NL.$message);
        }
        return $this;
    }


    /**
     * Set the error code of an {@link \Exception} or {@link \Throwable}. Used during up-bubbling to add additional infos
     * to an existing exception. Ignored if the exception's error code is already set.
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
            $this->betterMessage = ErrorHandler::composeBetterMessage($this);
        return $this->betterMessage;
    }


    /**
     * Return a string representation of the stack trace of the {@link \Exception} or {@link \Throwable} in a more readable
     * way (Java-like).
     *
     * @return string
     */
    public function getBetterTraceAsString() {
        if (!$this->betterTraceAsString)
            $this->betterTraceAsString = DebugHelper::getBetterTraceAsString($this);
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
        catch (\Exception $ex) { ErrorHandler::handleToStringException($ex); }  // @see  https://bugs.php.net/bug.php?id=53648

        return $value;
    }
}
