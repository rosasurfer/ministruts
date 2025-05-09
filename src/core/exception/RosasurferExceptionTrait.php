<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

use rosasurfer\ministruts\core\error\ErrorHandler;


/**
 * A trait adding the behavior of a {@link RosasurferException} to any custom {@link \Throwable}.
 */
trait RosasurferExceptionTrait {


    /**
     * Prepend a message to the exception's existing message. Used to enrich the exception with additional data.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function prependMessage(string $message): self {
        if (strlen($message)) {
            $this->message = trim($message.$this->message);
        }
        return $this;
    }


    /**
     * Append a message to the exception's existing message. Used to enrich the exception with additional data.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function appendMessage(string $message): self {
        if (strlen($message)) {
            $this->message = trim($this->message.$message);
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
    public function setCode(int $code): self {
        if (!isset($this->code)) {
            $this->code = $code;
        }
        return $this;
    }


    /**
     * {@inheritdoc}
     */
    public function __toString(): string {
        return ErrorHandler::getVerboseMessage($this);
    }
}
