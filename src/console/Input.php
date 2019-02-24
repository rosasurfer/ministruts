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
     * Return the single-value argument with the given name.
     *
     * @param  string $name - either wrapped in angular brackets or all-uppercase name
     *
     * @return string|null - the argument value or NULL if the argument was not specified
     */
    public function getArgument($name) {
        if ($this->isArgument($name)) {
            $value = $this->docoptResult[$name];
            if (!is_array($value))
                return $value;
        }
        return null;
    }


    /**
     * Return the multi-value argument with the given name.
     *
     * @param  string $name - either wrapped in angular brackets or all-uppercase name
     *
     * @return string[]|null - the argument values or NULL if the arguments were not specified
     */
    public function getArguments($name) {
        if ($this->isArgument($name)) {
            $value = $this->docoptResult[$name];
            if (is_array($value))
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
     * Return the single-value option with the given name. The value may be the defined default value.
     *
     * @param  string $name - long (if defined) or short option name including leading dashes
     *
     * @return bool|string - the option value or FALSE if the option was not specified
     */
    public function getOption($name) {
        if ($this->isOption($name)) {
            $value = $this->docoptResult[$name];
            if (!is_array($value))
                return $value;
        }
        return false;
    }


    /**
     * Return the multi-value option with the given name. The values may be the defined default values.
     *
     * @param  string $name - long (if defined) or short option name including leading dashes
     *
     * @return bool|string[] - the option value or FALSE if the options were not specified
     */
    public function getOptions($name) {
        if ($this->isOption($name)) {
            $value = $this->docoptResult[$name];
            if (is_array($value))
                return $value;
        }
        return false;
    }
}
