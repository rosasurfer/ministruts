<?php
use rosasurfer\exception\UnimplementedFeatureException;


/**
 * Messenger, der eine SMS via Nexmo verschickt.
 */
class NexmoSMSMessenger extends Messenger {


   /**
    * Verschickt eine SMS.
    *
    * @param  string $receiver - Empfänger (internationales Format)
    * @param  string $message  - Nachricht
   */
   public function sendMessage($receiver, $message) {
      throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
   }
}
