<?php
namespace rosasurfer\console\docopt\pattern;


/**
 * Either
 */
class Either extends BranchPattern {


    /**
     * @param  Pattern[] $left
     * @param  Pattern[] $collected [optional]
     *
     * @return array
     */
    public function match(array $left, array $collected = []) {
        $outcomes = [];
        foreach ($this->children as $pattern) {
            list ($matched, $dump1, $dump2) = $outcome = $pattern->match($left, $collected);
            if ($matched) {
                $outcomes[] = $outcome;
            }
        }
        if ($outcomes) {
            // return min(outcomes, key=lambda outcome: len(outcome[1]))
            $min = $result = null;
            foreach ($outcomes as $o) {
                $size = sizeof($o[1]);
                if ($min === null || $size < $min) {
                   $min    = $size;
                   $result = $o;
                }
            }
            return $result;
        }
        return [false, $left, $collected];
    }
}
