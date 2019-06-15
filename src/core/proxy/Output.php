<?php
namespace rosasurfer\core\proxy;


/**
 * Output
 *
 * Proxy for the "output" implementation currently registered in the service container.
 *
 * Default implementation: {@link \rosasurfer\core\io\Output}
 *
 * @method static \rosasurfer\core\io\Output instance()            Get the object behind the proxy.
 * @method static                            out(mixed $message)   Write a message to STDOUT.
 * @method static                            error(mixed $message) Write a message to STDERR.
 */
class Output extends Proxy {


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected static function getServiceName() {
        return 'output';
        return \rosasurfer\core\io\Output::class;
    }
}
