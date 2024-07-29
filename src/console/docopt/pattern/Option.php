<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;

use rosasurfer\ministruts\console\docopt\SingleMatch;


/**
 * Option
 */
class Option extends LeafPattern {


    /** @var ?string */
    public $short = null;

    /** @var ?string */
    public $long = null;

    /** @var int */
    public $argcount;


    /**
     * @param  ?string          $short    [optional]
     * @param  ?string          $long     [optional]
     * @param  int              $argcount [optional]
     * @param  bool|string|null $value    [optional]
     */
    public function __construct($short=null, $long=null, $argcount=0, $value=false) {
        if ($argcount > 1) throw new \InvalidArgumentException();

        $this->short    = $short;
        $this->long     = $long;
        $this->argcount = $argcount;

        parent::__construct($this->name(), ($argcount && $value===false) ? null : $value);
    }


    /**
     * @param  string $optionDescription
     *
     * @return Option
     */
    public static function parse($optionDescription) {
        $short    = null;
        $long     = null;
        $argcount = 0;
        $value    = false;

        $exp = explode('  ', trim($optionDescription), 2);
        $options = $exp[0];
        $description = isset($exp[1]) ? $exp[1] : '';

        $options = str_replace(',', ' ', str_replace('=', ' ', $options));
        foreach (preg_split('/\s+/', $options) as $s) {
            if (strpos($s, '--')===0) {
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
            if (preg_match('@\[default: (.*)\]@i', $description, $match))
                $value = $match[1];
        }
        return new static($short, $long, $argcount, $value);
    }


    /**
     * @param  Pattern[] $left
     *
     * @return SingleMatch
     */
    public function singleMatch(array $left) {
        foreach ($left as $n => $pattern) {
            if ($this->name() == $pattern->name())
                return new SingleMatch($n, $pattern);
        }
        return new SingleMatch(null, null);
    }


    /**
     * @return string
     */
    public function name() {
        return $this->long ?: $this->short;
    }
}
