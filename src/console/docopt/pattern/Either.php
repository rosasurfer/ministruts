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
     * @return mixed[]
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
            $min = $ret = null;
            foreach ($outcomes as $o) {
                $cnt = count($o[1]);
                if ($min === null || $cnt < $min) {
                   $min = $cnt;
                   $ret = $o;
                }
            }
            return $ret;
        }
        return [false, $left, $collected];
    }
}
