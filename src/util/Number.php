<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\util;

use rosasurfer\ministruts\core\StaticClass;


/**
 * Number
 */
class Number extends StaticClass {

    /**
     * Format a numeric value with a currency format.
     *
     * Unlike the PHP built-in function number_format() this function can be called with any number of optional arguments.
     *
     * @param  int|float $value                         - numeric value
     * @param  int       $decimals           [optional] - number of decimals  (default: 2)
     * @param  string    $decimalsSeparator  [optional] - decimals separator  (default: ".")
     * @param  string    $thousandsSeparator [optional] - thousands separator (default: "")
     *
     * @return string
     */
    public static function formatMoney($value, int $decimals=2, string $decimalsSeparator='.', string $thousandsSeparator=''): string {
        return number_format($value, $decimals, $decimalsSeparator, $thousandsSeparator);
    }
}
