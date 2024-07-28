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
     * {@inheritdoc}
     *
     * @param  array $options
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
    public function sendMessage($channel, $message): void {
        throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
    }
}
