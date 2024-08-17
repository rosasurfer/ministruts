<?php
namespace rosasurfer\net\messenger\sms;

use rosasurfer\core\exception\UnimplementedFeatureException;
use rosasurfer\net\messenger\Messenger;


/**
 * NexmoMessenger
 *
 * A {@link rosasurfer\net\messenger\Messenger} sending text messages via Nexmo.
 *
 * @see  https://docs.nexmo.com/messaging/sms-api
 */
class NexmoMessenger extends Messenger {


    /**
     * {@inheritdoc}
     *
     * @param  scalar[] $options
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
        // https://rest.nexmo.com/sms/json?api_key={api_key}&api_secret={api_secret}&to={receiver}&from={sender}&text={message};
    }
}
