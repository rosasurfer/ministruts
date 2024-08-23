<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\test\docopt;

use Exception;
use PHPUnit\Framework\TestCase;
use rosasurfer\ministruts\console\docopt\DocoptResult;

use function rosasurfer\ministruts\docopt;


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
     * @return array<array{string, string, mixed}>
     */
    public function dataProvider(): array {
        $filename = __DIR__.'/../fixtures/docopt.txt';
        if (!file_exists($filename)) throw new Exception("Python fixtures \"$filename\" not found.");
        $raw = file_get_contents($filename);

        $filename = __DIR__.'/../fixtures/docopt-extra.txt';
        if (!file_exists($filename)) throw new Exception("Python fixtures \"$filename\" not found.");
        $raw .= file_get_contents($filename);

        /** @var string $raw */
        $raw = preg_replace('/#.*$/m', '', $raw);
        $raw = trim($raw);                                          // drop file comments
        if (strpos($raw, '"""') === 0) {
            $raw = substr($raw, 3);
        }

        $cases = [];
        $i = 1;
        foreach (explode('r"""', $raw) as $fixture) {               // split fixtures
            if (!strlen($fixture)) continue;

            $parts = explode('"""', $fixture, 2);
            if (sizeof($parts) < 2) throw new Exception('Missing string close marker');
            list($usage, $body) = $parts;
                                                                    // split test cases
            foreach (array_slice(explode('$', $body), 1) as $testCase) {
                $testCase = trim($testCase);
                list($argv, $expected) = explode("\n", $testCase, 2);
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

    /**
     * @param  string               $doc
     * @param  string|string[]|null $args    [optional]
     * @param  mixed[]              $options [optional]
     *
     * @return DocoptResult
     */
    protected function docopt($doc, $args='', array $options=[]): DocoptResult {
        $options = array_merge(['exit'=>false, 'help'=>false], $options);
        return docopt($doc, $args, $options);
    }
}
