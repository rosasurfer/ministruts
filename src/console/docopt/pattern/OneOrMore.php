<?php
declare(strict_types=1);

namespace rosasurfer\ministruts\console\docopt\pattern;


/**
 * OneOrMore
 */
class OneOrMore extends BranchPattern {


    /**
     * {@inheritdoc}
     *
     * @param  Pattern[] $left
     * @param  Pattern[] $collected [optional]
     *
     * @return array{bool, Pattern[], Pattern[]}
     */
    public function match(array $left, array $collected = []) {
        if (sizeof($this->children) != 1) throw new \UnexpectedValueException();

        $l = $left;
        $c = $collected;

        $lnew = [];
        $matched = true;
        $times = 0;

        while ($matched) {
            // could it be that something didn't match but changed l or c?
            list ($matched, $l, $c) = $this->children[0]->match($l, $c);
            if ($matched) $times += 1;
            if ($lnew == $l) break;
            $lnew = $l;
        }

        if ($times >= 1) {
            return [true, $l, $c];
        }
        return [false, $left, $collected];
    }
}
