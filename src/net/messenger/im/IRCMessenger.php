<?php
namespace rosasurfer\net\messenger\im;

use rosasurfer\core\exception\UnimplementedFeatureException;
use rosasurfer\net\messenger\Messenger;


/**
 * Messenger, der eine Nachricht an einen IRC-Channel schickt.
 */
class IRCMessenger extends Messenger {


    /** @var array */
    protected $options;


    /**
     * Constructor
     *
     * @param  array $options - Messenger-Optionen
     */
    protected function __construct(array $options) {
        $this->options = $options;
    }


    /**
     * Verschickt eine Nachricht.
     *
     * @param  string $channel - IRC-Channel
     * @param  string $message - Nachricht
     */
    public function sendMessage($channel, $message) {
        throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
    }
}
