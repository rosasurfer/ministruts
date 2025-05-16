<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt;

use rosasurfer\ministruts\core\CObject;

use rosasurfer\ministruts\console\docopt\exception\DocoptFormatError;
use rosasurfer\ministruts\console\docopt\exception\DocoptUserNotification;
use rosasurfer\ministruts\console\docopt\pattern\Argument;
use rosasurfer\ministruts\console\docopt\pattern\Command;
use rosasurfer\ministruts\console\docopt\pattern\Either;
use rosasurfer\ministruts\console\docopt\pattern\OneOrMore;
use rosasurfer\ministruts\console\docopt\pattern\Option;
use rosasurfer\ministruts\console\docopt\pattern\Optional;
use rosasurfer\ministruts\console\docopt\pattern\OptionsShortcut;
use rosasurfer\ministruts\console\docopt\pattern\Pattern;
use rosasurfer\ministruts\console\docopt\pattern\Required;

use function rosasurfer\ministruts\array_filter;
use function rosasurfer\ministruts\array_merge;
use function rosasurfer\ministruts\strEndsWith;
use function rosasurfer\ministruts\strStartsWith;

use const rosasurfer\ministruts\NL;


/**
 * DocoptParser
 *
 * A command line argument parser for the {@link https://docopt.org/#} language format.
 */
class DocoptParser extends CObject {


    /** @var bool */
    protected bool $optionsFirst = false;

    /** @var bool */
    protected bool $help = true;

    /** @var bool */
    protected bool $exit = true;

    /** @var bool */
    protected bool $exitFullUsage = false;

    /** @var ?string - help text displayed with every parser generated output */
    protected ?string $autoHelp = null;

    /** @var string */
    protected string $version = '';


    /**
     * Constructor
     *
     * Create a new Docopt command line argument parser.
     *
     * @param  array<string, bool|string> $options [optional]
     */
    public function __construct(array $options = []) {
        foreach ($options as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }


    /**
     * Parse command line arguments and match them against the specified {@link https://docopt.org/#} syntax definition.
     *
     * @param  string               $doc
     * @param  string|string[]|null $args [optional]
     *
     * @return DocoptResult
     */
    public function parse(string $doc, $args = null): DocoptResult {
        try {
            if (!isset($args)) {
                $args = array_slice($_SERVER['argv'] ?? [], 1);
            }

            $usage = static::parseSection('Usage:', $doc);
            if (!$usage)            throw new DocoptFormatError('"Usage:" section not found');
            if (sizeof($usage) > 1) throw new DocoptFormatError('More than one "Usage:" section found');
            $usage = $usage[0];
            $this->autoHelp = $this->exitFullUsage ? $doc : $usage;

            $docOptions = static::parseDefaults($doc);
            $formalUse  = static::formalUsage($usage);
            $pattern    = static::parsePattern($formalUse, $docOptions);
            $args       = static::parseArgs(new TokenIterator($args), $docOptions, $this->optionsFirst);

            $docOptions     = static::parseDefaults($doc);              // create a new iterator with unmodified elements
            $patternOptions = $pattern->flat([Option::class]);

            foreach ($pattern->flat([OptionsShortcut::class]) as $optionsShortcut) {
                $optionsShortcut->children = array_diff((array)$docOptions, $patternOptions);
            }

            $this->handleSpecials($this->help, $this->version, $args, $doc);

            list($matched, $left, $collected) = $pattern->fix()->match($args);
            if ($matched && !$left) {
                $result = [];
                foreach (array_merge($pattern->flat(), $collected) as $pattern) {
                    if ($name = $pattern->name()) {
                        $result[$name] = $pattern->value;
                    }
                }
                return new DocoptResult($result, $usage);
            }
            throw new DocoptUserNotification();
        }
        catch (DocoptUserNotification $ex) {
            $this->handleExit($ex);
            $msg = trim($ex->getMessage().NL.$this->autoHelp);
            return new DocoptResult([], '', $ex->status, $msg);
        }
    }


    /**
     * @param  bool      $help
     * @param  string    $version
     * @param  Pattern[] $argv
     * @param  string    $doc
     *
     * @return void
     */
    protected function handleSpecials(bool $help, string $version, array $argv, string $doc): void {
        $hfound = $vfound = false;

        foreach ($argv as $o) {
            if ($o->value) {
                if ($o->name()=='-h' || $o->name()=='--help') {
                    $hfound = true;
                }
                if ($o->name()=='--version') {
                    $vfound = true;
                }
            }
        }
        if ($help && $hfound) {
            $this->autoHelp = null;
            throw new DocoptUserNotification($doc, 0);
        }
        if ($version && $vfound) {
            $this->autoHelp = null;
            throw new DocoptUserNotification($version, 0);
        }
    }


    /**
     * @param  DocoptUserNotification $exception
     *
     * @return void
     */
    protected function handleExit(DocoptUserNotification $exception): void {
        if ($this->exit) {
            echo trim($exception->getMessage().NL.$this->autoHelp).NL;
            exit($exception->status);
        }
    }


    /**
     * @param  string $section
     *
     * @return string
     */
    protected static function formalUsage(string $section): string {
        list(, $section) = explode(':', $section, 2);       // drop "usage:"
        $pu = preg_split('/\s+/', trim($section));

        $ret = [];
        foreach (array_slice($pu, 1) as $s) {
            if ($pu[0] == $s) $ret[] = ') | (';
            else              $ret[] = $s;
        }
        return '( '.join(' ', $ret).' )';
    }


    /**
     * Parse arguments.
     *
     * If $optionsFirst=true: argv ::= [ long | shorts ]* [ argument ]* [ '--' [ argument ]* ] ;
     * else:                  argv ::= [ long | shorts | argument ]* [ '--' [ argument ]* ] ;
     *
     * @param  TokenIterator  $tokens
     * @param  OptionIterator $options
     * @param  bool           $optionsFirst [optional]
     *
     * @return Pattern[]
     */
    protected static function parseArgs(TokenIterator $tokens, OptionIterator $options, bool $optionsFirst = false): array {
        $parsed = [];

        while ($tokens->current() !== null) {               // @phpstan-ignore notIdentical.alwaysTrue (FIXME: refactor using Iterator->valid())
            if ($tokens->current() == '--') {
                while ($tokens->current() !== null) {       // @phpstan-ignore notIdentical.alwaysTrue (FIXME: refactor using Iterator->valid())
                    $parsed[] = new Argument(null, $tokens->move());
                }
                return $parsed;                             // @phpstan-ignore deadCode.unreachable (FIXME: refactor using Iterator->valid())
            }
            elseif (strStartsWith($tokens->current(), '--')) {
                $parsed = array_merge($parsed, static::parseLong($tokens, $options));
            }
            elseif (strStartsWith($tokens->current(), '-') && $tokens->current() != '-') {
                $parsed = array_merge($parsed, static::parseShort($tokens, $options));
            }
            elseif ($optionsFirst) {
                return array_merge($parsed, array_map(function($value) {
                    return new Argument(null, $value);
                }, $tokens->left()));
            }
            else {
                $parsed[] = new Argument(null, $tokens->move());
            }
        }
        return $parsed;                                     // @phpstan-ignore deadCode.unreachable (FIXME: refactor using Iterator->valid())
    }


    /**
     * @param  string $doc
     *
     * @return OptionIterator
     */
    protected static function parseDefaults(string $doc): OptionIterator {
        $defaults = [];
        foreach (static::parseSection('options:', $doc) as $section) {
            # FIXME corner case "bla: options: --foo"
            list (, $section) = explode(':', $section, 2);
            $splitTmp = array_slice(preg_split("/\n[ \t]*(-\S+?)/", "\n".$section, 0, PREG_SPLIT_DELIM_CAPTURE), 1);
            $split = [];
            for ($size=sizeof($splitTmp), $i=0; $i < $size; $i+=2) {
                $split[] = $splitTmp[$i].(isset($splitTmp[$i+1]) ? $splitTmp[$i+1] : '');
            }
            $options = [];
            foreach ($split as $value) {
                if ($value[0] == '-') {
                    $options[] = Option::parse($value);
                }
            }
            $defaults = array_merge($defaults, $options);
        }
        return new OptionIterator($defaults);
    }


    /**
     * @param  string         $source
     * @param  OptionIterator $options
     *
     * @return Required
     */
    protected static function parsePattern(string $source, OptionIterator $options): Required {
        $tokens = TokenIterator::fromPattern($source);
        $result = static::parseExpression($tokens, $options);
        if ($tokens->current() !== null) {                  // @phpstan-ignore notIdentical.alwaysTrue (FIXME: refactor using Iterator->valid())
            $error = $tokens->getErrorClass();
            throw new $error('Unexpected ending: '.join(' ', $tokens->left()));
        }
        return new Required($result);                       // @phpstan-ignore deadCode.unreachable (FIXME: refactor using Iterator->valid())
    }


    /**
     * @param  string $name
     * @param  string $source
     *
     * @return string[]
     */
    protected static function parseSection(string $name, string $source): array {
        $matches = null;
        $result = [];
        if (preg_match_all('/^([^\n]*'.$name.'[^\n]*\n?(?:[ \t].*?(?:\n|$))*)/im', $source, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $result[] = trim($match[0]);
            }
        }
        return $result;
    }


    /**
     * expr ::= seq ( '|' seq )* ;
     *
     * @param  TokenIterator  $tokens
     * @param  OptionIterator $options
     *
     * @return Either|Pattern[]
     */
    protected static function parseExpression(TokenIterator $tokens, OptionIterator $options) {
        $seq = static::parseSequence($tokens, $options);
        if ($tokens->current() !== '|') {
            return $seq;
        }
        $result = null;
        if (sizeof($seq) > 1) $result = [new Required($seq)];
        else                  $result = $seq;

        while ($tokens->current() === '|') {                // @phpstan-ignore while.alwaysTrue,identical.alwaysTrue (FIXME: refactor using Iterator->valid())
            $tokens->move();
            $seq = static::parseSequence($tokens, $options);
            if (sizeof($seq) > 1) $result[] = new Required($seq);
            else                  $result   = array_merge($result, $seq);
        }

        if (sizeof($result) > 1)                            // @phpstan-ignore deadCode.unreachable (FIXME: refactor using Iterator->valid())
            return new Either($result);
        return $result;
    }


    /**
     * seq ::= ( atom [ '...' ] )* ;
     *
     * @param  TokenIterator  $tokens
     * @param  OptionIterator $options
     *
     * @return Pattern[]
     */
    protected static function parseSequence(TokenIterator $tokens, OptionIterator $options): array {
        $result = [];
        $not = [null, '', ']', ')', '|'];
        while (!in_array($tokens->current(), $not, true)) {
            $atom = static::parseAtom($tokens, $options);
            if ($tokens->current() == '...') {
                $atom = [new OneOrMore($atom)];
                $tokens->move();
            }
            if ($atom) {
                $result = array_merge($result, $atom);
            }
        }
        return $result;
    }


    /**
     * shorts ::= '-' ( chars )* [ [ ' ' ] chars ] ;
     *
     * @param  TokenIterator  $tokens
     * @param  OptionIterator $options
     *
     * @return Option[]
     */
    protected static function parseShort(TokenIterator $tokens, OptionIterator $options): array {
        $token = $tokens->move();

        if (!isset($token) || strpos($token, '-') !== 0 || strpos($token, '--') === 0) {
            throw new \UnexpectedValueException("short token '$token' does not start with '-' or '--'");
        }

        $left = ltrim($token, '-');
        $parsed = [];

        while ($left !== '') {
            $short = '-'.$left[0];
            $left = substr($left, 1);
            $similar = [];
            foreach ($options as $o) {
                if ($o->short == $short) {
                    $similar[] = $o;
                }
            }

            $similarCnt = sizeof($similar);
            if ($similarCnt > 1) {
                $error = $tokens->getErrorClass();
                throw new $error($short.' is specified ambiguously '.$similarCnt.' times');
            }
            elseif ($similarCnt < 1) {
                $o = new Option($short, null, 0);
                $options[] = $o;
                if ($tokens->getErrorClass() == DocoptUserNotification::class) {
                    $o = new Option($short, null, 0, true);
                }
            }
            else {
                $o = new Option($short, $similar[0]->long, $similar[0]->argcount, $similar[0]->value);
                $value = null;
                if ($o->argcount != 0) {
                    if ($left === '') {
                        if ($tokens->current()===null || $tokens->current()=='--') {    // @phpstan-ignore identical.alwaysFalse (FIXME: refactor using Iterator->valid())
                            $error = $tokens->getErrorClass();
                            throw new $error($short.' requires an argument');
                        }
                        $value = $tokens->move();
                    }
                    else {
                        $value = $left;
                        $left = '';
                    }
                }
                if ($tokens->getErrorClass() == DocoptUserNotification::class) {
                    $o->value = isset($value) ? $value : true;
                }
            }
            $parsed[] = $o;
        }
        return $parsed;
    }


    /**
     * long ::= '--' chars [ ( ' ' | '=' ) chars ] ;
     *
     * @param  TokenIterator  $tokens
     * @param  OptionIterator $options
     *
     * @return Option[]
     */
    protected static function parseLong(TokenIterator $tokens, OptionIterator $options): array {
        $tokenError = $tokens->getErrorClass();
        $token      = (string) $tokens->move();
        $exploded   = explode('=', $token, 2);

        if (sizeof($exploded) == 2) {
            $long = $exploded[0];
            $eq = '=';
            $value = $exploded[1];
        }
        else {
            $long = $token;
            $eq = null;
            $value = null;
        }

        if (strpos($long, '--') !== 0) {
            throw new \UnexpectedValueException("Expected long option, found '$long'");
        }
        $similar = array_values(array_filter($options, function($o) use ($long) {
            return ($o->long && $o->long==$long);
        }));
        if ($tokenError==DocoptUserNotification::class && !$similar) {
            $similar = array_values(array_filter($options, function($o) use ($long) {
                return ($o->long && strpos($o->long, $long)===0);
            }));
        }
        /** @var ?Option $o */
        $o = null;

        if (!$similar) {
            $argcount = (int) ($eq=='=');
            $o = new Option(null, $long, $argcount);
            $options[] = $o;
            if ($tokenError == DocoptUserNotification::class) {
                $o = new Option(null, $long, $argcount, $argcount ? $value : true);
            }
        }
        elseif (sizeof($similar) > 1) {
            // might be simply specified ambiguously 2+ times?
            throw new $tokenError("$long is not a unique prefix: ".join(', ', array_map(function($o) {
                return $o->long;
            }, $similar)));
        }
        else {
            $o = new Option($similar[0]->short, $similar[0]->long, $similar[0]->argcount, $similar[0]->value);
            if ($o->argcount == 0) {
                if (isset($value)) throw new $tokenError("$o->long must not have an argument");
            }
            elseif ($value === null) {
                // @phpstan-ignore identical.alwaysFalse (FIXME: refactor using Iterator->valid())
                if ($tokens->current()===null || $tokens->current()=='--') throw new $tokenError("$o->long requires an argument");
                $value = $tokens->move();
            }
            if ($tokens->getErrorClass() == DocoptUserNotification::class) {
                $o->value = $value ?? true;
            }
        }
        return [$o];
    }


    /**
     * atom ::= '(' expr ')' | '[' expr ']' | 'options' | long | shorts | argument | command ;
     *
     * @param  TokenIterator  $tokens
     * @param  OptionIterator $options
     *
     * @return Pattern[]
     */
    protected static function parseAtom(TokenIterator $tokens, OptionIterator $options): array {
        $tokenError = $tokens->getErrorClass();
        $token = $tokens->current();
        $result = [];

        if ($token=='(' || $token=='[') {
            $tokens->move();

            /** @var ?array<array<class-string<Pattern>>> $index */
            static $index;
            $index ??= [
                '(' => [')', Required::class],
                '[' => [']', Optional::class],
            ];
            list($matching, $patternClass) = $index[$token];

            $result = new $patternClass(static::parseExpression($tokens, $options));
            if ($tokens->move() != $matching) throw new $tokenError("Unmatched \"$token\"");
            return [$result];
        }
        elseif ($token == 'options') {
            $tokens->move();
            return [new OptionsShortcut()];
        }
        elseif (strpos($token, '--')===0 && $token!='--') {
            return static::parseLong($tokens, $options);
        }
        elseif (strpos($token, '-')===0 && $token!='-' && $token!='--') {
            return static::parseShort($tokens, $options);
        }
        elseif ((strpos($token, '<')===0 && strEndsWith($token, '>')) || static::isUpperCase($token)) {
            return [new Argument($tokens->move())];
        }
        else {
            return [new Command($tokens->move())];
        }
    }


    /**
     * Whether all cased characters in the string are uppercase, and there is at least one of them.
     *
     * @param  string $string
     *
     * @return bool
     */
    protected static function isUpperCase(string $string): bool {
        return preg_match('/[A-Z]/', $string) && !preg_match('/[a-z]/', $string);
    }
}
