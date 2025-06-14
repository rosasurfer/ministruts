<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\net\messenger\im;

use rosasurfer\ministruts\core\exception\UnimplementedFeatureException;
use rosasurfer\ministruts\net\messenger\Messenger;

/**
 * IRCMessenger
 *
 * A {@link rosasurfer\ministruts\net\messenger\Messenger} for sending messages to an IRC channel.
 */
class IRCMessenger extends Messenger {

    /**
     * {@inheritDoc}
     *
     * @param  mixed[] $options
     */
    protected function __construct(array $options) {
        $this->options = $options;
    }


    /**
     * Send a message to an IRC channel.
     *
     * @param  string $channel - channel name
     * @param  string $message - message
     *
     * @return void
     */
    public function sendMessage(string $channel, string $message): void {
        throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
    }
}
