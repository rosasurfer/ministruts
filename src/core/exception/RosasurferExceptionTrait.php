<?php
namespace rosasurfer\core\exception;

use rosasurfer\core\assert\Assert;
use rosasurfer\core\debug\DebugHelper;
use rosasurfer\core\debug\ErrorHandler;

use const rosasurfer\NL;


/**
 * A trait capable of adding the behaviour of {@link RosasurferException} to any {@link \Exception} or {@link \Throwable}.
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
        $this->message = trim(trim($this->message).NL.$message);
        return $this;
    }


    /**
     * Set the error code of an {@link \Exception} or {@link \Throwable}. Used during up-bubbling to add additional infos
     * to an existing exception. Ignored if the exception's error code is already set.
     *
     * @param  int|string $code
     *
     * @return $this
     */
    public function setCode($code) {
        if (!isset($this->code)) {
            $this->code = $code;
        }
        return $this;
    }


    /**
     * Return the message of the {@link \Exception} or {@link \Throwable} in a more readable way.
     *
     * @return string
     */
    public function getBetterMessage() {
        if (!$this->betterMessage)
            $this->betterMessage = DebugHelper::composeBetterMessage($this);
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
     * Return the name of the function or method (if any) where the {@link \Exception} or {@link \Throwable} was created.
     *
     * @return string
     */
    public function getFunction() {
        return DebugHelper::getFQFunctionName($this->getBetterTrace()[0]);
    }


    /**
     * Return a string representation of the {@link \Exception} or {@link \Throwable}.
     *
     * @return string
     */
    public function __toString() {
        try {
            $value = $this->getBetterMessage();
            Assert::string($value);                             // Ensure the method returns a string value as otherwise...
            return $value;                                      // PHP will trigger a non-catchable fatal error.
        }
        catch (\Throwable $ex) { ErrorHandler::handleToStringException($ex); }
        catch (\Exception $ex) { ErrorHandler::handleToStringException($ex); }
    }
}
