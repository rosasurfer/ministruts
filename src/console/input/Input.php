<?php
namespace rosasurfer\console\input;

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
     * Whether the argument with the given name has been specified by the user.
     *
     * @param  string $name - argument name
     *
     * @return bool
     */
    public function hasArgument($name) {
        if (!isset($this->docoptResult[$name]))
            return false;
        $value = $this->docoptResult[$name];
        return (!is_array($value) || $value);
    }


    /**
     * Return the value of the argument with the given name.
     *
     * @param  string $name - argument name
     *
     * @return string|string[] - a single or multiple argument values
     *
     * @throws NotFoundException if the argument was not specified by the user
     */
    public function getArgument($name) {
        if ($this->hasArgument($name))
            return $this->docoptResult[$name];
        throw new NotFoundException('Argument "'.$name.'" not found.');
    }


    /**
     * Whether the option with the given name is available in the input. An option may have a default value.
     *
     * @param  string $name - long name or short name if the option has no long name
     *                        (the option name must include the leading dashes "-" or "--")
     * @return bool
     */
    public function hasOption($name) {
        if (!isset($this->docoptResult[$name]))
            return false;
        $value = $this->docoptResult[$name];
        if (is_array($value))
            return (bool)$value;                        // an empty array means the option is not available
        return $value !== false;                        // FALSE means the option was not set
    }


    /**
     * Return the value of the option with the given name. The value may be the defined default value.
     *
     * @param  string $name - long name or short name if the option has no long name
     *                        (the option name must include the leading dashes "-" or "--")
     *
     * @return bool|string|string[] - a single or multiple option values
     *
     * @throws NotFoundException if the option is not available in the input
     */
    public function getOption($name) {
        if ($this->hasOption($name))
            return $this->docoptResult[$name];
        throw new NotFoundException('Option "'.$name.'" not found.');
    }
}
