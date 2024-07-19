<?php
namespace rosasurfer\console\io;

use rosasurfer\console\docopt\DocoptResult;
use rosasurfer\core\CObject;
use rosasurfer\core\assert\Assert;


/**
 * Input
 *
 * An object providing access to parsed command line arguments.
 */
class Input extends CObject {


    /** @var DocoptResult */
    private $docoptResult;


    /**
     * Set the internal Docopt result.
     *
     * @param  DocoptResult $docopt
     *
     * @return $this
     */
    public function setDocoptResult(DocoptResult $docopt) {
        $this->docoptResult = $docopt;
        return $this;
    }


    /**
     * Return the internal Docopt result.
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
        Assert::string($name);
        if (!$this->docoptResult)
            return false;

        if (!strlen($name) || !key_exists($name, $this->docoptResult->getArgs()))
            return false;
        return (bool) preg_match('/^[a-z]+$/', $name);
    }


    /**
     * Whether the command with the given name was specified.
     * See {@link Input::isCommand()} for the definition of "command".
     *
     * @param  string $name
     *
     * @return bool|int - boolean value or number of times a repetitive command was specified
     */
    public function hasCommand($name) {
        Assert::string($name);
        if (!$this->docoptResult)
            return false;

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
     * Arguments are command line parameters defined either in angular brackets or in all-uppercase characters.
     *
     * @param  string $name - argument name
     *
     * @return bool
     */
    public function isArgument($name) {
        Assert::string($name);
        if (!$this->docoptResult)
            return false;

        if (!($len=strlen($name)) || !key_exists($name, $this->docoptResult->getArgs()))
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
     * @return ?string - argument value or NULL if the argument was not specified
     */
    public function getArgument($name) {
        Assert::string($name);
        if (!$this->docoptResult)
            return null;

        if ($this->isArgument($name)) {
            $value = $this->docoptResult[$name];
            if (is_array($value))
                return $value ? $value[0] : null;
            return $value;
        }
        return null;
    }


    /**
     * Return the arguments with the given name.
     * See {@link Input::isArgument()} for the definition of "argument".
     *
     * @param  string $name
     *
     * @return string[] - argument values or an empty array if the argument was not specified
     */
    public function getArguments($name) {
        Assert::string($name);
        if (!$this->docoptResult)
            return [];

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
     *
     * Options are command line parameters with one leading dash (short options) or with two leading dashes (long options).
     *
     * @param  string $name - long or short option name with leading dash(es)
     *
     * @return bool
     */
    public function isOption($name) {
        Assert::string($name);
        if (!$this->docoptResult)
            return false;

        if (!strlen($name) || !key_exists($name, $this->docoptResult->getArgs()) || $name[0]!='-' || $name=='-' || $name=='--')
            return false;
        return true;
    }


    /**
     * Return the value of the option with the given name.
     *
     * If the option is not repetitive and has no arguments a boolean value is returned. If the option is repetitive and has
     * no arguments an integer is returned indicating the number of times the option was specified. If the option has
     * arguments the first argument is returned. The returned value may be the defined default value.
     * See {@link Input::isOption()} for the definition of "option".
     *
     * @param  string $name
     *
     * @return bool|int|string - option value or FALSE if the option was not specified
     */
    public function getOption($name) {
        Assert::string($name);
        if (!$this->docoptResult)
            return false;

        if ($this->isOption($name)) {
            $value = $this->docoptResult[$name];
            if (is_array($value)) return $value ? $value[0] : false;    // repetitive option with arguments
          //if (is_int($value))   return $value;                        // repetitive option without arguments
          //if (is_bool($value))  return $value;                        // non-repetitive option, no arguments
          //if (!is_null($value)) return $value;                        // non-repetitive option with argument
          //return false;                                               // non-repetitive option not specified
            if (!is_null($value)) return $value;
        }
        return false;                                                   // undefined option
    }


    /**
     * Return the values of the options with the given name. The returned values may be the defined default values.
     * See {@link Input::isOption()} for the definition of "option".
     *
     * @param  string $name
     *
     * @return string[] - option values or an empty array if the option was not specified
     */
    public function getOptions($name) {
        Assert::string($name);
        if (!$this->docoptResult)
            return [];

        if ($this->isOption($name)) {
            $value = $this->docoptResult[$name];
            if (is_array($value)) return $value;                        // repetitive option with arguments
          //if (is_int($value))   return [$value];                      // repetitive option without arguments
          //if (is_bool($value))  return $value ? [$value] : [];        // non-repetitive option, no arguments
          //if (!is_null($value)) return [$value];                      // non-repetitive option with argument
          //return [];                                                  // non-repetitive option not specified
            if ($value!==false && $value!==null) return [$value];
        }
        return [];                                                      // undefined option
    }
}
