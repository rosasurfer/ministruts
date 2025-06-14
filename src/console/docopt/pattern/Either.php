<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;

/**
 * Either
 */
class Either extends BranchPattern {

    /**
     * {@inheritDoc}
     */
    public function match(array $left, array $collected = []): array {
        $outcomes = [];
        foreach ($this->children as $pattern) {
            [$matched] = $outcome = $pattern->match($left, $collected);
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
                   $min = $size;
                   $result = $o;
                }
            }
            return $result;
        }
        return [false, $left, $collected];
    }
}
