<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\messenger\sms;

use rosasurfer\ministruts\core\exception\UnimplementedFeatureException;
use rosasurfer\ministruts\net\messenger\Messenger;


/**
 * NexmoMessenger
 *
 * A {@link rosasurfer\ministruts\net\messenger\Messenger} sending text messages via Nexmo.
 *
 * @see  https://docs.nexmo.com/messaging/sms-api
 */
class NexmoMessenger extends Messenger {


    /**
     * {@inheritdoc}
     *
     * @param  mixed[] $options
     */
    protected function __construct(array $options) {
        $this->options = $options;
    }


    /**
     * Send a text message.
     *
     * @param  string $receiver - phone number
     * @param  string $message  - message
     *
     * @return void
     */
    public function sendMessage(string $receiver, string $message): void {
        throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
        // https://rest.nexmo.com/sms/json?api_key={api_key}&api_secret={api_secret}&to={receiver}&from={sender}&text={message};
    }
}
