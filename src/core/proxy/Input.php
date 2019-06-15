<?php
namespace rosasurfer\core\proxy;

use rosasurfer\console\docopt\DocoptResult;
use rosasurfer\core\di\service\Service;


/**
 * Input
 *
 * A {@link Proxy} for the CLI "input" {@link Service} currently registered in the service container.
 *
 * Default implementation: {@link \rosasurfer\core\io\CliInput}
 *
 * @method static \rosasurfer\core\io\CliInput            instance()                            Get the object behind the proxy.
 * @method static \rosasurfer\core\io\CliInput            setDocoptResult(DocoptResult $docopt) Set the internal docopt result.
 * @method static \rosasurfer\console\docopt\DocoptResult getDocoptResult()                     Return the internal docopt result.
 * @method static bool                                    isCommand(string $name)               Whether the command with the given name is defined (not whether the command was specified).<br><br> Valid commands consists of only lower-case letters. The same command may be defined multiple times.
 * @method static bool|int                                hasCommand(string $name)              Whether the command with the given name was specified.<br><br> See {@link CliInput::isCommand()} for the definition of "command".
 * @method static bool                                    isArgument(string $name)              Whether the argument with the given name is defined (not whether the argument was specified).<br><br> Arguments are command line parameters defined either in angular brackets or in all-uppercase characters.
 * @method static string|null                             getArgument(string $name)             Return the single-value argument or the first multi-value argument with the given name.<br> See {@link CliInput::isArgument()} for the definition of "argument".
 * @method static string[]                                getArguments(string $name)            Return the arguments with the given name.<br><br> See {@link CliInput::isArgument()} for the definition of "argument".
 * @method static bool                                    isOption(string $name)                Whether the option with the given name is defined (not whether the option was specified).<br><br> Options are command line parameters with one leading dash (short options) or with two leading dashes (long options).
 * @method static bool|int|string                         getOption(string $name)               Return the value of the option with the given name.<br><br> If the option is not repetitive and has no arguments a boolean value is returned. If the option is repetitive and has no arguments an integer indicating the number of times the option was specified is returned. If the option has arguments the first argument is returned. The returned value may be the defined default value.<br> See {@link CliInput::isOption()} for the definition of "option".
 * @method static string[]                                getOptions(string $name)              Return the values of the options with the given name. The returned values may be the defined default values.<br><br> See {@link CliInput::isOption()} for the definition of "option".
 */
class Input extends Proxy {


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected static function getServiceName() {
        return 'input';
        return \rosasurfer\core\io\CliInput::class;
    }
}
