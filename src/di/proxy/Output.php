<?php
namespace rosasurfer\di\proxy;


/**
 * Output
 *
 * Proxy for the "output" implementation currently registered in the service container.
 *
 * Default implementations:                       <br>
 * {@link \rosasurfer\console\io\Output         } <br>
 * {@link \rosasurfer\console\io\Output::out()  } <br>
 * {@link \rosasurfer\console\io\Output::error()} <br>
 *
 *
 * @method static \rosasurfer\console\io\Output instance()            Get the object behind the proxy.
 * @method static                               out(mixed $message)   Write a message to STDOUT.
 * @method static                               error(mixed $message) Write a message to STDERR.
 */
class Output extends Proxy {


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected static function getServiceId() {
        return \rosasurfer\console\io\Output::class;
    }
}
