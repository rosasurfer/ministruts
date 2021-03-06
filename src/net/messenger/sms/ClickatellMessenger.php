<?php
namespace rosasurfer\net\messenger\sms;

use rosasurfer\core\exception\UnimplementedFeatureException;


/**
 * A messenger client sending text messages via Clickatell.
 *
 * @see  https://www.clickatell.com/developers/
 */
class ClickatellMessenger {


    /**
     * Send an SMS.
     *
     * @param  string $receiver - phone number
     * @param  string $message  - message
   */
    public function sendMessage($receiver, $message) {
        throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
    }
}
