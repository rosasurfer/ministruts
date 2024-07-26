<?php
namespace rosasurfer\ministruts\console\docopt;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\console\docopt\pattern\Pattern;


/**
 * SingleMatch
 */
class SingleMatch extends CObject {


    /** @var int */
    public $pos;

    /** @var Pattern */
    public $pattern;


    /**
     * @param  ?int    $pos
     * @param  Pattern $pattern [optional]
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
