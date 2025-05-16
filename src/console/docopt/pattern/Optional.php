<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;


/**
 * Optional
 */
class Optional extends BranchPattern {


    /**
     * {@inheritDoc}
     */
    public function match(array $left, array $collected = []): array {
        foreach ($this->children as $pattern) {
            list(, $left, $collected) = $pattern->match($left, $collected);
        }
        return [true, $left, $collected];
    }
}
