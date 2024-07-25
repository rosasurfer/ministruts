<?php
declare(strict_types=1);

namespace rosasurfer\test\docopt;

use rosasurfer\console\docopt\pattern\Either;
use rosasurfer\console\docopt\pattern\Pattern;


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
