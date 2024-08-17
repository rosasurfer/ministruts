<?php
namespace rosasurfer\net\messenger\im;

use rosasurfer\core\exception\UnimplementedFeatureException;
use rosasurfer\net\messenger\Messenger;


/**
 * IRCMessenger
 *
 * A {@link rosasurfer\net\messenger\Messenger} for sending messages to an IRC channel.
 */
class IRCMessenger extends Messenger {


    /**
     * {@inheritdoc}
     *
     * @param  scalar[] $options
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
    public function sendMessage($channel, $message) {
        throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
    }
}
