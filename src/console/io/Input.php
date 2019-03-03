<?php
namespace rosasurfer\console\io;

use rosasurfer\console\docopt\DocoptResult;
use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;
use function rosasurfer\first;


/**
 * Input
 */
class Input extends Object {


    /** @var DocoptResult */
    private $docoptResult;


    /**
     * Constructor
     *
     * @param  DocoptResult $docopt - a parsed and matched docopt result
     */
    public function __construct(DocoptResult $docopt) {
        $this->docoptResult = $docopt;
    }


    /**
     * Return the internal docopt result.
     *
     * @return DocoptResult
     */
    public function getDocoptResult() {
        return $this->docoptResult;
    }


    /**
     * Whether the command with the given name is defined (not whether the command was specified).
     * Valid commands consists of only lower-case letters. The same command may be defined multiple times.
     *
     * @param  string $name
     *
     * @return bool
     */
    public function isCommand($name) {
        if (!($len=strlen($name)) || !isset($this->docoptResult[$name]))
            return false;
        return (bool) preg_match('/^[a-z]+$/', $name);
    }


    /**
     * Whether the command with the given name was specified. See {@link Input::isCommand()} for the definition of "command".
     *
     * @param  string $name
     *
     * @return bool|int - boolean value or number of times the command was specified (if defined)
     */
    public function hasCommand($name) {
        if ($this->isCommand($name)) {
            $value = $this->docoptResult[$name];
            if (is_int($value) && $value > 1)
                return $value;
            return (bool) $value;
        }
        return false;
    }


    /**
     * Whether the argument with the given name is defined (not whether the argument was specified).
     * Arguments are command line parameters defined either in angular brackets or in all-uppercase letters.
     *
     * @param  string $name - argument name
     *
     * @return bool
     */
    public function isArgument($name) {
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.gettype($name));

        if (!($len=strlen($name)) || !isset($this->docoptResult[$name]))
            return false;

        $isBracketed = ('<'==$name[0] && $name[$len-1]=='>');
        $isUpperCase = ($name == strtoupper($name));

        return ($isBracketed || $isUpperCase);
    }


    /**
     * Return the single-value argument or the first multi-value argument with the given name.
     * See {@link Input::isArgument()} for the definition of "argument".
     *
     * @param  string $name
     *
     * @return string|null - argument value or NULL if the argument was not specified
     */
    public function getArgument($name) {
        if ($this->isArgument($name)) {
            $value = $this->docoptResult[$name];
            if (is_array($value))
                return first($value);
            return $value;
        }
        return null;
    }


    /**
     * Return the arguments with the given name. See {@link Input::isArgument()} for the definition of "argument".
     *
     * @param  string $name
     *
     * @return string[] - argument values or an empty array if the arguments were not specified
     */
    public function getArguments($name) {
        if ($this->isArgument($name)) {
            $value = $this->docoptResult[$name];
            if (is_array($value))
                return $value;
            return [$value];
        }
        return [];
    }


    /**
     * Whether the option with the given name is defined (not whether the option was specified).
     * Options are command line parameters defined with one leading dash (short options) or with two leading dashes (long
     * options). If an option is defined in both ways the parsed input values only reflect the long option.
     *
     * @param  string $name - option name with leading dash(es)
     *
     * @return bool
     */
    public function isOption($name) {
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.gettype($name));

        if (!strlen($name) || !isset($this->docoptResult[$name]) || $name[0]!='-' || $name=='-' || $name=='--')
            return false;
        return true;
    }


    /**
     * Return the single-value option or the first multi-value option with the given name. The returned value may be the
     * defined default value. See {@link Input::isOption()} for the definition of "option".
     *
     * @param  string $name
     *
     * @return bool|string - the option value or FALSE if the option was not specified
     */
    public function getOption($name) {
        if ($this->isOption($name)) {
            $value = $this->docoptResult[$name];
            if (!is_array($value))
                return $value;
            $value = first($value);
            if (isset($value))
                return $value;
        }
        return false;
    }


    /**
     * Return the options with the given name. The returned values may be the defined default values.
     * See {@link Input::isOption()} for the definition of "option".
     *
     * @param  string $name
     *
     * @return string[] - the option values or an empty array if the options were not specified
     */
    public function getOptions($name) {
        if ($this->isOption($name)) {
            $value = $this->docoptResult[$name];
            if (is_array($value))
                return $value;
            if ($value !== false)
                return [$value];
        }
        return [];
    }
}
