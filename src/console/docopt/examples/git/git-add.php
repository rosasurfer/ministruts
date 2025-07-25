#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\json_encode;

if (!is_file($autoload = __DIR__.'/../../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require($autoload);

$doc = <<<DOCOPT
Usage: git add [options] [--] [<filepattern>...]

    -h, --help
    -n, --dry-run        dry run
    -v, --verbose        be verbose

    -i, --interactive    interactive picking
    -p, --patch          select hunks interactively
    -e, --edit           edit current diff and apply
    -f, --force          allow adding otherwise ignored files
    -u, --update         update tracked files
    -N, --intent-to-add  record only the fact that the path will be added later
    -A, --all            add all, noticing removal of tracked files
    --refresh            don't add, only refresh the index
    --ignore-errors      just skip files which cannot be added because of errors
    --ignore-missing     check if - even missing - files are ignored in dry run

DOCOPT;

$result = docopt($doc);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}
