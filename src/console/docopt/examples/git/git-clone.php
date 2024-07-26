#!/usr/bin/env php
<?php
use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;

require(dirname(realpath(__FILE__)).'/../../../../../src/load.php');

$doc = <<<DOCOPT
Usage: git clone [options] [--] <repo> [<dir>]

    -v, --verbose          be more verbose
    -q, --quiet            be more quiet
    --progress             force progress reporting
    -n, --no-checkout      don't create a checkout
    --bare                 create a bare repository
    --mirror               create a mirror repository (implies bare)
    -l, --local            to clone from a local repository
    --no-hardlinks         don't use local hardlinks, always copy
    -s, --shared           setup as shared repository
    --recursive            initialize submodules in the clone
    --recurse-submodules   initialize submodules in the clone
    --template <template-directory>
                           directory from which templates will be used
    --reference <repo>     reference repository
    -o, --origin <branch>  use <branch> instead of 'origin' to track upstream
    -b, --branch <branch>  checkout <branch> instead of the remote's HEAD
    -u, --upload-pack <path>
                           path to git-upload-pack on the remote
    --depth <depth>        create a shallow clone of that depth

DOCOPT;

$result = docopt($doc);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}
