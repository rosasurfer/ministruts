<?php
namespace rosasurfer\net\messenger\sms;

use rosasurfer\core\exception\UnimplementedFeatureException;
use rosasurfer\net\messenger\Messenger;


/**
 * ClickatellMessenger
 *
 * A {@link rosasurfer\net\messenger\Messenger} sending text messages via Clickatell.
 *
 * @see  https://www.clickatell.com/developers/
 */
class ClickatellMessenger extends Messenger {


    /**
     * {@inheritdoc}
     *
     * @param  array $options
     */
    protected function __construct(array $options) {
        $this->options = $options;
    }


    /**
     * Send a text message.
     *
     * @param  string $receiver - phone number
     * @param  string $message  - message
     */
    public function sendMessage($receiver, $message) {
        throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
    }
}
