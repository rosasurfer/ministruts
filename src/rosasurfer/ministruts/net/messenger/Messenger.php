<?php
use rosasurfer\ministruts\core\Object;


/**
 * Messenger
 *
 * Messenger-Factory und abstrakte Basisklasse für alle Messenger-Implementierungen.
 */
abstract class Messenger extends Object {


   /**
    * Constructor
    *
    * @param  array $options - Messenger-Optionen
    */
   abstract protected function __construct(array $options);


   /**
    * Verschickt eine Message.
    *
    * @param  string $receiver - Empfänger (internationales Format)
    * @param  string $message  - Nachricht
   */
   abstract public function sendMessage($receiver, $message);
}
