<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\test\docopt;

use rosasurfer\ministruts\console\docopt\DocoptParser;
use rosasurfer\ministruts\console\docopt\OptionIterator;
use rosasurfer\ministruts\console\docopt\TokenIterator;
use rosasurfer\ministruts\console\docopt\pattern\Pattern;
use rosasurfer\ministruts\console\docopt\pattern\Required;


/**
 *
 */
class TestParser extends DocoptParser {

    /**
     * Public wrapper for the protected method {@link DocoptParser::formalUsage()}.
     *
     * @param  string $section
     *
     * @return string
     */
    public static function formalUsage(string $section): string {
        return parent::formalUsage($section);
    }

    /**
     * Public wrapper for the protected method {@link DocoptParser::parseArgs()}.
     *
     * @param  TokenIterator  $tokens
     * @param  OptionIterator $options
     * @param  bool           $optionsFirst [optional]
     *
     * @return Pattern[]
     */
    public static function parseArgs(TokenIterator $tokens, OptionIterator $options, $optionsFirst = false) {
        return parent::parseArgs($tokens, $options, $optionsFirst);
    }

    /**
     * Public wrapper for the protected method {@link DocoptParser::parseDefaults()}.
     *
     * @param  string $doc
     *
     * @return OptionIterator
     */
    public static function parseDefaults($doc) {
        return parent::parseDefaults($doc);
    }

    /**
     * Public wrapper for the protected method {@link DocoptParser::parsePattern()}.
     *
     * @param  string         $source
     * @param  OptionIterator $options
     *
     * @return Required
     */
    public static function parsePattern(string $source, OptionIterator $options): Required {
        return parent::parsePattern($source, $options);
    }

    /**
     * Public wrapper for the protected method {@link DocoptParser::parseSection()}.
     *
     * @param  string $name
     * @param  string $source
     *
     * @return string[]
     */
    public static function parseSection($name, $source) {
        return parent::parseSection($name, $source);
    }
}
