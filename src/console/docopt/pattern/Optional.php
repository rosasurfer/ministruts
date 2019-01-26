<?php
namespace rosasurfer\console\docopt\pattern;


/**
 *
 */
class Optional extends BranchPattern {


    /**
     * @param  Pattern[] $left
     * @param  Pattern[] $collected [optional]
     *
     * @return mixed[]
     */
    public function match(array $left, array $collected = []) {
        foreach ($this->children as $pattern) {
            list($m, $left, $collected) = $pattern->match($left, $collected);
        }
        return [true, $left, $collected];
    }
}
