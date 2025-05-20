<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;

use Traversable;

use rosasurfer\ministruts\console\docopt\SingleMatch;

use function rosasurfer\ministruts\array_filter;
use function rosasurfer\ministruts\array_merge;

/**
 * LeafPattern
 */
abstract class LeafPattern extends Pattern {

    /**
     * @param  ?string $name
     * @param  mixed   $value [optional]
     */
    public function __construct(?string $name, $value = null) {
        $this->name = $name;
        $this->value = $value;
    }


    /**
     * @param  Pattern[] $left
     *
     * @return SingleMatch
     */
    abstract public function singleMatch(array $left): SingleMatch;


    /**
     * {@inheritDoc}
     */
    public function flat(array $types = []): array {
        if (!$types || in_array(static::class, $types)) {
            return [$this];
        }
        return [];
    }


    /**
     * {@inheritDoc}
     */
    public function match(array $left, array $collected = []): array {
        [$pos, $match] = $this->singleMatch($left)->toArray();
        if (!$match) {
            return [false, $left, $collected];
        }

        $left_ = $left;
        unset($left_[$pos]);
        $left_ = array_values($left_);

        $name = $this->name();
        $sameName = array_values(array_filter($collected, function(Pattern $pattern) use ($name) {
            return $pattern->name() == $name;
        }));

        if (is_int($this->value) || is_array($this->value) || $this->value instanceof Traversable) {
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
            if (is_array($increment) || $increment instanceof Traversable) {
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
