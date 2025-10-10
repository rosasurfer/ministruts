<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\exception;

use Throwable;

/**
 * An interface defining common functionality for all "rosasurfer/ministruts" exceptions.
 */
interface ExceptionInterface extends Throwable {

    /**
     * Prepend a message to the exception's existing message.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function prependMessage(string $message): self;


    /**
     * Append a message to the exception's existing message.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function appendMessage(string $message): self;


    /**
     * Set the error code of an exception. Ignored if the error code is already set.
     *
     * @param  int $code
     *
     * @return $this
     */
    public function setCode(int $code): self;
}
