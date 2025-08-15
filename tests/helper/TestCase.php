<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\tests\helper;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 *
 */
class TestCase extends PHPUnitTestCase {

    /**
     * Asserts that two variables are equal. Adds optional verbose output of the diff.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message [optional]
     *
     * @return void
     */
    public static function assertEquals($expected, $actual, string $message = ''): void {
        try {
            parent::assertEquals($expected, $actual, $message);
        }
        catch (ExpectationFailedException $ex) {
            if (!self::isFullDiffMode()) {
                throw $ex;
            }
            $message = sprintf(
                "Failed asserting that two variables are equal.\n\nExpected:\n%s\n\nActual:\n%s",
                var_export($expected, true),
                var_export($actual, true),
            );
            parent::fail($message);
        }
    }


    /**
     * Asserts that two variables are equal (canonicalizing). Adds optional verbose output of the diff.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message [optional]
     *
     * @return void
     */
    public static function assertEqualsCanonicalizing($expected, $actual, string $message = ''): void {
        try {
            parent::assertEqualsCanonicalizing($expected, $actual, $message);
        }
        catch (ExpectationFailedException $ex) {
            if (!self::isFullDiffMode()) {
                throw $ex;
            }
            $message = sprintf(
                "Failed asserting that two variables are equal (canonicalizing).\n\nExpected:\n%s\n\nActual:\n%s",
                var_export($expected, true),
                var_export($actual, true),
            );
            parent::fail($message);
        }
    }


    /**
     * Asserts that two variables are identical. Adds optional verbose output of the diff.
     *
     * @param mixed  $expected
     * @param mixed  $actual
     * @param string $message [optional]
     *
     * @return void
     */
    public static function assertSame($expected, $actual, string $message = ''): void {
        try {
            parent::assertSame($expected, $actual, $message);
        }
        catch (ExpectationFailedException $ex) {
            if (!self::isFullDiffMode()) {
                throw $ex;
            }
            $message = sprintf(
                "Failed asserting that two variables are identical.\n\nExpected:\n%s\n\nActual:\n%s",
                var_export($expected, true),
                var_export($actual, true),
            );
            parent::fail($message);
        }
    }


    /**
     * Whether "full-diff" mode is enabled. It's enabled if the environment variable PHPUNIT_FULL_DIFF
     * is set and holds any non-empty string except "0".
     *
     * @return bool
     */
    protected static function isFullDiffMode(): bool {
        static $fullDiff;
        $fullDiff ??= !empty(getenv('PHPUNIT_FULL_DIFF'));
        return $fullDiff;
    }
}
