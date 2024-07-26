<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\test\docopt;

use rosasurfer\ministruts\console\docopt\pattern\Either;
use rosasurfer\ministruts\console\docopt\pattern\Pattern;


/**
 *
 */
abstract class TestPattern extends Pattern {

    /**
     * Public wrapper for the protected method {@link Pattern::transform()}.
     *
     * @param  Pattern $pattern
     *
     * @return Either
     */
    public static function transform(Pattern $pattern) {
        return parent::transform($pattern);
    }
}
