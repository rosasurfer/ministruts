<?php
use rosasurfer\ministruts\exception\UnimplementedFeatureException;


/**
 * Messenger, der eine SMS via Clickatell verschickt.
 */
class ClickatellSMSMessenger extends Messenger {


   /**
    * Verschickt eine SMS.
    *
    * @param  string $receiver - Empfänger (internationales Format)
    * @param  string $message  - Nachricht
   */
   public function sendMessage($receiver, $message) {
      throw new UnimplementedFeatureException('Method '.get_class($this).'::'.__FUNCTION__.'() is not implemented');
   }
}
