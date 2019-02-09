<?php
namespace rosasurfer\console\docopt\pattern;

use rosasurfer\console\docopt\SingleMatch;

use function rosasurfer\array_filter;
use function rosasurfer\array_merge;


/**
 *
 */
abstract class LeafPattern extends Pattern {


    /**
     * @param  string|null $name
     * @param  mixed       $value [optional]
     */
    public function __construct($name, $value = null) {
        $this->name = $name;
        $this->value = $value;
    }


    /**
     * @param  Pattern[] $left
     *
     * @return SingleMatch
     */
    abstract public function singleMatch($left);


    /**
     * @param  string[] $types [optional]
     *
     * @return Pattern[]
     */
    public function flat(array $types = []) {
        if (!$types || in_array(get_class($this), $types))
            return [$this];
        return [];
    }


    /**
     * @param  Pattern[] $left
     * @param  Pattern[] $collected [optional]
     *
     * @return mixed[]
     */
    public function match(array $left, array $collected = []) {
        list ($pos, $match) = $this->singleMatch($left)->toArray();
        if (!$match)
            return [false, $left, $collected];

        $left_ = $left;
        unset($left_[$pos]);
        $left_ = array_values($left_);

        $name = $this->name();
        $sameName = array_values(array_filter($collected, function(Pattern $pattern) use ($name) {
            return ($pattern->name() == $name);
        }));

        if (is_int($this->value) || is_array($this->value) || $this->value instanceof \Traversable) {
            if (is_int($this->value)) {
                $increment = 1;
            }
            else {
                $increment = is_string($match->value) ? [$match->value] : $match->value;
            }

            if (!$sameName) {
                $match->value = $increment;
                return [true, $left_, array_merge($collected, [$match])];
            }
            if (is_array($increment) || $increment instanceof \Traversable) {
                $sameName[0]->value = array_merge($sameName[0]->value, $increment);
            }
            else {
                $sameName[0]->value += $increment;
            }
            return [true, $left_, $collected];
        }
        return [true, $left_, array_merge($collected, [$match])];
    }
}
