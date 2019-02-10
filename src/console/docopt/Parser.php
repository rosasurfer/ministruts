<?php
namespace rosasurfer\console\docopt;

use rosasurfer\core\Object;

use rosasurfer\console\docopt\exception\DocoptFormatError;
use rosasurfer\console\docopt\exception\UserNotification;
use rosasurfer\console\docopt\pattern\Argument;
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
use function rosasurfer\echoPre;
use function rosasurfer\strEndsWith;
use function rosasurfer\strStartsWith;


/**
 * Parser
 *
 * A command line argument parser that will make you smile.
 */
class Parser extends Object {


    /** @var bool */
    protected $optionsFirst = false;

    /** @var bool */
    protected $help = true;

    /** @var bool */
    protected $exit = true;

    /** @var bool */
    protected $exitFullUsage = false;

    /** @var string - help text displayed with every parser generated output */
    protected $autoHelp;

    /** @var string */
    protected $version;


    /**
     * Constructor
     *
     * Create a new docopt command line argument parser.
     *
     * @param  array $options [optional]
     */
    public function __construct(array $options = []) {
        foreach ($options as $name => $value) {
            if (property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }


    /**
     * Parse command line arguments and match them against the specified {@link http://docopt.org} syntax definition.
     *
     * @param  string         $doc
     * @param  string|mixed[] $args [optional]
     *
     * @return Result
     */
    public function parse($doc, $args = null) {
        try {
            if (!isset($args) && isset($_SERVER['argv']))
                $args = array_slice($_SERVER['argv'], 1);

            $usage = static::parseSection('usage:', $doc);
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
                return new Result($result);
            }
            throw new UserNotification();
        }
        catch (UserNotification $ex) {
            $this->handleExit($ex);
            return new Result([], $ex->status, $ex->addMessage($this->autoHelp)->getMessage());
        }
    }


    /**
     * @param  bool      $help
     * @param  string    $version
     * @param  Pattern[] $argv
     * @param  string    $doc
     */
    protected function handleSpecials($help, $version, $argv, $doc) {
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
            throw new UserNotification($doc, 0);
        }
        if ($version && $vfound) {
            $this->autoHelp = null;
            throw new UserNotification($version, 0);
        }
    }


    /**
     * @param  UserNotification $exception
     */
    protected function handleExit(UserNotification $exception) {
        if ($this->exit) {
            echoPre($exception->addMessage($this->autoHelp)->getMessage());
            exit($exception->status);
        }
    }


    /**
     * @param  string $section
     *
     * @return string
     */
    protected static function formalUsage($section) {
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
     * Parse arguments.
     *
     * If options_first: argv ::= [ long | shorts ]* [ argument ]* [ '--' [ argument ]* ] ;
     * else:             argv ::= [ long | shorts | argument ]* [ '--' [ argument ]* ] ;
     *
     * @param  TokenIterator  $tokens
     * @param  \ArrayIterator $options
     * @param  bool           $optionsFirst [optional]
     *
     * @return Pattern[]
     */
    protected static function parseArgs(TokenIterator $tokens, \ArrayIterator $options, $optionsFirst = false) {
        $parsed = [];

        while ($tokens->current() !== null) {
            if ($tokens->current() == '--') {
                while ($tokens->current() !== null) {
                    $parsed[] = new Argument(null, $tokens->move());
                }
                return $parsed;
            }
            elseif (strStartsWith($tokens->current(), '--')) {
                $parsed = array_merge($parsed, static::parseLong($tokens, $options));
            }
            elseif (strStartsWith($tokens->current(), '-') && $tokens->current()!='-') {
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
        return $parsed;
    }


    /**
     * @param  string $doc
     *
     * @return \ArrayIterator
     */
    protected static function parseDefaults($doc) {
        $defaults = [];
        foreach (static::parseSection('options:', $doc) as $section) {
            # FIXME corner case "bla: options: --foo"
            list (, $section) = explode(':', $section, 2);
            $splitTmp = array_slice(preg_split("/\n[ \t]*(-\S+?)/", "\n".$section, null, PREG_SPLIT_DELIM_CAPTURE), 1);
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
        return new \ArrayIterator($defaults);
    }


    /**
     * @param  string         $source
     * @param  \ArrayIterator $options
     *
     * @return Required
     */
    protected static function parsePattern($source, \ArrayIterator $options) {
        $tokens = TokenIterator::fromPattern($source);
        $result = static::parseExpression($tokens, $options);
        if ($tokens->current() !== null) {
            $error = $tokens->getTokenError();
            throw new $error('Unexpected ending: '.join(' ', $tokens->left()));
        }
        return new Required($result);
    }


    /**
     * @param  string $name
     * @param  string $source
     *
     * @return string[]
     */
    protected static function parseSection($name, $source) {
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
     * @param  \ArrayIterator $options
     *
     * @return Either|Pattern[]
     */
    protected static function parseExpression(TokenIterator $tokens, \ArrayIterator $options) {
        $seq = static::parseSequence($tokens, $options);
        if ($tokens->current() != '|')
            return $seq;

        $result = null;
        if (sizeof($seq) > 1) $result = [new Required($seq)];
        else                  $result = $seq;

        while ($tokens->current() == '|') {
            $tokens->move();
            $seq = static::parseSequence($tokens, $options);
            if (sizeof($seq) > 1) $result[] = new Required($seq);
            else                  $result   = array_merge($result, $seq);
        }

        if (sizeof($result) > 1)
            return new Either($result);
        return $result;
    }


    /**
     * seq ::= ( atom [ '...' ] )* ;
     *
     * @param  TokenIterator  $tokens
     * @param  \ArrayIterator $options
     *
     * @return Pattern[]
     */
    protected static function parseSequence(TokenIterator $tokens, \ArrayIterator $options) {
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
     * @param  \ArrayIterator $options
     *
     * @return Option[]
     */
    protected static function parseShort(TokenIterator $tokens, \ArrayIterator $options) {
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

            $similarCnt = sizeof($similar);
            if ($similarCnt > 1) {
                $error = $tokens->getTokenError();
                throw new $error($short.' is specified ambiguously '.$similarCnt.' times');
            }
            elseif ($similarCnt < 1) {
                $o = new Option($short, null, 0);
                $options[] = $o;
                if ($tokens->getTokenError() == UserNotification::class) {
                    $o = new Option($short, null, 0, true);
                }
            }
            else {
                $o = new Option($short, $similar[0]->long, $similar[0]->argcount, $similar[0]->value);
                $value = null;
                if ($o->argcount != 0) {
                    if ($left == '') {
                        if ($tokens->current()===null || $tokens->current()=='--') {
                            $error = $tokens->getTokenError();
                            throw new $error($short.' requires argument');
                        }
                        $value = $tokens->move();
                    }
                    else {
                        $value = $left;
                        $left = '';
                    }
                }
                if ($tokens->getTokenError() == UserNotification::class) {
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
     * @param  \ArrayIterator $options
     *
     * @return Option[]
     */
    protected static function parseLong(TokenIterator $tokens, \ArrayIterator $options) {
        $tokenError = $tokens->getTokenError();
        $token      = $tokens->move();
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

        if (strpos($long, '--') !== 0)
            throw new \UnexpectedValueException("Expected long option, found '$long'");

        $value = (!$eq && !$value) ? null : $value;

        $similar = array_values(array_filter($options, function($o) use ($long) {
            return ($o->long && $o->long==$long);
        }));
        if ($tokenError==UserNotification::class && !$similar) {
            $similar = array_values(array_filter($options, function($o) use ($long) {
                return ($o->long && strpos($o->long, $long)===0);
            }));
        }
        /** @var Option $o */
        $o = null;

        if (!$similar) {
            $argcount = (int) ($eq=='=');
            $o = new Option(null, $long, $argcount);
            $options[] = $o;
            if ($tokenError == UserNotification::class) {
                $o = new Option(null, $long, $argcount, $argcount ? $value : true);
            }
        }
        elseif (sizeof($similar) > 1) {
            // might be simply specified ambiguously 2+ times?
            throw new $tokenError($long.' is not a unique prefix: '.join(', ', array_map(function($o) {
                return $o->long;
            }, $similar)));
        }
        else {
            $o = new Option($similar[0]->short, $similar[0]->long, $similar[0]->argcount, $similar[0]->value);
            if ($o->argcount == 0) {
                if (isset($value)) throw new $tokenError($o->long.' must not have an argument');
            }
            else if ($value === null) {
                if ($tokens->current()===null || $tokens->current()=='--')
                    throw new $tokenError($o->long.' requires argument');
                $value = $tokens->move();
            }
            if ($tokens->getTokenError() == UserNotification::class) {
                $o->value = isset($value) ? $value : true;
            }
        }
        return [$o];
    }


    /**
     * atom ::= '(' expr ')' | '[' expr ']' | 'options' | long | shorts | argument | command ;
     *
     * @param  TokenIterator  $tokens
     * @param  \ArrayIterator $options
     *
     * @return Pattern[]
     */
    protected static function parseAtom(TokenIterator $tokens, \ArrayIterator $options) {
        $tokenError = $tokens->getTokenError();
        $token      = $tokens->current();
        $result     = [];

        if ($token=='(' || $token=='[') {
            $tokens->move();

            static $index; !$index && $index = [
                '(' => [')', Required::class],
                '[' => [']', Optional::class],
            ];
            list ($matching, $patternClass) = $index[$token];

            $result = new $patternClass(static::parseExpression($tokens, $options));
            if ($tokens->move() != $matching)
                throw new $tokenError('Unmatched "'.$token.'"');
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
    protected static function isUpperCase($string) {
        return preg_match('/[A-Z]/', $string) && !preg_match('/[a-z]/', $string);
    }
}
