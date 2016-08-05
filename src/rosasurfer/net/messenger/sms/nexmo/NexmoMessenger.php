<?php
namespace rosasurfer\net\messenger\sms\nexmo;

use rosasurfer\exception\UnimplementedFeatureException;


/**
 * A messenger client sending text messages via Nexmo.
 *
 * @see  https://docs.nexmo.com/messaging/sms-api
 */
class NexmoMessenger {


   /**
    * Send a text message to the specified receiver.
    *
    * @param  string $receiver - phone number
    * @param  string $message  - message
   */
   public function sendMessage($receiver, $message) {
      throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
   }
}
