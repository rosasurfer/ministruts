<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\log\filter;

/**
 * An interface for content filters.
 */
interface ContentFilterInterface {

    /** replacement value for sensitive strings */
    public const SUBSTITUTE = '******';

    /**
     * Filter a plain string.
     *
     * @param  string $input - filter input
     *
     * @return string - filtered string
     */
    public function filterString(string $input): string;


    /**
     * Filter a single named value.
     *
     * @param  string $name
     * @param  mixed  $value
     *
     * @return mixed - filtered value
     */
    public function filterValue(string $name, $value);


    /**
     * Filter an array of named values.
     *
     * @param  mixed[]     $values          - input values
     * @param  array-key[] $skip [optional] - elements to skip (default: none)
     *
     * @return mixed[] - filtered values
     */
    public function filterValues(array $values, array $skip = []): array;


    /**
     * Filter the string representation of an URI.
     *
     * @param  string $uri - filter input
     *
     * @return string - filtered URI
     */
    public function filterUri(string $uri): string;
}
