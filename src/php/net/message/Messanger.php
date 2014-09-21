<?php
/**
 * Messanger
 *
 * Messanger-Factory und abstrakte Basisklasse für alle Messanger-Implementierungen.
 */
abstract class Messanger extends Object {


   /**
    * Constructor
    *
    * @param  array $options - Messanger-Optionen
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
?>
