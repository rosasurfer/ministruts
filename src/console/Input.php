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
        if ($this->isArgument($name))
            return $this->docoptResult[$name];
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
     * Return the value of the option with the given name. The value may be the defined default value.
     *
     * @param  string $name - option name: long (if defined) or short option name including leading dashes
     *
     * @return bool|string|string[] - a single or multiple option values
     */
    public function getOption($name) {
        if ($this->isOption($name))
            return $this->docoptResult[$name];
        return false;
    }
}
