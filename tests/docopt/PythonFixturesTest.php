<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\tests\docopt;

use Exception;
use PHPUnit\Framework\TestCase;

use function rosasurfer\ministruts\docopt;

use const rosasurfer\ministruts\NL;


/**
 * Unit tests using original Python fixtures
 */
class PythonFixturesTest extends TestCase {

    /**
     * @param  string $usage
     * @param  string $argv
     * @param  mixed  $expected
     *
     * @return void
     *
     * @dataProvider dataProvider
     */
    public function testPythonFixture(string $usage, string $argv, $expected): void {

        $docoptResult = docopt($usage, $argv, ['exit'=>false]);
        $actual = $docoptResult->isSuccess() ? $docoptResult->getArgs() : 'user-error';

        $this->assertEquals($expected, $actual);
    }

    /**
     * Provide the original Python fixtures as an array of test cases.
     *
     * - Tests are separated by `r"""`.
     * - A test's Docopt definition and the following test(s) are separated by `"""`.
     * - Each test case starts with `$` and a command prompt (one line).
     * - All following lines are expected output in JSON format.
     * - Inline comments start with `#`.
     * - Empty lines are skipped.
     *
     * @return array<array{string, string, mixed}> - test cases
     */
    public function dataProvider(): array {
        $filename = __DIR__.'/../fixtures/docopt.txt';
        if (!file_exists($filename)) throw new Exception("Python fixtures \"$filename\" not found.");
        $content = file_get_contents($filename);

        $filename = __DIR__.'/../fixtures/docopt-extra.txt';
        if (!file_exists($filename)) throw new Exception("Python fixtures \"$filename\" not found.");
        $content .= file_get_contents($filename);

        /** @var string $content */
        $content = preg_replace('/#.*$/m', '', $content);           // drop inline comments
        $content = trim($content);

        $cases = [];
        $i = 1;
        foreach (explode('r"""', $content) as $fixture) {           // split fixtures
            if (!strlen($fixture)) continue;

            $parts = explode('"""', $fixture, 2);
            if (sizeof($parts) < 2) throw new Exception('Missing string close marker');
            list($usage, $body) = $parts;
                                                                    // split test cases
            foreach (array_slice(explode('$', $body), 1) as $testCase) {
                $testCase = trim($testCase);
                list($argv, $expected) = explode(NL, $testCase, 2);
                $expected = json_decode($expected, true, 512, JSON_THROW_ON_ERROR);

                $name = "$i: $argv";
                $args = explode(' ', $argv, 2);
                $argv = $args[1] ?? '';

                $cases[$name] = [$usage, $argv, $expected];
                $i++;
            }
        }
        return $cases;
    }
}
