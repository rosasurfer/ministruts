<?php
namespace rosasurfer\core\assert;

use rosasurfer\core\CObject;
use rosasurfer\core\assert\FailedAssertionExceptionInterface as IFailedAssertionException;

use function rosasurfer\echoPre;


/**
 * Assertion
 *
 * Chainable assertions to validate arguments. The full assertion chain is checked only on call of Assertion->assert().
 *
 * @method static Assertion int(  $value, $message=null, ...$args) Ensure that the passed value is an integer.
 * @method static Assertion float($value, $message=null, ...$args) Ensure that the passed value is a float.
 */
class Assertion extends CObject {


    /** @var array - chained assertions */
    protected $assertions = [];


    /**
     * Create a new instance and store the specified assertion as start of a new assertion chain.
     *
     * @param  string $method - assertion method
     * @param  array  $args   - assertion arguments
     */
    protected function __construct($method, array $args) {
        $this->assertions[$method] = $args;
    }


    /**
     * Check the chained assertions. If a logical test is performed the method returns a boolean result. Otherwise the
     * method throws a {@link IFailedAssertionException} if the assertion is not true.
     *
     * @param  bool $logical [optional] - whether to perform a logical test
     *
     * @return bool
     *
     * @throws IFailedAssertionException
     */
    public function assert($logical = false) {
        echoPre($this->assertions);
        foreach ($this->assertions as $method => $args) {
            $this->$method(...$args);
        }
        return false;
    }


    /**
     * Ensure that the passed value is an integer.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     */
    protected function int($value, $message = null, ...$args) {
        echoPre(__METHOD__.'()  $value: '.$value);
    }


    /**
     * Ensure that the passed value is a float.
     *
     * @param  mixed    $value
     * @param  string   $message [optional] - value identifier or description
     * @param  array ...$args    [optional] - additional description arguments
     */
    protected function float($value, $message = null, ...$args) {
        echoPre(__METHOD__.'()  $value: '.$value);
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
        if (method_exists($this, $method)) {
            $this->assertions[$method] = $args;
            return $this;
        }
        parent::__call($method, $args);
    }

    /**
     * Handle calls of undefined or inaccessible static methods.
     *
     * @param  string $method - method name
     * @param  array  $args   - arguments passed to the method call
     *
     * @return Assertion
     */
    public static function __callStatic($method, array $args) {
        if (method_exists(static::class, $method))
            return new static($method, $args);
        parent::__callStatic($method, $args);
    }
}
