<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt;

use rosasurfer\ministruts\console\docopt\exception\DocoptFormatError;
use rosasurfer\ministruts\console\docopt\exception\DocoptUserNotification;
use rosasurfer\ministruts\core\ObjectTrait;
use rosasurfer\ministruts\core\di\DiAwareTrait;


/**
 * TokenIterator
 *
 * @extends \ArrayIterator<int, string>
 */
class TokenIterator extends \ArrayIterator {

    use ObjectTrait, DiAwareTrait;


    /** @var string */
    protected $tokenError;


    /**
     * Constructor
     *
     * @param  string|string[] $source
     * @param  string          $tokenError [optional] - classname of token errors used for error output
     *                                                  (default: "DocoptUserNotification")
     */
    public function __construct($source, $tokenError = DocoptUserNotification::class) {
        if (!is_array($source)) {
            $source = trim($source);
            $source = strlen($source) ? preg_split('/\s+/', $source) : [];
        }
        parent::__construct($source);

        $this->tokenError = $tokenError;
    }


    /**
     * @param  string $source
     *
     * @return TokenIterator
     */
    public static function fromPattern($source) {
        $source = preg_replace('/([\[\]\(\)\|]|\.\.\.)/', ' $1 ', $source);
        $source = preg_split('/\s+|(\S*<.*?'.'>)/', $source, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        return new static($source, DocoptFormatError::class);
    }


    /**
     * @return string - classname of token errors
     */
    public function getTokenError() {
        return $this->tokenError;
    }


    /**
     * @return string
     */
    public function move() {
        $item = $this->current();
        $this->next();
        return $item;
    }


    /**
     * @return string[]
     */
    public function left() {
        $left = [];
        while (($token=$this->move()) !== null) {
            $left[] = $token;
        }
        return $left;               // @phpstan-ignore deadCode.unreachable (FIXME: refactor using iterator->valid())
    }
}
