<?php
/**
 * Messanger, der eine Nachricht an einen ICQ-Kontakt verschickt.
 */
class ICQMessanger extends Messanger {


   /**
    * Verschickt eine Nachricht.
    *
    * @param  string $receiver - ICQ-Kontakt
    * @param  string $message  - Nachricht
   */
   public function sendMessage($receiver, $message) {
      throw new UnimplementedFeatureException('Method '.get_class($this).'::'.__FUNCTION__.'() is not implemented');
   }
}
?>
