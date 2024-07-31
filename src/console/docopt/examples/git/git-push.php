#!/usr/bin/env php
<?php
use function rosasurfer\echoPre;
use function rosasurfer\docopt;
use function rosasurfer\json_encode;

if (!is_file($autoload = __DIR__.'/../../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require($autoload);

$doc = <<<DOCOPT

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

$result = docopt($doc);

foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}
