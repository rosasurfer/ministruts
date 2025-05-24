<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\proxy;

use rosasurfer\ministruts\console\io\Output as ConsoleOutput;

/**
 * Output
 *
 * A {@link Proxy} for the "output" {@link \rosasurfer\ministruts\core\di\service\Service} currently registered in the service container.
 *
 * Default implementation: {@link \rosasurfer\ministruts\console\io\Output}
 *
 * @method static ConsoleOutput instance()            Get the object behind the proxy.
 * @method static void          out(mixed $message)   Write a message to STDOUT.
 * @method static void          error(mixed $message) Write a message to STDERR.
 */
class Output extends Proxy {

    /**
     * {@inheritDoc}
     */
    protected static function getServiceName(): string {
        return 'output';
        return ConsoleOutput::class;                // @phpstan-ignore deadCode.unreachable (keep for testing)
    }
}
