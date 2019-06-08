<?php
namespace rosasurfer\di\proxy;


/**
 * Output
 *
 * Proxy for the "output" implementation currently registered in the service container.
 *
 * Default implementation: {@link \rosasurfer\console\io\Output}  <br>
 * Default methods:                                               <br>
 * {@link \rosasurfer\console\io\Output::out()}                   <br>
 * {@link \rosasurfer\console\io\Output::error()}                 <br>
 *
 *
 * @method static out(mixed $message)   Write a message to STDOUT.
 * @method static error(mixed $message) Write a message to STDERR.
 */
class Output extends Proxy {


    /**
     * Return the service identifier of the proxied instance.
     *
     * @return string
     */
    protected static function getServiceId() {
        return \rosasurfer\console\io\Output::class;
    }
}
