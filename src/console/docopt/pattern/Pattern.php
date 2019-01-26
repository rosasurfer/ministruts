<?php
namespace rosasurfer\console\docopt\pattern;

use rosasurfer\core\Object;
use rosasurfer\console\docopt\Handler;

use function rosasurfer\array_merge;


/**
 *
 */
abstract class Pattern extends Object {


    /** @var string */
    protected $name;

    /** @var mixed */
    public $value;

    /** @var Pattern[] */
    public $children = [];


    /**
     * @param  string[] $types [optional]
     *
     * @return Pattern[]
     */
    abstract function flat(array $types = []);


    /**
     * @param  Pattern[] $left
     * @param  Pattern[] $collected [optional]
     *
     * @return mixed[]
     */
    abstract function match(array $left, array $collected = []);


    /**
     * @return string
     */
    public function name() {
        return $this->name;
    }


    /**
     * @return string
     */
    public function __toString() {
        return serialize($this);
    }


    /**
     * @return string
     */
    public function hash() {
        return (string) crc32((string)$this);
    }


    /**
     * @return $this
     */
    public function fix() {
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
    public function fixIdentities($unique = null) {
        if ($this->children) {
            if (!isset($unique))
                $unique = array_unique($this->flat());

            foreach ($this->children as $i => $child) {
                if (!$child instanceof BranchPattern) {
                    if (!in_array($child, $unique))               // Not sure if this is a true substitute for 'assert c in uniq'
                        throw new \UnexpectedValueException();
                    $this->children[$i] = $unique[array_search($child, $unique)];
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
    public function fixRepeatingArguments() {
        $either = [];
        foreach (Handler::transform($this)->children as $child) {
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
                    elseif (!is_array($e->value) && !$e->value instanceof \Traversable) {
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
}
