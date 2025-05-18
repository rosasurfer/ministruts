<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\tests\docopt;

use rosasurfer\ministruts\console\docopt\pattern\Either;
use rosasurfer\ministruts\console\docopt\pattern\Pattern;


/**
 *
 */
abstract class TestPattern extends Pattern {

    /**
     * {@inheritDoc}
     *
     * Public wrapper for the protected method {@link Pattern::transform()}.
     */
    public static function transform(Pattern $pattern): Either {
        return parent::transform($pattern);
    }
}
