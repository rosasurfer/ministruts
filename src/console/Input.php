<?php
namespace rosasurfer\console;

use rosasurfer\console\docopt\DocoptResult;
use rosasurfer\core\Object;
use rosasurfer\exception\NotFoundException;


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
     * Whether the command with the given name was specified.
     *
     * @param  string $name - command name: all command line parameters which don't match the definition of arguments or
     *                        options are interpreted as (possibly sub)commands
     * @return bool
     */
    public function hasCommand($name, $count = 1) {
        // TODO: this implementation sucks
        if (isset($this->docoptResult[$name]) && strlen($name))
            return (!$this->hasArgument($name) && !$this->hasOption($name));
        return false;
    }


    /**
     * Whether the argument with the given name was specified.
     *
     * @param  string $name - argument name: either wrapped in angular brackets or all-uppercase
     *
     * @return bool
     */
    public function hasArgument($name) {
        if (!isset($this->docoptResult[$name]) || !($len=strlen($name)))
            return false;

        $bracketed = ('<'==$name[0] && $name[$len-1]=='>');
        $upperCase = ($name === strtoupper($name));

        if ($bracketed || $upperCase) {
            $value = $this->docoptResult[$name];
            return (!is_array($value) || $value);
        }
        return false;
    }


    /**
     * Return the value of the argument with the given name.
     *
     * @param  string $name - argument name: either wrapped in angular brackets or all-uppercase
     *
     * @return string|string[] - a single or multiple argument values
     *
     * @throws NotFoundException if the argument was not specified
     */
    public function getArgument($name) {
        if ($this->hasArgument($name))
            return $this->docoptResult[$name];
        throw new NotFoundException('Argument "'.$name.'" not found.');
    }


    /**
     * Whether the option with the given name is available as input. Options may be available due to default values.
     *
     * @param  string $name - option name: either long "--" (precedence) or short "-" option name (including dashes)
     *
     * @return bool
     */
    public function hasOption($name) {
        if (!isset($this->docoptResult[$name]) || !strlen($name) || $name[0]!='-' || $name=='-' || $name=='--')
            return false;
        $value = $this->docoptResult[$name];
        if (is_array($value))
            return (bool) $value;                       // an empty array means a repeatable option is not available
        return $value !== false;                        // FALSE means the option was not set
    }


    /**
     * Return the value of the option with the given name. The option may be the defined due to a default value.
     *
     * @param  string $name - option name: either long "--" (precedence) or short "-" option name (including dashes)
     *
     * @return bool|string|string[] - a single or multiple option values
     *
     * @throws NotFoundException if the option is not available as input
     */
    public function getOption($name) {
        if ($this->hasOption($name))
            return $this->docoptResult[$name];
        throw new NotFoundException('Option "'.$name.'" not found.');
    }
}
