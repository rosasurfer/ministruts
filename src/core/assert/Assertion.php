<?php
namespace rosasurfer\core\assert;

use rosasurfer\core\CObject;
use rosasurfer\core\assert\FailedAssertionExceptionInterface as IFailedAssertionException;

use const rosasurfer\NL;


/**
 * Assertion
 *
 * Chainable assertions to validate arguments. The full assertion chain is checked only once on call of Assertion::assert().
 *
 * @method static Assertion int(  $value, $message=null, ...$args=null) Ensure that the passed value is an integer.
 * @method static Assertion float($value, $message=null, ...$args=null) Ensure that the passed value is a float.
 *
 * @method        Assertion and() Combine two assertions with a logical AND, e.g. <tt>Assertion::a()->and()->b()</tt>.<br> AND is the default and essentially the same as <tt>Assertion::a()->b()</tt>
 * @method        Assertion or()  Combine two assertions with a logical OR, e.g. <tt>Assertion::a()->or()->b()</tt>.
 */
class Assertion extends CObject {

    use FailedAssertionTrait;


    /** @var array - chained assertions */
    protected $assertions = [];


    /**
     * Create a new instance and store the specified assertion as the first in a new assertion chain.
     *
     * @param  string $method - assertion method
     * @param  array  $args   - assertion arguments
     */
    protected function __construct($method, array $args) {
        $this->assertions[$method] = $args;
    }


    /**
     * Process the stored chain and check the full assertion. If a logical test is performed the method returns an array of
     * assertion errors. Otherwise the method throws a {@link IFailedAssertionException} if the assertion doesn't hold true.
     *
     * @param  bool $logical [optional] - whether to return a logical test result or to throw an assertion exception
     *
     * @return string[]
     *
     * @throws IFailedAssertionException
     */
    public function assert($logical = false) {
        $errors = [];
        foreach ($this->assertions as $assert => $args) {
            $method = '_'.$assert;
            if ($error = $this->$method(...$args)) {
                $errors = array_merge($errors, $error);
                break;
            }
        }
        if ($errors && !$logical)
            throw new InvalidArgumentException(join(NL, $errors));
        return $errors;
    }


    /**
     * Ensure that the passed value is an integer.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return string[] - array of assertion errors or an empty array if the assertion holds TRUE
     */
    protected function _int($value, $message = null, ...$args) {
        if (!is_int($value))
            return [static::illegalTypeMessage($value, 'int', $message, $args)];
        return [];
    }


    /**
     * Ensure that the passed value is a float.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     *
     * @return string[] - array of assertion errors or an empty array if the assertion holds TRUE
     */
    protected function _float($value, $message = null, ...$args) {
        if (!is_float($value))
            return [static::illegalTypeMessage($value, 'float', $message, $args)];
        return [];
    }


    /**
     * Handle calls of undefined or inaccessible instance methods.
     *
     * @param  string $method - method name
     * @param  array  $args   - arguments passed to the method call
     *
     * @return $this
     */
    public function __call($method, array $args) {
        $method = strtolower($method);

        switch ($method) {
            case 'and': return $this;
            case 'or':  return $this;
            default:
                if (method_exists($this, '_'.$method)) {
                    $this->assertions[$method] = $args;
                    return $this;
                }
        }
        parent::__call($method, $args);
    }

    /**
     * Handle calls of undefined or inaccessible static methods.
     *
     * @param  string $method - method name
     * @param  array  $args   - arguments passed to the method call
     *
     * @return static
     */
    public static function __callStatic($method, array $args) {
        if (method_exists(static::class, '_'.$method))
            return new static($method, $args);
        parent::__callStatic($method, $args);
    }
}
