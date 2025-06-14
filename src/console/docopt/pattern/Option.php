<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;

use InvalidArgumentException;

use rosasurfer\ministruts\console\docopt\SingleMatch;

use function rosasurfer\ministruts\preg_match;
use function rosasurfer\ministruts\preg_split;
use function rosasurfer\ministruts\strStartsWith;

/**
 * Option
 */
class Option extends LeafPattern {

    /** @var ?string */
    public ?string $short = null;

    /** @var ?string */
    public ?string $long = null;

    /** @var int */
    public int $argcount;


    /**
     * @param  ?string          $short    [optional]
     * @param  ?string          $long     [optional]
     * @param  int              $argcount [optional]
     * @param  bool|string|null $value    [optional]
     */
    public function __construct(?string $short=null, ?string $long=null, int $argcount=0, $value=false) {
        if (!isset($short) && !isset($long)) throw new InvalidArgumentException('invalid arguments $short/$long (one must be set)');
        if ($argcount > 1)                   throw new InvalidArgumentException("invalid argument \$argcount: $argcount (must be 0 or 1)");

        $this->short    = $short;
        $this->long     = $long;
        $this->argcount = $argcount;

        parent::__construct($this->name(), $argcount && $value===false ? null : $value);
    }


    /**
     * @param  string $optionDescription
     *
     * @return Option
     */
    public static function parse(string $optionDescription): self {
        $short    = null;
        $long     = null;
        $argcount = 0;
        $value    = false;

        $exp = explode('  ', trim($optionDescription), 2);
        $options = $exp[0];
        $description = $exp[1] ?? '';

        $options = str_replace(',', ' ', str_replace('=', ' ', $options));

        foreach (preg_split('/\s+/', $options) as $s) {
            if (strStartsWith($s, '--')) {
                $long = $s;
            }
            elseif ($s && $s[0] == '-') {
                $short = $s;
            }
            else {
                $argcount = 1;
            }
        }

        if ($argcount) {
            $value = $match = null;
            if (preg_match('@\[default: (.*)\]@i', $description, $match)) {
                $value = $match[1];
            }
        }
        return new static($short, $long, $argcount, $value);
    }


    /**
     * {@inheritDoc}
     */
    public function singleMatch(array $left): SingleMatch {
        foreach ($left as $i => $pattern) {
            if ($this->name() == $pattern->name()) {
                return new SingleMatch($i, $pattern);
            }
        }
        return new SingleMatch(null, null);
    }


    /**
     * @return string
     */
    public function name(): string {
        return $this->long ?? $this->short;         // @phpstan-ignore return.type (one of them is always set)
    }
}
