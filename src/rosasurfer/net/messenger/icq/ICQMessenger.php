<?php
use rosasurfer\exception\UnimplementedFeatureException;


/**
 * Messenger, der eine Nachricht an einen ICQ-Kontakt verschickt.
 */
class ICQMessenger extends Messenger {


   /**
    * Verschickt eine Nachricht.
    *
    * @param  string $receiver - ICQ-Kontakt
    * @param  string $message  - Nachricht
   */
   public function sendMessage($receiver, $message) {
      throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
   }
}
