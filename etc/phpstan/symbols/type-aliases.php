<?php
declare(strict_types=1);

/**
 * IDE support for PHPStan type aliases. The types are used in PHPdoc only, PHP never sees them.
 * Add this file to the library path of the project.
 *
 * @phpstan-import-type  STACKFRAME from \rosasurfer\ministruts\Application
 */
namespace rosasurfer\ministruts {

    /**
     * Alias for an array holding a single frame of a stacktrace.
     *
     * <pre>
     * array(
     *   'file'     => (string),
     *   'line'     => (int),
     *   'class'    => (string),
     *   'type'     => '->'|'::',
     *   'function' => (string),
     *   'object'   => (object),
     *   'args'     => mixed[],
     * )
     * </pre>
     */
    class STACKFRAME {}
}

namespace rosasurfer\ministruts\core\error {
    /** @see  \rosasurfer\ministruts\STACKFRAME */
    class STACKFRAME extends rosasurfer\ministruts\STACKFRAME {}
}

namespace rosasurfer\ministruts\log {
    /** @see  \rosasurfer\ministruts\STACKFRAME */
    class STACKFRAME extends rosasurfer\ministruts\STACKFRAME {}
}
