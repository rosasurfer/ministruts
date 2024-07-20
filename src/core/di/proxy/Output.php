<?php
namespace rosasurfer\core\di\proxy;


/**
 * Output
 *
 * A {@link Proxy} for the "output" {@link \rosasurfer\core\di\service\Service} currently registered in the service container.
 *
 * Default implementation: {@link \rosasurfer\console\io\Output}
 *
 * @method static \rosasurfer\console\io\Output instance()            Get the object behind the proxy.
 * @method static                               out(mixed $message)   Write a message to STDOUT.
 * @method static                               error(mixed $message) Write a message to STDERR.
 */
class Output extends Proxy {


    /**
     * @return string
     */
    protected static function getServiceName() {
        return 'output';
        return \rosasurfer\console\io\Output::class;
    }
}
