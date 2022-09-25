<?php
namespace rosasurfer\console\docopt\pattern;

use rosasurfer\console\docopt\SingleMatch;


/**
 * Argument
 */
class Argument extends LeafPattern {


    /**
     * @param  Pattern[] $left
     *
     * @return SingleMatch
     */
    public function singleMatch(array $left) {
        foreach ($left as $n=>$pattern) {
            if ($pattern instanceof Argument) {
                return new SingleMatch($n, new Argument($this->name(), $pattern->value));
            }
        }
        return new SingleMatch(null, null);
    }


    /**
     * @param  string $source
     *
     * @return self
     */
    public static function parse($source) {
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
