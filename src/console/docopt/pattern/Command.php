<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;

use rosasurfer\ministruts\console\docopt\SingleMatch;


/**
 * Command
 */
class Command extends Argument {


    /**
     * @param  Pattern[] $left
     *
     * @return SingleMatch
     */
    public function singleMatch(array $left) {
        foreach ($left as $i => $pattern) {
            if ($pattern instanceof Argument) {
                if ($pattern->value == $this->name()) {
                    return new SingleMatch($i, new Command($this->name(), true));
                }
                else {
                    break;
                }
            }
        }
        return new SingleMatch(null, null);
    }
}
