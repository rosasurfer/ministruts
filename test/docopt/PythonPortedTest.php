<?php
declare(strict_types=1);

namespace rosasurfer\test\docopt;

use PHPUnit\Framework\TestCase;

use rosasurfer\console\docopt\DocoptResult;
use rosasurfer\console\docopt\TokenIterator;
use rosasurfer\console\docopt\exception\DocoptFormatError;
use rosasurfer\console\docopt\pattern\Argument;
use rosasurfer\console\docopt\pattern\Command;
use rosasurfer\console\docopt\pattern\Either;
use rosasurfer\console\docopt\pattern\OneOrMore;
use rosasurfer\console\docopt\pattern\Option;
use rosasurfer\console\docopt\pattern\Optional;
use rosasurfer\console\docopt\pattern\OptionsShortcut;
use rosasurfer\console\docopt\pattern\Required;

use function rosasurfer\docopt;


/**
 * Tests ported from Python to PHPUnit
 */
class PythonPortedTest extends TestCase {

    /**
     *
     */
    function testFlatPattern(): void {
        $required = new Required([new OneOrMore(new Argument('N')), new Option('-a'), new Argument('M')]);

        $this->assertEquals(
            $required->flat(),
            [new Argument('N'), new Option('-a'), new Argument('M')]
        );
    }

    /**
     *
     */
    function testOption(): void {
        $this->assertEquals(Option::parse('-h'),         new Option('-h'));
        $this->assertEquals(Option::parse('--help'),     new Option(null, '--help'));
        $this->assertEquals(Option::parse('-h --help'),  new Option('-h', '--help'));
        $this->assertEquals(Option::parse('-h, --help'), new Option('-h', '--help'));

        $this->assertEquals(Option::parse('-h TOPIC'),               new Option('-h', null, 1));
        $this->assertEquals(Option::parse('--help TOPIC'),           new Option(null, '--help', 1));
        $this->assertEquals(Option::parse('-h TOPIC --help TOPIC'),  new Option('-h', '--help', 1));
        $this->assertEquals(Option::parse('-h TOPIC, --help TOPIC'), new Option('-h', '--help', 1));
        $this->assertEquals(Option::parse('-h TOPIC, --help=TOPIC'), new Option('-h', '--help', 1));

        $this->assertEquals(Option::parse('-h  Description...'),        new Option('-h', null));
        $this->assertEquals(Option::parse('-h --help  Description...'), new Option('-h', '--help'));
        $this->assertEquals(Option::parse('-h TOPIC  Description...'),  new Option('-h', null, 1));

        $this->assertEquals(Option::parse('    -h'), new Option('-h', null));

        $this->assertEquals(Option::parse('-h TOPIC  Description... [default: 2]'),       new Option('-h', null, 1, '2'));
        $this->assertEquals(Option::parse('-h TOPIC  Description... [default: topic-1]'), new Option('-h', null, 1, 'topic-1'));
        $this->assertEquals(Option::parse('--help=TOPIC  ... [default: 3.14]'),           new Option(null, '--help', 1, '3.14'));
        $this->assertEquals(Option::parse('-h, --help=DIR  ... [default: ./]'),           new Option('-h', '--help', 1, "./"));
        $this->assertEquals(Option::parse('-h TOPIC  Description... [dEfAuLt: 2]'),       new Option('-h', null, 1, '2'));
    }

    /**
     *
     */
    public function testOptionName(): void {
        $option = new Option('-h', null);
        $this->assertSame($option->name(), '-h');

        $option = new Option('-h', '--help');
        $this->assertSame($option->name(), '--help');

        $option = new Option(null, '--help');
        $this->assertSame($option->name(), '--help');
    }

    /**
     *
     */
    public function testCommands(): void {
        $this->assertEquals($this->docopt('Usage: prog add',      'add')->getArgs(), ['add' => true ]);
        $this->assertEquals($this->docopt('Usage: prog [add]',       '')->getArgs(), ['add' => false]);
        $this->assertEquals($this->docopt('Usage: prog [add]',    'add')->getArgs(), ['add' => true ]);
        $this->assertEquals($this->docopt('Usage: prog (add|rm)', 'add')->getArgs(), ['add' => true,  'rm' => false]);
        $this->assertEquals($this->docopt('Usage: prog (add|rm)',  'rm')->getArgs(), ['add' => false, 'rm' => true ]);
        $this->assertEquals($this->docopt('Usage: prog a b',      'a b')->getArgs(), ['a'   => true,  'b'  => true ]);

        // invalid input exit test
        $this->assertSame($this->docopt('Usage: prog a b', 'b a')->getError(), 1);
    }

    /**
     *
     */
    public function testFormalUsage(): void {
        $doc =
            "Usage: prog [-hv] ARG\n"
           ."           prog N M\n"
           ."\n"
           ."prog is a program"
        ;

        list($usage, ) = TestParser::parseSection('usage:', $doc);

        $this->assertSame($usage, "Usage: prog [-hv] ARG\n           prog N M");
        $this->assertSame(TestParser::formalUsage($usage), "( [-hv] ARG ) | ( N M )");
    }

    /**
     *
     */
    public function testParseArgv(): void {
        $o = new \ArrayIterator([new Option('-h'), new Option('-v', '--verbose'), new Option('-f', '--file', 1)]);
        $ts = function($src) {
            return new TokenIterator($src);
        };

        $this->assertEquals(TestParser::parseArgs($ts(''), $o), []);
        $this->assertEquals(
            TestParser::parseArgs($ts('-h'), $o),
            [new Option('-h', null, 0, true)]
        );
        $this->assertEquals(
            TestParser::parseArgs($ts('-h --verbose'), $o),
            [new Option('-h', null, 0, true), new Option('-v', '--verbose', 0, true)]
        );
        $this->assertEquals(
            TestParser::parseArgs($ts('-h --file f.txt'), $o),
            [new Option('-h', null, 0, true), new Option('-f', '--file', 1, 'f.txt')]
        );
        $this->assertEquals(
            TestParser::parseArgs($ts('-h --file f.txt arg'), $o),
            [
             new Option('-h', null, 0, true),
             new Option('-f', '--file', 1, 'f.txt'),
             new Argument(null, 'arg')
            ]
        );
        $this->assertEquals(
            TestParser::parseArgs($ts('-h --file f.txt arg arg2'), $o),
            [
             new Option('-h', null, 0, true),
             new Option('-f', '--file', 1, 'f.txt'),
             new Argument(null, 'arg'),
             new Argument(null, 'arg2')
            ]
        );
        $this->assertEquals(
            TestParser::parseArgs($ts('-h arg -- -v'), $o),
            [
             new Option('-h', null, 0, true),
             new Argument(null, 'arg'),
             new Argument(null, '--'),
             new Argument(null, '-v')
            ]
        );
    }

    /**
     *
     */
    public function testParsePattern(): void {
        $o = new \ArrayIterator([new Option('-h'), new Option('-v', '--verbose'), new Option('-f', '--file', 1)]);

        $this->assertEquals(
            TestParser::parsePattern('[ -h ]', $o),
            new Required(new Optional(new Option('-h')))
        );
        $this->assertEquals(
            TestParser::parsePattern('[ ARG ... ]', $o),
            new Required(new Optional(new OneOrMore(new Argument('ARG'))))
        );
        $this->assertEquals(
            TestParser::parsePattern('[ -h | -v ]', $o),
            new Required(new Optional(
                new Either(new Option('-h'), new Option('-v', '--verbose'))
            ))
        );
        $this->assertEquals(
            TestParser::parsePattern('( -h | -v [ --file <f> ] )', $o),
            new Required(new Required(new Either(new Option('-h'), new Required(new Option('-v', '--verbose'), new Optional(new Option('-f', '--file', 1, null))))))
        );
        $this->assertEquals(
            TestParser::parsePattern('(-h|-v[--file=<f>]N...)', $o),
            new Required(new Required(new Either(new Option('-h'),
            new Required(new Option('-v', '--verbose'),
            new Optional(new Option('-f', '--file', 1, null)),
            new OneOrMore(new Argument('N'))))))
        );
        $this->assertEquals(
            TestParser::parsePattern('(N [M | (K | L)] | O P)', new \ArrayIterator([])),
            new Required(new Required(new Either(new Required(new Argument('N'),
            new Optional(new Either(new Argument('M'), new Required(
            new Either(new Argument('K'), new Argument('L')))))),
            new Required(new Argument('O'), new Argument('P')))))
        );
        $this->assertEquals(
            TestParser::parsePattern('[ -h ] [N]', $o),
            new Required(
                new Optional(new Option('-h')),
                new Optional(new Argument('N'))
            )
        );
        $this->assertEquals(
            TestParser::parsePattern('[options]', $o),
            new Required(new Optional(new OptionsShortcut()))
        );
        $this->assertEquals(TestParser::parsePattern('[options] A', $o),
            new Required(
            new Optional(new OptionsShortcut()),
            new Argument('A'))
        );
        $this->assertEquals(TestParser::parsePattern('-v [options]', $o),
            new Required(new Option('-v', '--verbose'),
            new Optional(new OptionsShortcut()))
        );
        $this->assertEquals(TestParser::parsePattern('ADD',   $o), new Required(new Argument('ADD')));
        $this->assertEquals(TestParser::parsePattern('<add>', $o), new Required(new Argument('<add>')));
        $this->assertEquals(TestParser::parsePattern('add',   $o), new Required(new Command('add')));
    }

    /**
     *
     */
    public function testOptionMatch(): void {
        $option = new Option('-a');
        $this->assertEquals(
            $option->match([new Option('-a', null, 0, true)]),
            [true, [], [new Option('-a', null, 0, true)]]
        );

        $option = new Option('-a');
        $this->assertEquals(
            $option->match([new Option('-x')]),
            [false, array(new Option('-x')), []]
        );

        $option = new Option('-a');
        $this->assertEquals(
            $option->match([new Argument('N')]),
            [false, array(new Argument('N')), []]
        );

        $option = new Option('-a');
        $this->assertEquals(
            $option->match([new Option('-x'), new Option('-a'), new Argument('N')]),
            [true, array(new Option('-x'), new Argument('N')), array(new Option('-a'))]
        );

        $option = new Option('-a');
        $this->assertEquals(
            $option->match([new Option('-a', null, 0, true), new Option('-a')]),
            [true, array(new Option('-a')), array(new Option('-a', null, 0, true))]
        );
    }

    /**
     *
     */
    function testArgumentMatch(): void {
        $argument = new Argument('N');
        $this->assertEquals(
            $argument->match([new Argument(null, 9)]),
            [true, [], [new Argument('N', 9)]]
        );

        $argument = new Argument('N');
        $this->assertEquals(
            $argument->match([new Option('-x')]),
            [false, [new Option('-x')], []]
        );

        $argument = new Argument('N');
        $this->assertEquals(
            $argument->match([new Option('-x'), new Option('-a'), new Argument(null, 5)]),
            [true, [new Option('-x'), new Option('-a')], [new Argument('N', 5)]]
        );

        $argument = new Argument('N');
        $this->assertEquals(
            $argument->match([new Argument(null, 9), new Argument(null, 0)]),
            [true, [new Argument(null, 0)], [new Argument('N', 9)]]
        );
    }

    /**
     *
     */
    function testCommandMatch(): void {
        $command = new Command('c');
        $this->assertEquals(
            $command->match([new Argument(null, 'c')]),
            [true, [], [new Command('c', true)]]
        );

        $command = new Command('c');
        $this->assertEquals(
            $command->match([new Option('-x')]),
            [false, [new Option('-x')], []]
        );

        $command = new Command('c');
        $this->assertEquals(
            $command->match([new Option('-x'), new Option('-a'), new Argument(null, 'c')]),
            [true, [new Option('-x'), new Option('-a')], [new Command('c', true)]]
        );

        $either = new Either(new Command('add', false), new Command('rm', false));
        $this->assertEquals(
            $either->match([new Argument(null, 'rm')]),
            [true, [], [new Command('rm', true)]]
        );
    }

    /**
     *
     */
    function testOptionalMatch(): void {
        $optional = new Optional(new Option('-a'));
        $this->assertEquals(
            $optional->match([new Option('-a')]),
            [true, [], [new Option('-a')]]
        );

        $optional = new Optional(new Option('-a'));
        $this->assertEquals(
            $optional->match([]),
            [true, [], []]
        );

        $optional = new Optional(new Option('-a'));
        $this->assertEquals(
            $optional->match([new Option('-x')]),
            [true, [new Option('-x')], []]
        );

        $optional = new Optional(new Option('-a'), new Option('-b'));
        $this->assertEquals(
            $optional->match([new Option('-a')]),
            [true, [], [new Option('-a')]]
        );

        $optional = new Optional(new Option('-a'), new Option('-b'));
        $this->assertEquals(
            $optional->match([new Option('-b')]),
            [true, [], [new Option('-b')]]
        );

        $optional = new Optional(new Option('-a'), new Option('-b'));
        $this->assertEquals(
            $optional->match([new Option('-x')]),
            [true, [new Option('-x')], []]
        );

        $optional = new Optional(new Argument('N'));
        $this->assertEquals(
            $optional->match([new Argument(null, 9)]),
            [true, [], [new Argument('N', 9)]]
        );

        $optional = new Optional(new Option('-a'), new Option('-b'));
        $this->assertEquals(
            $optional->match([new Option('-b'), new Option('-x'), new Option('-a')]),
            [true, [new Option('-x')], [new Option('-a'), new Option('-b')]]
        );
    }

    /**
     *
     */
    function testRequiredMatch(): void {
        $required = new Required(new Option('-a'));
        $this->assertEquals(
            $required->match([new Option('-a')]),
            [true, [], array(new Option('-a'))]
        );

        $required = new Required(new Option('-a'));
        $this->assertEquals(
            $required->match([]),
            [false, [], []]
        );

        $required = new Required(new Option('-a'));
        $this->assertEquals(
            $required->match([new Option('-x')]),
            [false, array(new Option('-x')), []]
        );

        $required = new Required(new Option('-a'), new Option('-b'));
        $this->assertEquals(
            $required->match([new Option('-a')]),
            [false, array(new Option('-a')), []]
        );
    }

    /**
     *
     */
    function testEitherMatch(): void {
        $either = new Either(new Option('-a'), new Option('-b'));
        $this->assertEquals(
            $either->match([new Option('-a')]),
            [true, [], [new Option('-a')]]
        );

        $either = new Either(new Option('-a'), new Option('-b'));
        $this->assertEquals(
            $either->match([new Option('-a'), new Option('-b')]),
            [true, [new Option('-b')], [new Option('-a')]]
        );

        $either = new Either(new Option('-a'), new Option('-b'));
        $this->assertEquals(
            $either->match(array(new Option('-x'))),
            array(false, array(new Option('-x')), [])
        );

        $either = new Either(new Option('-a'), new Option('-b'), new Option('-c'));
        $this->assertEquals(
            $either->match(array(new Option('-x'), new Option('-b'))),
            array(true, array(new Option('-x')), array(new Option('-b')))
        );

        $either = new Either(new Argument('M'), new Required(new Argument('N'), new Argument('M')));
        $this->assertEquals(
            $either->match(array(new Argument(null, 1), new Argument(null, 2))),
            array(true, [], array(new Argument('N', 1), new Argument('M', 2)))
        );
    }

    /**
     *
     */
    function testOneOrMoreMatch(): void {
        $oneOrMore = new OneOrMore(new Argument('N'));
        $this->assertEquals(
            $oneOrMore->match(array(new Argument(null, 9))),
            array(true, [], array(new Argument('N', 9)))
        );

        $oneOrMore = new OneOrMore(new Argument('N'));
        $this->assertEquals(
            $oneOrMore->match([]),
            array(false, [], [])
        );

        $oneOrMore = new OneOrMore(new Argument('N'));
        $this->assertEquals(
            $oneOrMore->match(array(new Option('-x'))),
            array(false, array(new Option('-x')), [])
        );

        $oneOrMore = new OneOrMore(new Argument('N'));
        $this->assertEquals(
            $oneOrMore->match(array(new Argument(null, 9), new Argument(null, 8))),
            array(true, [], array(new Argument('N', 9), new Argument('N', 8)))
        );

        $oneOrMore = new OneOrMore(new Argument('N'));
        $this->assertEquals(
            $oneOrMore->match(array(new Argument(null, 9), new Option('-x'), new Argument(null, 8))),
            array(true, array(new Option('-x')), array(new Argument('N', 9), new Argument('N', 8)))
        );

        $oneOrMore = new OneOrMore(new Option('-a'));
        $this->assertEquals(
            $oneOrMore->match(array(new Option('-a'), new Argument(null, 8), new Option('-a'))),
            array(true, array(new Argument(null, 8)), array(new Option('-a'), new Option('-a')))
        );

        $oneOrMore = new OneOrMore(new Option('-a'));
        $this->assertEquals(
            $oneOrMore->match(array(new Argument(null, 8), new Option('-x'))),
            array(false, array(new Argument(null, 8), new Option('-x')), [])
        );

        $oneOrMore = new OneOrMore(new Required(new Option('-a'), new Argument('N')));
        $this->assertEquals(
            $oneOrMore->match(array(new Option('-a'), new Argument(null, 1), new Option('-x'), new Option('-a'), new Argument(null, 2))),
            array(true, array(new Option('-x')), array(new Option('-a'), new Argument('N', 1), new Option('-a'), new Argument('N', 2)))
        );

        $oneOrMore = new OneOrMore(new Optional(new Argument('N')));
        $this->assertEquals(
            $oneOrMore->match(array(new Argument(null, 9))),
            array(true, [], array(new Argument('N', 9)))
        );
    }

    /**
     *
     */
    function testListArgumentMatch(): void {
        $input = new Required(new Argument('N'), new Argument('N'));

        $this->assertEquals(
            $input->fix()->match([new Argument(null, '1'), new Argument(null, '2')]),
            [true, [], [new Argument('N', ['1', '2'])]]
        );

        $input = new OneOrMore(new Argument('N'));
        $this->assertEquals(
            $input->fix()->match(array(new Argument(null, '1'), new Argument(null, '2'), new Argument(null, '3'))),
            array(true, [], array(new Argument('N', array('1', '2', '3'))))
        );

        $input = new Required(new Argument('N'), new OneOrMore(new Argument('N')));
        $this->assertEquals(
            $input->fix()->match(array(new Argument(null, '1'), new Argument(null, '2'), new Argument(null, '3'))),
            array(true, [], array(new Argument('N', array('1', '2', '3'))))
        );

        $input = new Required(new Argument('N'), new Required(new Argument('N')));
        $this->assertEquals(
            $input->fix()->match(array(new Argument(null, '1'), new Argument(null, '2'))),
            array(true, [], array(new Argument('N', array('1', '2'))))
        );
    }

    /**
     *
     */
    function testBasicPatternMatch(): void {
        // ( -a N [ -x Z ] )
        $pattern = new Required(new Option('-a'), new Argument('N'), new Optional(new Option('-x'), new Argument('Z')));

        // -a N
        $this->assertEquals(
            $pattern->match(array(new Option('-a'), new Argument(null, 9))),
            array(true, [], array(new Option('-a'), new Argument('N', 9)))
        );

        // -a -x N Z
        $this->assertEquals(
            $pattern->match(array(new Option('-a'), new Option('-x'), new Argument(null, 9), new Argument(null, 5))),
            array(true, [], array(new Option('-a'), new Argument('N', 9), new Option('-x'), new Argument('Z', 5)))
        );

        // -x N Z  # BZZ!
        $this->assertEquals(
            $pattern->match(array(new Option('-x'), new Argument(null, 9), new Argument(null, 5))),
            array(false, array(new Option('-x'), new Argument(null, 9), new Argument(null, 5)), [])
        );
    }

    /**
     *
     */
    function testPatternEither(): void {
        $input = new Option('-a');
        $this->assertEquals(
            TestPattern::transform($input),
            new Either(new Required(new Option('-a')))
        );

        $input = new Argument('A');
        $this->assertEquals(
            TestPattern::transform($input),
            new Either(new Required(new Argument('A')))
        );

        $input = new Required(new Either(new Option('-a'), new Option('-b')), new Option('-c'));
        $this->assertEquals(
            TestPattern::transform($input),
            new Either(
                new Required(new Option('-a'), new Option('-c')),
                new Required(new Option('-b'), new Option('-c'))
            )
        );

        $input = new Optional(new Option('-a'), new Either(new Option('-b'), new Option('-c')));
        $this->assertEquals(
            TestPattern::transform($input),
            new Either(
                new Required(new Option('-b'), new Option('-a')),
                new Required(new Option('-c'), new Option('-a'))
            )
        );

        $input = new Either(new Option('-x'), new Either(new Option('-y'), new Option('-z')));
        $this->assertEquals(
            TestPattern::transform($input),
            new Either(
                new Required(new Option('-x')),
               new Required(new Option('-y')),
               new Required(new Option('-z'))
            )
        );

        $input = new OneOrMore(new Argument('N'), new Argument('M'));
        $this->assertEquals(
            TestPattern::transform($input),
            new Either(new Required(new Argument('N'), new Argument('M'), new Argument('N'), new Argument('M')))
        );
    }

    /**
     *
     */
    function testPatternFixRepeatingArguments(): void {
        $input = new Option('-a');
        $this->assertEquals($input->fixRepeatingArguments(), new Option('-a'));

        $input = new Argument('N', null);
        $this->assertEquals($input->fixRepeatingArguments(), new Argument('N', null));

        $input = new Required(new Argument('N'), new Argument('N'));
        $this->assertEquals(
            $input->fixRepeatingArguments(),
            new Required(new Argument('N', []), new Argument('N', []))
        );

        $input = new Either(new Argument('N'), new OneOrMore(new Argument('N')));
        $this->assertEquals(
            $input->fix(),
            new Either(new Argument('N', []), new OneOrMore(new Argument('N', [])))
        );
    }

    /**
     *
     */
    function testSet(): void {
        $this->assertEquals(new Argument('N'), new Argument('N'));
        $this->assertEquals(
            array_unique(array(new Argument('N'), new Argument('N'))),
            array(new Argument('N'))
        );
    }

    /**
     *
     */
    function testPatternFixIdentities1(): void {
        $pattern = new Required(new Argument('N'), new Argument('N'));
        $this->assertEquals($pattern->children[0], $pattern->children[1]);
        $this->assertNotSame($pattern->children[0], $pattern->children[1]);
        $pattern->fixIdentities();
        $this->assertSame($pattern->children[0], $pattern->children[1]);
    }

    /**
     *
     */
    function testPatternFixIdentities2(): void {
        $pattern = new Required(new Optional(new Argument('X'), new Argument('N')), new Argument('N'));
        $this->assertEquals($pattern->children[0]->children[1], $pattern->children[1]);
        $this->assertNotSame($pattern->children[0]->children[1], $pattern->children[1]);
        $pattern->fixIdentities();
        $this->assertSame($pattern->children[0]->children[1], $pattern->children[1]);
    }

    /**
     *
     */
    function testLongOptionsErrorHandling1(): void {
        // $this->setExpectedException(DocoptFormatError::class);
        // $this->docopt('Usage: prog --non-existent', '--non-existent')
        //
        // $this->setExpectedException(DocoptFormatError::class);
        // $this->docopt('Usage: prog --non-existent')

        $result = $this->docopt('Usage: prog', '--non-existent');
        $this->assertFalse($result->isSuccess());

        $result = $this->docopt("Usage: prog [--version --verbose]\n".
                                "Options: --version\n --verbose", '--ver');
        $this->assertFalse($result->isSuccess());
    }

    /**
     *
     */
    function testLongOptionsErrorHandling2(): void {
        $this->expectException(DocoptFormatError::class);
        $this->docopt("Usage: prog --long\nOptions: --long ARG");
    }

    /**
     *
     */
    function testLongOptionsErrorHandling3(): void {
        $result = $this->docopt("Usage: prog --long ARG\nOptions: --long ARG", '--long');
        $this->assertFalse($result->isSuccess());
    }

    /**
     *
     */
    function testLongOptionsErrorHandling4() {
        $this->expectException(DocoptFormatError::class);
        $this->docopt("Usage: prog --long=ARG\nOptions: --long");
    }

    /**
     *
     */
    function testLongOptionsErrorHandling5(): void {
        $result = $this->docopt("Usage: prog --long\nOptions: --long", '--long=ARG');
        $this->assertFalse($result->isSuccess());
    }

    /**
     *
     */
    function testShortOptionsErrorHandling1(): void {
        $this->expectException(DocoptFormatError::class);
        $this->docopt("Usage: prog -x\nOptions: -x  this\n -x  that");
    }

    /**
     *
     */
    function testShortOptionsErrorHandling2(): void {
        $result = $this->docopt('Usage: prog', '-x');
        $this->assertFalse($result->isSuccess());
    }

    /**
     *
     */
    function testShortOptionsErrorHandling3(): void {
        $this->expectException(DocoptFormatError::class);
        $this->docopt("Usage: prog -o\nOptions: -o ARG");
    }

    /**
     *
     */
    function testShortOptionsErrorHandling4(): void {
        $result = $this->docopt("Usage: prog -o ARG\n\n-o ARG", '-o');
        $this->assertFalse($result->isSuccess());
    }

    /**
     *
     */
    function testMatchingParentheses1(): void {
        $this->expectException(DocoptFormatError::class);
        $this->docopt('Usage: prog [a [b]');
    }

    /**
     *
     */
    function testMatchingParentheses2(): void {
        $this->expectException(DocoptFormatError::class);
        $this->docopt('Usage: prog [a [b] ] c )');
    }

    /**
     *
     */
    function testAllowDoubleDash(): void {
        $this->assertEquals(
            $this->docopt("usage: prog [-o] [--] <arg>\nOptions: -o", '-- -o')->getArgs(),
            array('-o'=> false, '<arg>'=>'-o', '--'=>true)
        );

        $this->assertEquals(
            $this->docopt("usage: prog [-o] [--] <arg>\nOptions: -o", '-o 1')->getArgs(),
            array('-o'=>true, '<arg>'=>'1', '--'=>false)
        );

        $result = $this->docopt("usage: prog [-o] <arg>\nOptions: -o", '-- -o');    // "--" is not allowed; FIXME?
        $this->assertFalse($result->isSuccess());
    }

    /**
     *
     */
    function testDocopt(): void {
        $doc = "Usage: prog [-v] A\n\n  Options: -v  Be verbose.";

        $this->assertEquals($this->docopt($doc, 'arg')->getArgs(), array('-v'=>false, 'A'=>'arg'));
        $this->assertEquals($this->docopt($doc, '-v arg')->getArgs(), array('-v'=>true, 'A'=>'arg'));

        $doc = "Usage: prog [-vqr] [FILE]
                  prog INPUT OUTPUT
                  prog --help

        Options:
          -v  print status messages
          -q  report only file names
          -r  show all occurrences of the same error
          --help

        ";

        $a = $this->docopt($doc, '-v file.py');
        $this->assertEquals(
            $a->getArgs(),
            array('-v'=>true, '-q'=>false, '-r'=>false, '--help'=>false, 'FILE'=>'file.py', 'INPUT'=>null, 'OUTPUT'=>null)
        );

        $a = $this->docopt($doc, '-v');
        $this->assertEquals(
            $a->getArgs(),
            array('-v'=>true, '-q'=>false, '-r'=>false, '--help'=>false, 'FILE'=>null, 'INPUT'=>null, 'OUTPUT'=>null)
        );

        $result = $this->docopt($doc, '-v input.py output.py');
        $this->assertFalse($result->isSuccess());

        $result = $this->docopt($doc, '--fake');
        $this->assertFalse($result->isSuccess());

        $result = $this->docopt($doc, '--hel');
        $this->assertTrue($result['--help']);
    }

    /**
     *
     */
    function testDocoptFormatError1(): void {
        $this->expectException(DocoptFormatError::class);
        $this->docopt('no usage with colon here');
    }

    /**
     *
     */
    function testDocoptFormatError2(): void {
        $this->expectException(DocoptFormatError::class);
        $this->docopt("usage: here \n\n and again usage: here");
    }

    /**
     *
     */
    function testIssue40(): void {
        $result = $this->docopt('usage: prog --help-commands | --help', '--help');
        $this->assertTrue($result['--help']);

        $this->assertEquals(
            $this->docopt('usage: prog --aabb | --aa', '--aa')->getArgs(),
            array('--aabb'=>false, '--aa'=>true)
        );
    }

    /**
     *
     */
    function testCountMultipleFlags(): void {
        $this->assertEquals($this->docopt('usage: prog [-v]', '-v')->getArgs(), array('-v'=>true));
        $this->assertEquals($this->docopt('usage: prog [-vv]', '')->getArgs(), array('-v'=>0));
        $this->assertEquals($this->docopt('usage: prog [-vv]', '-v')->getArgs(), array('-v'=>1));
        $this->assertEquals($this->docopt('usage: prog [-vv]', '-vv')->getArgs(), array('-v'=>2));
        $this->assertEquals($this->docopt('usage: prog [-vv]', '-v -v')->getArgs(), array('-v'=>2));

        $this->assertFalse($this->docopt('usage: prog [-vv]', '-vvv')->isSuccess());

        $this->assertEquals($this->docopt('usage: prog [-v | -vv | -vvv]', '-vvv')->getArgs(), array('-v'=>3));
        $this->assertEquals($this->docopt('usage: prog -v...', '-vvvvvv')->getArgs(), array('-v'=>6));
        $this->assertEquals($this->docopt('usage: prog [--ver --ver]', '--ver --ver')->getArgs(), array('--ver'=>2));
    }

    /**
     *
     */
    function testOptionsShortcutParameter(): void {
        $result = $this->docopt('usage: prog [options]', '-foo --bar --spam=eggs');
        $this->assertFalse($result->isSuccess());

        //$this->assertEquals(
        //    $this->docopt('usage: prog [options]', '-foo --bar --spam=eggs', $any_options=true),
        //    array('-f'=>true, '-o'=>2, '--bar'=>true, '--spam'=>'eggs')
        //);

        $result = $this->docopt('usage: prog [options]', '--foo --bar --bar');
        $this->assertFalse($result->isSuccess());

        //$this->assertEquals(
        //    $this->docopt('usage: prog [options]', '--foo --bar --bar', $any_options=true),
        //    array('--foo'=>true, '--bar'=>2)
        //);

        $result = $this->docopt('usage: prog [options]', '--bar --bar --bar -ffff');
        $this->assertFalse($result->isSuccess());

        //$this->assertEquals(
        //    $this->docopt('usage: prog [options]', '--bar --bar --bar -ffff', $any_options=true),
        //    array('--bar'=>3, '-f'=>4)
        //);

        $result = $this->docopt('usage: prog [options]', '--long=arg --long=another');
        $this->assertFalse($result->isSuccess());

        //$this->assertEquals(
        //    $this->docopt('usage: prog [options]', '--long=arg --long=another', $any_options=true),
        //    array('--long'=>['arg', 'another'])
        //);
    }

    #def test_options_shortcut_multiple_commands():
    #    # any_options is disabled
    #    $this->assertEquals(
    #        $this->docopt('usage: prog c1 [options] prog c2 [options]', 'c2 -o', $any_options=true),
    #        array('-o'=>true, 'c1'=>false, 'c2'=>true)
    #    );
    #    $this->assertEquals(
    #        $this->docopt('usage: prog c1 [options] prog c2 [options]', 'c1 -o', $any_options=true),
    #        array('-o'=>true, 'c1'=>true, 'c2'=>false)
    #    );

    /**
     * for some reason removed in the Python version
     */
    public function testOptionsShortcutDoesNotAddOptionsToPatternSecondTime(): void {
        $this->assertEquals(
            $this->docopt("usage: prog [options] [-a]\nOptions: -a -b", '-a')->getArgs(),
            array('-a'=>true, '-b'=>false)
        );

        $result = $this->docopt("usage: prog [options] [-a]\nOptions: -a -b", '-aa');
        $this->assertFalse($result->isSuccess());
    }

    /**
     *
     */
    function testDefaultValueForPositionalArguments(): void {
        $doc = "Usage: prog [--data=<data>...]\n".
               "Options:\n\t-d --data=<arg>    Input data [default: x]";
        $a = $this->docopt($doc, '')->getArgs();
        $this->assertEquals($a, array('--data'=>array('x')));

        $doc = "Usage: prog [--data=<data>...]\n".
               "Options:\n\t-d --data=<arg>    Input data [default: x y]";
        $a = $this->docopt($doc, '')->getArgs();
        $this->assertEquals($a, array('--data'=>array('x', 'y')));

        $doc = "Usage: prog [--data=<data>...]\n".
               "Options:\n\t-d --data=<arg>    Input data [default: x y]";
        $a = $this->docopt($doc, '--data=this')->getArgs();
        $this->assertEquals($a, array('--data'=>array('this')));

        // doesn't work
        //$doc = "Usage: prog [--data=<data>...]\n".
        //       "Options:\n\t-d --data=<arg>    Input data [default: \"hello world\"]";
        //$args = $this->docopt($doc, '')->getArgs();
        //$this->assertEquals($args, ['--data'=>['hello world']]);
    }

    #def test_parse_defaults():
    #    $this->assertEquals(parse_defaults("""usage: prog
    #
    #                          -o, --option <o>
    #                          --another <a>  description
    #                                         [default: x]
    #                          <a>
    #                          <another>  description [default: y]"""),
    #           ([new Option('-o', '--option', 1, null),
    #             new Option(null, '--another', 1, 'x')],
    #            [new Argument('<a>', null),
    #             new Argument('<another>', 'y')])
    #
    #    doc = '''
    #    -h, --help  Print help message.
    #    -o FILE     Output file.
    #    --verbose   Verbose mode.'''
    #    $this->assertEquals(parse_defaults(doc)[0], [new Option('-h', '--help'),
    #                                      new Option('-o', null, 1),
    #                                      new Option(null, '--verbose')]

    /**
     *
     */
    public function testIssue59(): void {
        $this->assertEquals($this->docopt("usage: prog --long=<a>", '--long=')->getArgs(), array('--long'=>''));
        $this->assertEquals($this->docopt("usage: prog -l <a>\noptions: -l <a>", array('-l', ''))->getArgs(), array('-l'=>''));
    }

    /**
     *
     */
    public function testOptionsFirst(): void {
        $this->assertEquals(
            $this->docopt('usage: prog [--opt] [<args>...]', '--opt this that')->getArgs(),
            ['--opt'=>true, '<args>'=>['this', 'that']]
        );

        $this->assertEquals(
            $this->docopt('usage: prog [--opt] [<args>...]', 'this that --opt')->getArgs(),
            ['--opt'=>true, '<args>'=>['this', 'that']]
        );

        $this->assertEquals(
            $this->docopt('usage: prog [--opt] [<args>...]', 'this that --opt', array('optionsFirst'=>true))->getArgs(),
            array('--opt'=>false, '<args>'=>array('this', 'that', '--opt'))
        );

        // found issue with PHP version in this situation
        $this->assertEquals(
            $this->docopt('usage: prog [--opt=<val>] [<args>...]', ' --opt=foo this that --opt', array('optionsFirst'=>true))->getArgs(),
            array('--opt'=>'foo', '<args>'=>array('this', 'that', '--opt'))
        );
    }

    /**
     *
     */
    public function testIssue68OptionsShortcutDoesNotIncludeOptionsInUsagePattern(): void {
        $args = $this->docopt("usage: prog [-ab] [options]\noptions: -x\n -y", '-ax');
        $this->assertTrue($args['-a']);
        $this->assertFalse($args['-b']);
        $this->assertTrue($args['-x']);
        $this->assertFalse($args['-y']);
    }

    /**
     *
     */
    public function testIssue71DoubleDashIsNotAValidOptionArgument(): void {
        $result = $this->docopt("usage: prog [--log=LEVEL] [--] <args>...", "--log -- 1 2");
        $this->assertFalse($result->isSuccess());

        $result = $this->docopt("usage: prog [-l LEVEL] [--] <args>...\noptions: -l LEVEL", "-l -- 1 2");
        $this->assertFalse($result->isSuccess());
    }

    /**
     *
     */
    public function testParseSection(): void {
        $this->assertEquals(TestParser::parseSection('usage:', 'foo bar fizz buzz'), []);
        $this->assertEquals(TestParser::parseSection('usage:', 'usage: prog'), array('usage: prog'));
        $this->assertEquals(TestParser::parseSection('usage:', "usage: -x\n -y"), array("usage: -x\n -y"));

        $usage = <<<TEST_PARSE_SECTION
usage: this

usage:hai
usage: this that

usage: foo
       bar

PROGRAM USAGE:
 foo
 bar
usage:
\ttoo
\ttar
Usage: eggs spam
BAZZ
usage: pit stop
TEST_PARSE_SECTION;

        $this->assertEquals(
            TestParser::parseSection("usage:", $usage),
            array(
                "usage: this",
                "usage:hai",
                "usage: this that",
                "usage: foo\n       bar",
                "PROGRAM USAGE:\n foo\n bar",
                "usage:\n\ttoo\n\ttar",
                "Usage: eggs spam",
                "usage: pit stop"
            )
        );
    }

    /**
     *
     */
    public function testIssue126DefaultsNotParsedCorrectlyWhenTabs(): void {
        $section = "Options:\n\t--foo=<arg>  [default: bar]";
        $this->assertEquals(
            TestParser::parseDefaults($section)->getArrayCopy(),
            array(new Option(null, '--foo', 1, 'bar'))
        );
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