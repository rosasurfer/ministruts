<?php
namespace rosasurfer\net\messenger;

use rosasurfer\core\CObject;


/**
 * Messenger
 *
 * A Messenger factory and abstract base class for all Messenger implementations.
 */
abstract class Messenger extends CObject {

    /** @var scalar[] */
    protected $options;


    /**
     * Constructor
     *
     * @param  scalar[] $options
     */
    abstract protected function __construct(array $options);


    /**
     * Send a message.
     *
     * @param  string $receiver
     * @param  string $message
     *
     * @return void
     */
    abstract public function sendMessage($receiver, $message);
}
