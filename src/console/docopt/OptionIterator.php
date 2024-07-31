<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt;

use rosasurfer\ministruts\core\ObjectTrait;


/**
 * OptionIterator
 *
 * An empty ArrayIterator without further functionality saves us from IDE incompatibilities with non-standard PHPStan annotations.
 *
 * @extends \ArrayIterator<int, \rosasurfer\ministruts\console\docopt\pattern\Option>
 */
class OptionIterator extends \ArrayIterator {

    use ObjectTrait;
}
