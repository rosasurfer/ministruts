<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\core\di\proxy;


/**
 * Output
 *
 * A {@link Proxy} for the "output" {@link \rosasurfer\ministruts\core\di\service\Service} currently registered in the service container.
 *
 * Default implementation: {@link \rosasurfer\ministruts\console\io\Output}
 *
 * @method static \rosasurfer\ministruts\console\io\Output instance()            Get the object behind the proxy.
 * @method static void                                     out(mixed $message)   Write a message to STDOUT.
 * @method static void                                     error(mixed $message) Write a message to STDERR.
 */
class Output extends Proxy {


    /**
     * @return string
     */
    protected static function getServiceName() {
        return 'output';
        return \rosasurfer\ministruts\console\io\Output::class;     // @phpstan-ignore deadCode.unreachable (keep for testing)
    }
}
