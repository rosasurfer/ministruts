<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;

use function rosasurfer\ministruts\array_merge;


/**
 * BranchPattern
 */
abstract class BranchPattern extends Pattern {


    /**
     * @param  Pattern|Pattern[]|null $children [optional]
     */
    public function __construct($children = null) {
        if (!$children) {
            $children = [];
        }
        elseif ($children instanceof Pattern) {
            $children = func_get_args();
        }
        foreach ($children as $child) {
            $this->children[] = $child;
        }
    }


    /**
     * @param  string[] $types [optional]
     *
     * @return Pattern[]
     */
    public function flat(array $types = []) {
        if (in_array(get_class($this), $types))
            return [$this];
        $flat = [];
        foreach ($this->children as $c) {
            $flat = array_merge($flat, $c->flat($types));
        }
        return $flat;
    }


    /**
     * @param  Pattern[] $left
     * @param  Pattern[] $collected [optional]
     *
     * @return array
     */
    public function match(array $left, array $collected = []) {
        throw new \RuntimeException("Unsupported");
    }
}
