<?php
namespace rosasurfer\console;

use rosasurfer\console\docopt\DocoptResult;
use rosasurfer\core\Object;
use rosasurfer\exception\IllegalTypeException;


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
     * Whether the command with the given name is defined. Not whether the command was specified..
     *
     * @param  string $name - command name: all parameters not matching arguments or options are interpreted as commands
     *                        and/or subcommands
     * @return bool
     */
    public function isCommand($name, $count = 1) {
        // TODO: this implementation sucks
        if (isset($this->docoptResult[$name]) && strlen($name))
            return (!$this->isArgument($name) && !$this->isOption($name));
        return false;
    }


    /**
     * Whether the argument with the given name is defined. Not whether the argument was specified..
     *
     * @param  string $name - argument name: either all-uppercase or enclosed in angular brackets
     *
     * @return bool
     */
    public function isArgument($name) {
        if (!is_string($name)) throw new IllegalTypeException('Illegal type of parameter $name: '.gettype($name));

        if (!($len=strlen($name)) || !isset($this->docoptResult[$name]))
            return false;

        $bracketed = ('<'==$name[0] && $name[$len-1]=='>');
        $upperCase = ($name == strtoupper($name));

        return ($bracketed || $upperCase);
    }


    /**
     * Return the value of the argument with the given name.
     *
     * @param  string $name - argument name: either wrapped in angular brackets or all-uppercase
     *
     * @return string|string[]|null - the argument value(s) or NULL if the argument was not specified
     */
    public function getArgument($name) {
        if ($this->isArgument($name)) {
            $value = $this->docoptResult[$name];
            if (!is_array($value) || $value)
                return $value;
        }
        return null;
    }


    /**
     * Whether the option with the given name is defined. Not whether the option was specified..
     *
     * @param  string $name - option name: either a long "--" (precedence) or a short "-" option name (including dashes)
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
     * Return the value of the option with the given name. The option may be the defined due to a default value.
     *
     * @param  string $name - option name: either long "--" (precedence) or short "-" option name (including dashes)
     *
     * @return bool|string|string[] - a single or multiple option values
     */
    public function getOption($name) {
        if ($this->isOption($name)) {
            $value = $this->docoptResult[$name];
            if (!is_array($value) || $value)
                return $value;
        }
        return false;
    }
}
