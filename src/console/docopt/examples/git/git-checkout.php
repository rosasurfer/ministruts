#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;

if (!is_file($autoload = __DIR__.'/../../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require($autoload);

$doc = <<<DOCOPT
Usage: git checkout [options] <branch>
       git checkout [options] <branch> -- <file>...

    -q, --quiet            suppress progress reporting
    -b <branch>            create and checkout a new branch
    -B <branch>            create/reset and checkout a branch
    -l                     create reflog for new branch
    -t, --track            set upstream info for new branch
    --orphan <new branch>  new unparented branch
    -2, --ours             checkout our version for unmerged files
    -3, --theirs           checkout their version for unmerged files
    -f, --force            force checkout (throw away local modifications)
    -m, --merge            perform a 3-way merge with the new branch
    --conflict <style>     conflict style (merge or diff3)
    -p, --patch            select hunks interactively

DOCOPT;

$result = docopt($doc);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value, JSON_THROW_ON_ERROR).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}
