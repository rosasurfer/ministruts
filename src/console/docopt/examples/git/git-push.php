#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\toString;

is_file($autoload = __DIR__.'/../../../../../vendor/autoload.php') || die("Error: file \"$autoload\" not found".PHP_EOL);
require $autoload;

$doc = <<<'DOCOPT'
Usage: git push [options] [<repository> [<refspec>...]]

    -h, --help
    -v, --verbose          be more verbose
    -q, --quiet            be more quiet
    --repo <repository>    repository
    --all                  push all refs
    --mirror               mirror all refs
    --delete               delete refs
    --tags                 push tags (can't be used with --all or --mirror)
    -n, --dry-run          dry run
    --porcelain            machine-readable output
    -f, --force            force updates
    --thin                 use thin pack
    --receive-pack <receive-pack>
                           receive pack program
    --exec <receive-pack>  receive pack program
    -u, --set-upstream     set upstream for git pull/status
    --progress             force progress reporting

DOCOPT;

$docoptResult = docopt($doc);
echof(toString(json_encode($docoptResult)));
