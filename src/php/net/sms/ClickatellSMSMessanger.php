<?php
/**
 * SMSMessanger, der SMS via Clickatell verschickt.
 */
class ClickatellSMSMessanger extends SMSMessanger {


   /**
    * Verschickt eine SMS.
    *
    * @param  string $receiver - Empfänger (internationales Format)
    * @param  string $message  - Inhalt der SMS
   */
   public function sendSMS($receiver, $message) {
      throw new UnimplementedFeatureException('Method '.get_class($this).'::'.__FUNCTION__.'() is not implemented');
   }
}
?>
