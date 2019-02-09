<?php
namespace rosasurfer\console\docopt;

use rosasurfer\console\docopt\exception\DocoptFormatError;
use rosasurfer\console\docopt\exception\UserSyntaxError;
use rosasurfer\core\ObjectTrait;


/**
 *
 */
class TokenStream extends \ArrayIterator {

    use ObjectTrait;


    /** @var string */
    public $errorClass;


    /**
     * @param  string|mixed[] $source
     * @param  string         $errorClass - class name of errors
     */
    public function __construct($source, $errorClass = UserSyntaxError::class) {
        if (!is_array($source)) {
            $source = trim($source);
            if ($source) $source = preg_split('/\s+/', $source);
            else         $source = [];
        }
        parent::__construct($source);

        $this->errorClass = $errorClass;
    }


    /**
     * @param  string $source
     *
     * @return self
     */
    public static function fromPattern($source) {
        $source = preg_replace('/([\[\]\(\)\|]|\.\.\.)/', ' $1 ', $source);
        $source = preg_split('/\s+|(\S*<.*?'.'>)/', $source, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        return new static($source, DocoptFormatError::class);
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
        while (($token = $this->move()) !== null) {
            $left[] = $token;
        }
        return $left;
    }


    /**
     * @param string $message
     */
    public function throwException($message) {
        $exception = $this->errorClass;
        throw new $exception($message);
    }
}
