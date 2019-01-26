<?php
namespace rosasurfer\console\docopt;

use rosasurfer\core\Object;

use rosasurfer\console\docopt\exception\DocoptFormatError;
use rosasurfer\console\docopt\exception\UserSyntaxError;

use rosasurfer\console\docopt\pattern\Argument;
use rosasurfer\console\docopt\pattern\BranchPattern;
use rosasurfer\console\docopt\pattern\Command;
use rosasurfer\console\docopt\pattern\Either;
use rosasurfer\console\docopt\pattern\OneOrMore;
use rosasurfer\console\docopt\pattern\Option;
use rosasurfer\console\docopt\pattern\Optional;
use rosasurfer\console\docopt\pattern\OptionsShortcut;
use rosasurfer\console\docopt\pattern\Pattern;
use rosasurfer\console\docopt\pattern\Required;

use function rosasurfer\array_filter;
use function rosasurfer\array_merge;
use function rosasurfer\strEndsWith;


/**
 *
 */
class Handler extends Object {


    /** @var bool */
    protected $exit = true;

    /** @var bool */
    protected $exitFullUsage = false;

    /** @var bool */
    protected $help = true;

    /** @var bool */
    protected $optionsFirst = false;

    /** @var string */
    protected $version;


    /**
     * Constructor
     *
     * @param  mixed[] $options
     */
    public function __construct(array $options = []) {
        foreach ($options as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }


    /**
     * @param  string         $doc
     * @param  string|mixed[] $argv
     *
     * @return Response
     */
    public function handle($doc, $argv = null) {
        try {
            if (!isSet($argv) && isSet($_SERVER['argv']))
                $argv = array_slice($_SERVER['argv'], 1);

            $usageSections = self::parseSection('usage:', $doc);
            if (!$usageSections)            throw new DocoptFormatError('"usage:" (case-insensitive) not found.');
            if (sizeOf($usageSections) > 1) throw new DocoptFormatError('More than one "usage:" (case-insensitive).');
            $usage = $usageSections[0];

            // temp fix until python port provides solution
            UserSyntaxError::$usage = !$this->exitFullUsage ? $usage : $doc;

            $options = self::parseDefaults($doc);

            $formalUse = self::formalUsage($usage);
            $pattern = self::parsePattern($formalUse, $options);

            $argv = self::parseArgv(new TokenStream($argv), $options, $this->optionsFirst);

            $patternOptions = $pattern->flat([Option::class]);
            foreach ($pattern->flat([OptionsShortcut::class]) as $optionsShortcut) {
                $docOptions = self::parseDefaults($doc);
                $optionsShortcut->children = array_diff((array)$docOptions, $patternOptions);
            }

            $this->extras($this->help, $this->version, $argv, $doc);

            list($matched, $left, $collected) = $pattern->fix()->match($argv);
            if ($matched && !$left) {
                $result = [];
                foreach (array_merge($pattern->flat(), $collected) as $pattern) {
                    if ($name = $pattern->name()) {
                        $result[$name] = $pattern->value;
                    }
                }
                return new Response($result);
            }
            throw new UserSyntaxError();
        }
        catch (UserSyntaxError $ex) {
            $this->handleExit($ex);
            return new Response([], $ex->status, $ex->getMessage());
        }
    }


    /**
     *
     */
    protected function handleExit(UserSyntaxError $ex) {
        if ($this->exit) {
            echo $ex->getMessage().PHP_EOL;
            exit($ex->status);
        }
    }


    /**
     * @param  bool      $help
     * @param  string    $version
     * @param  Pattern[] $argv
     * @param  string    $doc
     */
    protected function extras($help, $version, $argv, $doc) {
        $ofound = $vfound = false;

        foreach ($argv as $o) {
            if ($o->value) {
                if ($o->name()=='-h' || $o->name()=='--help') {
                    $ofound = true;
                }
                if ($o->name()=='--version') {
                    $vfound = true;
                }
            }
        }
        if ($help && $ofound) {
            UserSyntaxError::$usage = null;
            throw new UserSyntaxError($doc, 0);
        }
        if ($version && $vfound) {
            UserSyntaxError::$usage = null;
            throw new UserSyntaxError($version, 0);
        }
    }


    /**
     * @param  string $section
     *
     * @return string
     */
    public static function formalUsage($section) {
        list (, $section) = explode(':', $section, 2);  # drop "usage:"
        $pu = preg_split('/\s+/', trim($section));

        $ret = [];
        foreach (array_slice($pu, 1) as $s) {
            if ($s == $pu[0]) $ret[] = ') | (';
            else              $ret[] = $s;
        }
        return '( '.join(' ', $ret).' )';
    }


    /**
     * Parse command-line argument vector.
     *
     * If options_first: argv ::= [ long | shorts ]* [ argument ]* [ '--' [ argument ]* ] ;
     * else:             argv ::= [ long | shorts | argument ]* [ '--' [ argument ]* ] ;
     *
     * @param  bool $optionsFirst
     *
     * @return Pattern[]
     */
    public static function parseArgv(TokenStream $tokens, \ArrayIterator $options, $optionsFirst = false) {
        $parsed = [];

        while ($tokens->current() !== null) {
            if ($tokens->current() == '--') {
                while ($tokens->current() !== null) {
                    $parsed[] = new Argument(null, $tokens->move());
                }
                return $parsed;
            }
            elseif (strpos($tokens->current(), '--')===0) {
                $parsed = array_merge($parsed, self::parseLong($tokens, $options));
            }
            elseif (strpos($tokens->current(), '-')===0 && $tokens->current() != '-') {
                $parsed = array_merge($parsed, self::parseShort($tokens, $options));
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
        return $parsed;
    }


    /**
     * @param  string $doc
     *
     * @return \ArrayIterator
     */
    public static function parseDefaults($doc) {
        $defaults = [];
        foreach (self::parseSection('options:', $doc) as $s) {
            # FIXME corner case "bla: options: --foo"
            list (, $s) = explode(':', $s, 2);
            $splitTmp = array_slice(preg_split("@\n[ \t]*(-\S+?)@", "\n".$s, null, PREG_SPLIT_DELIM_CAPTURE), 1);
            $split = [];
            for ($cnt = count($splitTmp), $i=0; $i < $cnt; $i+=2) {
                $split[] = $splitTmp[$i] . (isset($splitTmp[$i+1]) ? $splitTmp[$i+1] : '');
            }
            $options = [];
            foreach ($split as $s) {
                if (strpos($s, '-') === 0) {
                    $options[] = Option::parse($s);
                }
            }
            $defaults = array_merge($defaults, $options);
        }
        return new \ArrayIterator($defaults);
    }


    /**
     * @param  string $source
     *
     * @return Required
     */
    public static function parsePattern($source, \ArrayIterator $options) {
        $tokens = TokenStream::fromPattern($source);
        $result = self::parseExpression($tokens, $options);
        if ($tokens->current() != null) {
            $tokens->throwException('unexpected ending: '.join(' ', $tokens->left()));
        }
        return new Required($result);
    }


    /**
     * @param  string $name
     * @param  string $source
     *
     * @return string[]
     */
    public static function parseSection($name, $source) {
        $ret = [];
        if (preg_match_all('@^([^\n]*'.$name.'[^\n]*\n?(?:[ \t].*?(?:\n|$))*)@im', $source, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $ret[] = trim($match[0]);
            }
        }
        return $ret;
    }


    /**
     * expr ::= seq ( '|' seq )* ;
     *
     * @return Either|Pattern[]
     */
    private static function parseExpression(TokenStream $tokens, \ArrayIterator $options) {
        $seq = self::parseSequence($tokens, $options);
        if ($tokens->current() != '|')
            return $seq;

        $result = null;
        if (count($seq) > 1) $result = [new Required($seq)];
        else                 $result = $seq;

        while ($tokens->current() == '|') {
            $tokens->move();
            $seq = self::parseSequence($tokens, $options);
            if (count($seq) > 1) {
                $result[] = new Required($seq);
            }
            else {
                $result = array_merge($result, $seq);
            }
        }

        if (count($result) > 1)
            return new Either($result);
        return $result;
    }


    /**
     * seq ::= ( atom [ '...' ] )* ;
     *
     * @return Pattern[]
     */
    private static function parseSequence(TokenStream $tokens, \ArrayIterator $options) {
        $result = [];
        $not = [null, '', ']', ')', '|'];
        while (!in_array($tokens->current(), $not, true)) {
            $atom = self::parseAtom($tokens, $options);
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
     * @return Option[]
     */
    private static function parseShort(TokenStream $tokens, \ArrayIterator $options) {
        $token = $tokens->move();

        if (strpos($token, '-') !== 0 || strpos($token, '--') === 0)
            throw new \UnexpectedValueException("short token '$token' does not start with '-' or '--'");

        $left = ltrim($token, '-');
        $parsed = [];
        while ($left != '') {
            $short = '-'.$left[0];
            $left = substr($left, 1);
            $similar = [];
            foreach ($options as $o) {
                if ($o->short == $short) {
                    $similar[] = $o;
                }
            }

            $similarCnt = count($similar);
            if ($similarCnt > 1) {
                $tokens->throwException($short.' is specified ambiguously '.$similarCnt.' times');
            }
            elseif ($similarCnt < 1) {
                $o = new Option($short, null, 0);
                $options[] = $o;
                if ($tokens->errorClass == UserSyntaxError::class) {
                    $o = new Option($short, null, 0, true);
                }
            }
            else {
                $o = new Option($short, $similar[0]->long, $similar[0]->argcount, $similar[0]->value);
                $value = null;
                if ($o->argcount != 0) {
                    if ($left == '') {
                        if ($tokens->current() === null || $tokens->current() == '--') {
                            $tokens->throwException($short.' requires argument');
                        }
                        $value = $tokens->move();
                    }
                    else {
                        $value = $left;
                        $left = '';
                    }
                }
                if ($tokens->errorClass == UserSyntaxError::class) {
                    $o->value = $value!==null ? $value : true;
                }
            }
            $parsed[] = $o;
        }
        return $parsed;
    }


    /**
     * long ::= '--' chars [ ( ' ' | '=' ) chars ] ;
     *
     * @return Option[]
     */
    private static function parseLong(TokenStream $tokens, \ArrayIterator $options) {
        $token = $tokens->move();
        $exploded = explode('=', $token, 2);
        if (count($exploded) == 2) {
            $long = $exploded[0];
            $eq = '=';
            $value = $exploded[1];
        }
        else {
            $long = $token;
            $eq = null;
            $value = null;
        }

        if (strpos($long, '--') !== 0)
            throw new \UnexpectedValueException("Expected long option, found '$long'");

        $value = (!$eq && !$value) ? null : $value;

        $similar = array_values(array_filter($options, function($o) use ($long) {
            return ($o->long && $o->long==$long);
        }));
        if ($tokens->errorClass==UserSyntaxError::class && !$similar) {
            $similar = array_values(array_filter($options, function($o) use ($long) {
                return ($o->long && strPos($o->long, $long)===0);
            }));
        }
        /** @var Option $o */
        $o = null;

        if (!$similar) {
            $argcount = (int) ($eq=='=');
            $o = new Option(null, $long, $argcount);
            $options[] = $o;
            if ($tokens->errorClass == UserSyntaxError::class) {
                $o = new Option(null, $long, $argcount, $argcount ? $value : true);
            }
        }
        elseif (sizeOf($similar) > 1) {
            // might be simply specified ambiguously 2+ times?
            $tokens->throwException($long.' is not a unique prefix: '.join(', ', array_map(function($o) {
                return $o->long;
            }, $similar)));
        }
        else {
            $o = new Option($similar[0]->short, $similar[0]->long, $similar[0]->argcount, $similar[0]->value);
            if ($o->argcount == 0) {
                if (isSet($value)) $tokens->throwException($o->long.' must not have an argument');
            }
            else {
                if ($value === null) {
                    if ($tokens->current()===null || $tokens->current()=='--') {
                        $tokens->throwException($o->long.' requires argument');
                    }
                    $value = $tokens->move();
                }
            }
            if ($tokens->errorClass == UserSyntaxError::class) {
                $o->value = isSet($value) ? $value : true;
            }
        }
        return [$o];
    }


    /**
     * atom ::= '(' expr ')' | '[' expr ']' | 'options' | long | shorts | argument | command ;
     *
     * @return Pattern[]
     */
    private static function parseAtom(TokenStream $tokens, \ArrayIterator $options) {
        $token = $tokens->current();
        $result = [];

        if ($token=='(' || $token=='[') {
            $tokens->move();

            static $index; !$index && $index = [
                '(' => [')', Required::class],
                '[' => [']', Optional::class],
            ];
            list ($matching, $pattern) = $index[$token];

            $result = new $pattern(self::parseExpression($tokens, $options));
            if ($tokens->move() != $matching)
                $tokens->throwException("Unmatched '$token'");
            return [$result];
        }
        elseif ($token == 'options') {
            $tokens->move();
            return [new OptionsShortcut()];
        }
        elseif (strpos($token, '--') === 0 && $token != '--') {
            return self::parseLong($tokens, $options);
        }
        elseif (strpos($token, '-') === 0 && $token != '-' && $token != '--') {
            return self::parseShort($tokens, $options);
        }
        elseif (strpos($token, '<') === 0 && strEndsWith($token, '>') || self::isUpper($token)) {
            return [new Argument($tokens->move())];
        }
        else {
            return [new Command($tokens->move())];
        }
    }


    /**
     * Expand pattern into an (almost) equivalent one, but with single Either.
     *
     * Example: ((-a | -b) (-c | -d)) => (-a -c | -a -d | -b -c | -b -d)
     * Quirks: [-a] => (-a), (-a...) => (-a -a)
     *
     * @param  Pattern $pattern
     *
     * @return Either
     */
    public static function transform(Pattern $pattern) {
        $result = [];
        $groups = [[$pattern]];

        while ($groups) {
            $children = array_shift($groups);
            $hasBranchPattern = false;
            foreach ($children as $c) {
                if ($c instanceof BranchPattern) {
                    $hasBranchPattern = true;
                    break;
                }
            }
            if ($hasBranchPattern) {
                /** @var BranchPattern $child */
                $child = null;
                foreach ($children as $key => $currentChild) {
                    if ($currentChild instanceof BranchPattern) {
                        $child = $currentChild;
                        unset($children[$key]);
                        break;
                    }
                }
                if ($child instanceof Either) {
                    foreach ($child->children as $c) {
                        $groups[] = array_merge([$c], $children);
                    }
                }
                else if ($child instanceof OneOrMore) {
                    $groups[] = array_merge($child->children, $child->children, $children);
                }
                else {
                    $groups[] = array_merge($child->children, $children);
                }
            }
            else {
                $result[] = $children;
            }
        }

        $rs = [];
        foreach ($result as $e) {
            $rs[] = new Required($e);
        }
        return new Either($rs);
    }


    /**
     * Return true if all cased characters in the string are uppercase and there is
     * at least one cased character, false otherwise.
     * Python method with no knowrn equivalent in PHP.
     *
     * @param  string $string
     *
     * @return bool
     */
    private static function isUpper($string) {
        return preg_match('/[A-Z]/', $string) && !preg_match('/[a-z]/', $string);
    }
}
