<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;
use rosasurfer\core\exception\IllegalTypeException;


/**
 * Number
 */
class Number extends StaticClass {


    /**
     * Format a numeric value with a currency format&#46;  Opposite to the PHP built-in function number_format() this
     * function can be called with any number of optional arguments.
     *
     * @param  int|float $value                         - numeric value
     * @param  int       $decimals           [optional] - number of decimals  (default:   2)
     * @param  string    $decimalsSeparator  [optional] - decimals separator  (default: ".")
     * @param  string    $thousandsSeparator [optional] - thousands separator (default:  "")
     *
     * @return string
     */
    public static function formatMoney($value, $decimals=2, $decimalsSeparator='.', $thousandsSeparator='') {
        if (!is_int($value) && !is_float($value))
            throw new IllegalTypeException('Illegal type of parameter $value: '.gettype($value));
        Assert::int   ($decimals,           '$decimals');
        Assert::string($decimalsSeparator,  '$decimalsSeparator');
        Assert::string($thousandsSeparator, '$thousandsSeparator');

        return number_format($value, $decimals, $decimalsSeparator, $thousandsSeparator);
    }
}
