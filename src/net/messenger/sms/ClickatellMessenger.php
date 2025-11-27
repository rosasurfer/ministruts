<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\messenger\sms;

use rosasurfer\ministruts\core\exception\UnimplementedFeatureException;
use rosasurfer\ministruts\net\messenger\Messenger;

/**
 * ClickatellMessenger
 *
 * A {@link rosasurfer\ministruts\net\messenger\Messenger} sending text messages via Clickatell.
 *
 * @link https://www.clickatell.com/developers/
 */
class ClickatellMessenger extends Messenger {

    /**
     * {@inheritDoc}
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
    }
}
