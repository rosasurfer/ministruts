<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;


/**
 * Either
 */
class Either extends BranchPattern {


    /**
     * {@inheritdoc}
     *
     * @param  Pattern[] $left
     * @param  Pattern[] $collected [optional]
     *
     * @return array{bool, Pattern[], Pattern[]}
     */
    public function match(array $left, array $collected = []) {
        $outcomes = [];
        foreach ($this->children as $pattern) {
            list($matched) = $outcome = $pattern->match($left, $collected);
            if ($matched) $outcomes[] = $outcome;
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
