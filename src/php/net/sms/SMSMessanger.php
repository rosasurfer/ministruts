<?php
/**
 * SMSMessanger
 *
 * SMS-Messanger-Factory und abstrakte Basisklasse für alle Messanger-Implementierungen.
 */
abstract class SMSMessanger extends Object {


   /**
    * Constructor
    *
    * @param  array $options - Messanger-Optionen
    */
   abstract protected function __construct(array $options);


   /**
    * Verschickt eine SMS.
    *
    * @param  string $receiver - Empfänger (internationales Format)
    * @param  string $message  - Inhalt der SMS
   */
   abstract public function sendSMS($receiver, $message);
}
?>
