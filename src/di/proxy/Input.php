<?php
namespace rosasurfer\di\proxy;

use rosasurfer\console\docopt\DocoptResult;


/**
 * Input
 *
 * @method static $this           setDocoptResult(DocoptResult $docopt) Set the internal docopt result.
 * @method static DocoptResult    getDocoptResult()                     Return the internal docopt result.
 * @method static bool            isCommand(string $name)               Whether the command with the given name is defined (not whether the command was specified). Valid commands consists of only lower-case letters. The same command may be defined multiple times.
 * @method static bool|int        hasCommand(string $name)              Whether the command with the given name was specified. See {@link Input::isCommand()} for the definition of "command".
 * @method static bool            isArgument(string $name)              Whether the argument with the given name is defined (not whether the argument was specified). Arguments are command line parameters defined either in angular brackets or in all-uppercase characters.
 * @method static string|null     getArgument(string $name)             Return the single-value argument or the first multi-value argument with the given name. See {@link Input::isArgument()} for the definition of "argument".
 * @method static string[]        getArguments(string $name)            Return the arguments with the given name. See {@link Input::isArgument()} for the definition of "argument".
 * @method static bool            isOption(string $name)                Whether the option with the given name is defined (not whether the option was specified). Options are command line parameters defined with one leading dash (short options) or with two leading dashes (long options).
 * @method static bool|int|string getOption(string $name)               Return the value of the option with the given name. If the option is not repetitive and has no arguments a boolean value is returned. If the option is repetitive and has no arguments an integer is returned indicating the number of times the option was specified. If the option has arguments the first argument is returned. The returned value may be the defined default value. See {@link Input::isOption()} for the definition of "option".
 * @method static string[]        getOptions(string $name)              Return the values of the options with the given name. The returned values may be the defined default values. See {@link Input::isOption()} for the definition of "option".
 */
class Input extends Proxy {


    /**
     * Get the identifier of the proxied instance.
     *
     * @return string
     */
    protected static function getProxiedIdentifier() {
        return \rosasurfer\console\io\Input::class;
    }
}
