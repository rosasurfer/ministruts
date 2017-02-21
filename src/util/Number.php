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
     * @param  int|float $value              - numeric value
     * @param  int       $decimals           - number of decimals  (default:   2)
     * @param  string    $decimalsSeparator  - decimals separator  (default: ".")
     * @param  string    $thousandsSeparator - thousands separator (default:  "")
     *
     * @return string
     */
    public static function formatMoney($value, $decimals=2, $decimalsSeparator='.', $thousandsSeparator='') {
        if (!is_int($value) && !is_float($value)) throw new IllegalTypeException('Illegal type of parameter $value: '.getType($value));
        if (!is_int($decimals))                   throw new IllegalTypeException('Illegal type of parameter $decimals: '.getType($decimals));
        if (!is_string($decimalsSeparator))       throw new IllegalTypeException('Illegal type of parameter $decimalsSeparator: '.getType($decimalsSeparator));
        if (!is_string($thousandsSeparator))      throw new IllegalTypeException('Illegal type of parameter $thousandsSeparator: '.getType($thousandsSeparator));

        return number_format($value, $decimals, $decimalsSeparator, $thousandsSeparator);
    }
}
