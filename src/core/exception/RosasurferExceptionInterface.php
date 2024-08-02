<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;


/**
 * An interface defining common functionality for all "rosasurfer" exceptions.
 */
interface RosasurferExceptionInterface extends \Throwable {

    /**
     * Set the error code of an exception. Used to enrich the exception with additional data.
     * Ignored if the error code is already set.
     *
     * @param  int $code
     *
     * @return $this
     */
    public function setCode(int $code): self;


    /**
     * Prepend a message to the exception's existing message. Used to enrich the exception with additional data.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function prependMessage(string $message): self;


    /**
     * Append a message to the exception's existing message. Used to enrich the exception with additional data.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function appendMessage(string $message): self;
}
