<?php
namespace rosasurfer\exception;

use rosasurfer\debug\DebugHelper;

use const rosasurfer\NL;


/**
 * A trait adding the behaviour of {@link RosasurferException}s to any {@link \Exception}.
 */
trait RosasurferExceptionTrait {


    /** @var string - better message */
    private $betterMessage;

    /** @var array - better stacktrace */
    private $betterTrace;

    /** @var string - better stacktrace as string */
    private $betterTraceAsString;


    /**
     * Add a message to the exception's existing message. Used to add additional information to an existing message.
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
     * Set the error code of the exception *only* if it's not yet set. An existing error code cannot be changed. In this
     * case the method does nothing.
     *
     * @param  mixed $code
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
     * Return the exception's message in a more readable way.
     *
     * @return string - message
     */
    public function getBetterMessage() {
        if (!$this->betterMessage)
            $this->betterMessage = DebugHelper::composeBetterMessage($this);
        return $this->betterMessage;
    }


    /**
     * Return a text representation of the exception's stacktrace in a more readable way (Java-like).
     *
     * @return string
     */
    public function getBetterTraceAsString() {
        if (!$this->betterTraceAsString)
            $this->betterTraceAsString = DebugHelper::getBetterTraceAsString($this);
        return $this->betterTraceAsString;
    }


    /**
     * Return the name of the function (if any) where the exception was raised.
     *
     * @return string
     */
    public function getFunctionName() {
        return DebugHelper::getFQFunctionName($this->getBetterTrace()[0]);
    }


    /**
     * Return a description of the exception.
     *
     * @return string - description
     */
    public function __toString() {
        return $this->getBetterMessage();
    }
}
