<?php
namespace rosasurfer\core\exception;


/**
 * An interface defining common functionality for all "rosasurfer" exceptions.
 */
interface RosasurferExceptionInterface {

    /**
     * Return the message of the exception.
     *
     * @return string
     */
    public function getMessage();


    /**
     * Return the message of the exception in a more readable way.
     *
     * @return string
     */
    public function getBetterMessage();


    /**
     * Add a message to the exception's existing message. Used to enrich the exception with additional data.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function appendMessage($message);


    /**
     * Return the error code of the exception.
     *
     * @return int
     */
    public function getCode();


    /**
     * Set the error code of an exception. Used to enrich the exception with additional data.
     * Ignored if the error code is already set.
     *
     * @param  int $code
     *
     * @return $this
     */
    public function setCode($code);


    /**
     * Return the name of the file where the exception was created.
     *
     * @return string
     */
    public function getFile();


    /**
     * Return the line of the file where the exception was created.
     *
     * @return int
     */
	public function getLine();


    /**
     * Return the stack trace of the exception.
     *
     * @return array
     */
	public function getTrace();


    /**
     * Return the stack trace of the exception as a string.
     *
     * @return string
     */
	public function getTraceAsString();


	/**
     * Return the stack trace of the exception in a more readable way.
     *
     * @return array
     */
    public function getBetterTrace();


    /**
     * Return the stacktrace of the exception in a more readable way as a string. The returned string contains nested exceptions.
     *
     * @return string
     */
    public function getBetterTraceAsString();


    /**
     * Return the exception causing this exception (if any).
     *
     * @return ?\Exception|\Throwable
     */
	public function getPrevious();


    /**
     * Return a string representation of the exception.
     *
     * @return string
     */
	public function __toString();
}
