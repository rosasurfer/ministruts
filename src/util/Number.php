<?php
namespace rosasurfer\util;

use rosasurfer\core\StaticClass;
use rosasurfer\exception\IllegalTypeException;


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
        if (!is_int($value) && !is_float($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.gettype($value));
        if (!is_int($decimals))                   throw new IllegalTypeException('Illegal type of parameter $decimals: '.gettype($decimals));
        if (!is_string($decimalsSeparator))       throw new IllegalTypeException('Illegal type of parameter $decimalsSeparator: '.gettype($decimalsSeparator));
        if (!is_string($thousandsSeparator))      throw new IllegalTypeException('Illegal type of parameter $thousandsSeparator: '.gettype($thousandsSeparator));

        return number_format($value, $decimals, $decimalsSeparator, $thousandsSeparator);
    }
}
