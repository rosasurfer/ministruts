<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\util;

use rosasurfer\ministruts\core\StaticClass;
use rosasurfer\ministruts\core\assert\Assert;
use rosasurfer\ministruts\core\exception\InvalidTypeException;


/**
 * Number
 */
class Number extends StaticClass {

    /**
     * Format a numeric value with a currency format.
     *
     * Opposite to the PHP built-in function number_format() this function can be called with
     * any number of optional arguments.
     *
     * @param  int|float $value                         - numeric value
     * @param  int       $decimals           [optional] - number of decimals  (default: 2)
     * @param  string    $decimalsSeparator  [optional] - decimals separator  (default: ".")
     * @param  string    $thousandsSeparator [optional] - thousands separator (default: "")
     *
     * @return string
     */
    public static function formatMoney($value, $decimals=2, $decimalsSeparator='.', $thousandsSeparator='') {
        if (!is_int($value) && !is_float($value)) throw new InvalidTypeException('Invalid type of parameter $value: '.gettype($value));     // @phpstan-ignore booleanAnd.alwaysFalse (types come from PHPDoc)
        Assert::int   ($decimals,           '$decimals');
        Assert::string($decimalsSeparator,  '$decimalsSeparator');
        Assert::string($thousandsSeparator, '$thousandsSeparator');

        return number_format($value, $decimals, $decimalsSeparator, $thousandsSeparator);
    }
}
