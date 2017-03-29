<?php
namespace rosasurfer\exception;


/**
 * An interface defining common functionality for all "rosasurfer" exceptions.
 */
interface RosasurferExceptionInterface {


    /**
     * Add a message to the exception's existing message. Used during up-bubbling to add additional information to an
     * exception's original message.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function addMessage($message);


    /**
     * Set the error code of an exception. Used during up-bubbling to add additional information to an existing exception.
     * Ignored if the exception's error code is already set.
     *
     * @param  mixed $code
     *
     * @return $this
     */
    public function setCode($code);


    /**
     * Return the exception's message in a more readable way.
     *
     * @return string
     */
    public function getBetterMessage();


    /**
     * Return the exception's stacktrace in a more readable way (Java-like).
     *
     * @return array
     */
    public function getBetterTrace();


    /**
     * Return a text representation of the exception's stacktrace in a more readable way (Java-like).
     *
     * @return string
     */
    public function getBetterTraceAsString();


    /**
     * Return the name of the function (if any) where the exception was raised.
     *
     * @return string
     */
    public function getFunctionName();
}
