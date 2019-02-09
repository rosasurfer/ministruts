<?php
namespace rosasurfer\console\docopt\pattern;

use rosasurfer\console\docopt\SingleMatch;


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
        foreach ($left as $n => $pattern) {
            if ($pattern instanceof Argument) {
                if ($pattern->value == $this->name()) {
                    return new SingleMatch($n, new Command($this->name(), true));
                }
                else {
                    break;
                }
            }
        }
        return new SingleMatch(null, null);
    }
}
