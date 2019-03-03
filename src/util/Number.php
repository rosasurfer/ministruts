<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;
use rosasurfer\core\assert\Assert;


/**
 * Number
 */
class Number extends StaticClass {


    /**
     * Format a numeric value with a currency format. Opposite to the PHP built-in function number_format() this function
     * can be called with any number of optional arguments.
     *
     * @param  int|float $value                         - numeric value
     * @param  int       $decimals           [optional] - number of decimals  (default:   2)
     * @param  string    $decimalsSeparator  [optional] - decimals separator  (default: ".")
     * @param  string    $thousandsSeparator [optional] - thousands separator (default:  "")
     *
     * @return string
     */
    public static function formatMoney($value, $decimals=2, $decimalsSeparator='.', $thousandsSeparator='') {
        Assert::intOrFloat($value,              '$value');
        Assert::int       ($decimals,           '$decimals');
        Assert::string    ($decimalsSeparator,  '$decimalsSeparator');
        Assert::string    ($thousandsSeparator, '$thousandsSeparator');

        return number_format($value, $decimals, $decimalsSeparator, $thousandsSeparator);
    }
}
