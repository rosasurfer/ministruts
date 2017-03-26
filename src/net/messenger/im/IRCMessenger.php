<?php
namespace rosasurfer\net\messenger\im;

use rosasurfer\exception\UnimplementedFeatureException;
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
        parent::__construct();
        $this->options = $options;
    }


    /**
     * Verschickt eine Nachricht.
     *
     * @param  string $channel - IRC-Channel
     * @param  string $message - Nachricht
     */
    public function sendMessage($receiver, $message) {
        throw new UnimplementedFeatureException(__METHOD__.'() not yet implemented');
    }
}
