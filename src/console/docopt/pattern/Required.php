<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;


/**
 * Required
 */
class Required extends BranchPattern {


    /**
     * {@inheritdoc}
     *
     * @param  Pattern[] $left
     * @param  Pattern[] $collected [optional]
     *
     * @return array{bool, Pattern[], Pattern[]}
     */
    public function match(array $left, array $collected = []) {
        $l = $left;
        $c = $collected;

        foreach ($this->children as $pattern) {
            list ($matched, $l, $c) = $pattern->match($l, $c);
            if (!$matched) {
                return [false, $left, $collected];
            }
        }
        return [true, $l, $c];
    }
}
