<?php
namespace rosasurfer\core\exception;


/**
 * An interface defining common functionality for all "rosasurfer" exceptions.
 */
interface RosasurferExceptionInterface {


    /**
     * Add a message to the exception's existing message. Used during up-bubbling to add additional infos to an exception's
     * original message.
     *
     * @param  string $message
     *
     * @return $this
     */
    public function addMessage($message);


    /**
     * Return the message of the {@link \Exception} or {@link \Throwable}.
     *
     * @return string
     */
    public function getMessage();


    /**
     * Return the message of the {@link \Exception} or {@link \Throwable} in a more readable way.
     *
     * @return string
     */
    public function getBetterMessage();


    /**
     * Set the error code of an {@link \Exception} or {@link \Throwable}. Used during up-bubbling to add additional infos
     * to an existing exception. Ignored if the exception's error code is already set.
     *
     * @param  int|string $code
     *
     * @return $this
     */
    public function setCode($code);


    /**
     * Return the error code of the {@link \Exception} or {@link \Throwable}.
     *
     * @return int|string
     */
    public function getCode();


    /**
     * Return the filename where the {@link \Exception} or {@link \Throwable} was created.
     *
     * @return string
     */
    public function getFile();


    /**
     * Return the line number of the file where the {@link \Exception} or {@link \Throwable} was created.
     *
     * @return int
     */
	public function getLine();


    /**
     * Return the name of the function or method (if any) where the {@link \Exception} or {@link \Throwable} was created.
     *
     * @return string
     */
    public function getFunction();


    /**
     * Return the stack trace of the {@link \Exception} or {@link \Throwable}.
     *
     * @return array
     */
	public function getTrace();


    /**
     * Return the stack trace of the {@link \Exception} or {@link \Throwable} as a string.
     *
     * @return string
     */
	public function getTraceAsString();


	/**
     * Return the stack trace of the {@link \Exception} or {@link \Throwable} in a more readable way (Java-like).
     *
     * @return array
     */
    public function getBetterTrace();


    /**
     * Return a string representation of the stack trace of the {@link \Exception} or {@link \Throwable} in a more readable
     * way (Java-like).
     *
     * @return string
     */
    public function getBetterTraceAsString();


    /**
     * Return the {@link \Exception} or {@link \Throwable} (if any) causing this exception.
     *
     * @return \Exception|\Throwable|null
     */
	public function getPrevious();


    /**
     * Return a string representation of the {@link \Exception} or {@link \Throwable}.
     *
     * @return string
     */
	public function __toString();
}
