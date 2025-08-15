<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\tests\helper;

use PHPUnit\Framework\TestResult;
use PHPUnit\Util\TestDox\CliTestDoxPrinter as PHPUnitPrinter;

/**
 * A TestDox printer which always duplication of failed test output.
 * The original PHPUnit printer duplicates failure output if 70% or less of the tests fail.
 */
class TestDoxPrinter extends PHPUnitPrinter
{
    /**
     * {@inheritDoc}
     */
    public function printResult(TestResult $result): void
    {
        $this->printHeader($result);
        //$this->printNonSuccessfulTestsSummary($result->count());      // skip
        $this->printFooter($result);
    }
}
