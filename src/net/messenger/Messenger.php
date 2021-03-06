<?php
namespace rosasurfer\net\messenger;

use rosasurfer\core\CObject;


/**
 * Messenger
 *
 * Messenger-Factory und abstrakte Basisklasse fuer alle Messenger-Implementierungen.
 */
abstract class Messenger extends CObject {


    /**
     * Constructor
     *
     * @param  array $options - Messenger-Optionen
     */
    abstract protected function __construct(array $options);


    /**
     * Verschickt eine Message.
     *
     * @param  string $receiver - Empfaenger (internationales Format)
     * @param  string $message  - Nachricht
     *
     * @return void
     */
    abstract public function sendMessage($receiver, $message);
}
