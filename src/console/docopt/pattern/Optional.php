<?php
namespace rosasurfer\ministruts\console\docopt\pattern;


/**
 * Optional
 */
class Optional extends BranchPattern {


    /**
     * @param  Pattern[] $left
     * @param  Pattern[] $collected [optional]
     *
     * @return array
     */
    public function match(array $left, array $collected = []) {
        foreach ($this->children as $pattern) {
            list(, $left, $collected) = $pattern->match($left, $collected);
        }
        return [true, $left, $collected];
    }
}
