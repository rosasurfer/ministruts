<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt;

use rosasurfer\ministruts\core\CObject;
use rosasurfer\ministruts\console\docopt\pattern\Pattern;


/**
 * SingleMatch
 */
class SingleMatch extends CObject {


    /** @var ?int */
    public $pos = null;

    /** @var ?Pattern */
    public $pattern = null;


    /**
     * @param  ?int     $pos
     * @param  ?Pattern $pattern
     */
    public function __construct(?int $pos, ?Pattern $pattern) {
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
