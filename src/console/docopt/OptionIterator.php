<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt;

use ArrayIterator;

use rosasurfer\ministruts\console\docopt\pattern\Option;
use rosasurfer\ministruts\core\ObjectTrait;

/**
 * OptionIterator
 *
 * An empty ArrayIterator without further functionality saves us from IDE incompatibilities with non-standard PHPStan annotations.
 *
 * @extends ArrayIterator<int, Option>
 */
class OptionIterator extends ArrayIterator {

    use ObjectTrait;
}
