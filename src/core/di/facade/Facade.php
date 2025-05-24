<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\facade;

use rosasurfer\ministruts\core\StaticClass;

/**
 * A {@link Facade} simplifies access to an underlying API (it modifies the API).
 *
 * In MiniStruts it translates static method calls from one API to another.
 */
abstract class Facade extends StaticClass {
}
