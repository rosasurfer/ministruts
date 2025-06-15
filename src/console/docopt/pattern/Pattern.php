<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;

use Traversable;
use UnexpectedValueException;

use rosasurfer\ministruts\core\CObject;

use function rosasurfer\ministruts\array_merge;
use function rosasurfer\ministruts\preg_split;

/**
 * Pattern
 */
abstract class Pattern extends CObject {

    /** @var ?string */
    protected ?string $name = null;

    /** @var mixed */
    public $value = null;

    /** @var Pattern[] */
    public array $children = [];


    /**
     * @param  string[] $types [optional]
     *
     * @return Pattern[]
     */
    abstract function flat(array $types = []): array;


    /**
     * @param  Pattern[] $left
     * @param  Pattern[] $collected [optional]
     *
     * @return array{bool, Pattern[], Pattern[]}
     */
    abstract function match(array $left, array $collected = []): array;


    /**
     * @return ?string
     */
    public function name(): ?string {
        return $this->name;
    }


    /**
     * @return $this
     */
    public function fix(): self {
        $this->fixIdentities();
        $this->fixRepeatingArguments();
        return $this;
    }


    /**
     * Make pattern-tree tips point to same object if they are equal.
     *
     * @param Pattern[]|null $unique [optional]
     *
     * @return $this
     */
    public function fixIdentities(?array $unique = null): self {
        if ($this->children) {
            if (!isset($unique))
                $unique = array_unique($this->flat());

            foreach ($this->children as $i => $child) {
                if (!$child instanceof BranchPattern) {
                    if (!\in_array($child, $unique, false))         // Not sure if this is a true substitute for 'assert child in unique'
                        throw new UnexpectedValueException();
                    $this->children[$i] = $unique[array_search($child, $unique, false)];
                }
                else {
                    $child->fixIdentities($unique);
                }
            }
        }
        return $this;
    }


    /**
     * Fix elements that should accumulate/increment values.
     *
     * @return $this
     */
    public function fixRepeatingArguments(): self {
        $either = [];
        foreach (static::transform($this)->children as $child) {
            $either[] = $child->children;
        }

        foreach ($either as $case) {
            $counts = [];
            foreach ($case as $child) {
                $ser = serialize($child);
                if (!isset($counts[$ser])) {
                    $counts[$ser] = ['cnt'=>0, 'items'=>[]];
                }

                $counts[$ser]['cnt']++;
                $counts[$ser]['items'][] = $child;
            }

            $repeatedCases = [];
            foreach ($counts as $child) {
                if ($child['cnt'] > 1) {
                    $repeatedCases = array_merge($repeatedCases, $child['items']);
                }
            }

            foreach ($repeatedCases as $e) {
                if ($e instanceof Argument || ($e instanceof Option && $e->argcount)) {
                    if (!$e->value) {
                        $e->value = [];
                    }
                    elseif (!is_array($e->value) && !$e->value instanceof Traversable) {
                        $e->value = preg_split('/\s+/', $e->value);
                    }
                }
                if ($e instanceof Command || ($e instanceof Option && !$e->argcount)) {
                    $e->value = 0;
                }
            }
        }
        return $this;
    }


    /**
     * Expand pattern into an (almost) equivalent one, but with single {@link Either}.
     *
     * Example: ((-a | -b) (-c | -d)) => (-a -c | -a -d | -b -c | -b -d)
     * Quirks: [-a] => (-a), (-a...) => (-a -a)
     *
     * @param  Pattern $pattern
     *
     * @return Either
     */
    protected static function transform(self $pattern): Either {
        $result = [];
        $groups = [[$pattern]];

        while ($groups) {
            $children = array_shift($groups);
            $branchChild = null;

            foreach ($children as $key => $child) {
                if ($child instanceof BranchPattern) {
                    $branchChild = $child;
                    unset($children[$key]);
                    break;
                }
            }

            if ($branchChild) {
                if ($branchChild instanceof Either) {
                    foreach ($branchChild->children as $child) {
                        $groups[] = array_merge([$child], $children);
                    }
                }
                elseif ($branchChild instanceof OneOrMore) {
                    $groups[] = array_merge($branchChild->children, $branchChild->children, $children);
                }
                else {
                    $groups[] = array_merge($branchChild->children, $children);
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
     * {@inheritDoc}
     */
    public function __toString(): string {
        return serialize($this);
    }
}
