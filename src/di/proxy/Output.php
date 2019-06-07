<?php
namespace rosasurfer\di\proxy;


/**
 * Output
 *
 * @method static out(mixed $message)   Write a message to STDOUT.
 * @method static error(mixed $message) Write a message to STDERR.
 */
class Output extends Proxy {


    /**
     * Get the identifier of the proxied instance.
     *
     * @return string
     */
    protected static function getProxiedIdentifier() {
        return \rosasurfer\console\io\Output::class;
    }
}
