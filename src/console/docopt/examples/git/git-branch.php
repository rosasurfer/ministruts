#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;

if (!is_file($autoload = __DIR__.'/../../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require $autoload;

$doc = <<<'DOCOPT'
Usage: git branch [options] [-r | -a] [--merged=<commit> | --no-merged=<commit>]
       git branch [options] [-l] [-f] <branchname> [<start-point>]
       git branch [options] [-r] (-d | -D) <branchname>
       git branch [options] (-m | -M) [<oldbranch>] <newbranch>

Generic options:
    -h, --help
    -v, --verbose         show hash and subject, give twice for upstream branch
    -t, --track           set up tracking mode (see git-pull(1))
    --set-upstream        change upstream info
    --color=<when>        use colored output
    -r                    act on remote-tracking branches
    --contains=<commit>   print only branches that contain the commit
    --abbrev=<n>          use <n> digits to display SHA-1s

Specific git-branch actions:
    -a                    list both remote-tracking and local branches
    -d                    delete fully merged branch
    -D                    delete branch (even if not merged)
    -m                    move/rename a branch and its reflog
    -M                    move/rename a branch, even if target exists
    -l                    create the branch's reflog
    -f, --force           force creation (when already exists)
    --no-merged=<commit>  print only not merged branches
    --merged=<commit>     print only merged branches

DOCOPT;

$result = docopt($doc);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value, JSON_THROW_ON_ERROR).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}
