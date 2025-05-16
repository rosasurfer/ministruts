<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\tests\docopt;

use rosasurfer\ministruts\console\docopt\DocoptParser;
use rosasurfer\ministruts\console\docopt\OptionIterator;
use rosasurfer\ministruts\console\docopt\TokenIterator;
use rosasurfer\ministruts\console\docopt\pattern\Required;


/**
 *
 */
class TestParser extends DocoptParser {

    /**
     * {@inheritDoc}
     *
     * Public wrapper for the protected method {@link DocoptParser::formalUsage()}.
     */
    public static function formalUsage(string $section): string {
        return parent::formalUsage($section);
    }

    /**
     * {@inheritDoc}
     *
     * Public wrapper for the protected method {@link DocoptParser::parseArgs()}.
     */
    public static function parseArgs(TokenIterator $tokens, OptionIterator $options, bool $optionsFirst = false): array {
        return parent::parseArgs($tokens, $options, $optionsFirst);
    }

    /**
     * {@inheritDoc}
     *
     * Public wrapper for the protected method {@link DocoptParser::parseDefaults()}.
     */
    public static function parseDefaults($doc): OptionIterator {
        return parent::parseDefaults($doc);
    }

    /**
     * {@inheritDoc}
     *
     * Public wrapper for the protected method {@link DocoptParser::parsePattern()}.
     */
    public static function parsePattern(string $source, OptionIterator $options): Required {
        return parent::parsePattern($source, $options);
    }

    /**
     * {@inheritDoc}
     *
     * Public wrapper for the protected method {@link DocoptParser::parseSection()}.
     */
    public static function parseSection($name, $source): array {
        return parent::parseSection($name, $source);
    }
}
