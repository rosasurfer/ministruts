<?php
namespace rosasurfer\console\docopt;

use rosasurfer\core\Object;
use rosasurfer\console\docopt\pattern\Pattern;


/**
 * SingleMatch
 */
class SingleMatch extends Object {


    /** @var int */
    public $pos;

    /** @var Pattern */
    public $pattern;


    /**
     * @param  int|null $pos
     * @param  Pattern  $pattern [optional]
     */
    public function __construct($pos, Pattern $pattern = null) {
        $this->pos = $pos;
        $this->pattern = $pattern;
    }


    /**
     * @return array
     */
    public function toArray() {
        return [$this->pos, $this->pattern];
    }
}
