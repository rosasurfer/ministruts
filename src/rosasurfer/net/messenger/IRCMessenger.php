<?php
use rosasurfer\ministruts\exception\UnimplementedFeatureException;


/**
 * Messenger, der eine Nachricht an einen IRC-Channel schickt.
 */
class IRCMessenger extends Messenger {


   /**
    * Verschickt eine Nachricht.
    *
    * @param  string $channel - IRC-Channel
    * @param  string $message - Nachricht
   */
   public function sendMessage($receiver, $message) {
      throw new UnimplementedFeatureException('Method '.get_class($this).'::'.__FUNCTION__.'() is not implemented');
   }
}
