<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;

use rosasurfer\ministruts\console\docopt\SingleMatch;


/**
 * Argument
 */
class Argument extends LeafPattern {


    /**
     * {@inheritDoc}
     */
    public function singleMatch(array $left): SingleMatch {
        foreach ($left as $i => $pattern) {
            if ($pattern instanceof Argument) {
                return new SingleMatch($i, new Argument($this->name(), $pattern->value));
            }
        }
        return new SingleMatch(null, null);
    }


    /**
     * @param  string $source
     *
     * @return Argument
     */
    public static function parse(string $source): self {
        $name = $value = $matches = null;

        if (preg_match_all('@(<\S*?'.'>)@', $source, $matches)) {
            $name = $matches[0][0];
        }
        if (preg_match_all('@\[default: (.*)\]@i', $source, $matches)) {
            $value = $matches[0][1];
        }
        return new static($name, $value);
    }
}
