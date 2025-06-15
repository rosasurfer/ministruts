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
Usage: git commit [options] [--] [<filepattern>...]

    -h, --help
    -q, --quiet            suppress summary after successful commit
    -v, --verbose          show diff in commit message template

Commit message options
    -F, --file <file>      read message from file
    --author <author>      override author for commit
    --date <date>          override date for commit
    -m, --message <message>
                           commit message
    -c, --reedit-message <commit>
                           reuse and edit message from specified commit
    -C, --reuse-message <commit>
                           reuse message from specified commit
    --fixup <commit>       use autosquash formatted message to fixup specified commit
    --squash <commit>      use autosquash formatted message to squash specified commit
    --reset-author         the commit is authored by me now
                           (used with -C-c/--amend)
    -s, --signoff          add Signed-off-by:
    -t, --template <file>  use specified template file
    -e, --edit             force edit of commit
    --cleanup <default>    how to strip spaces and #comments from message
    --status               include status in commit message template

Commit contents options
    -a, --all              commit all changed files
    -i, --include          add specified files to index for commit
    --interactive          interactively add files
    -o, --only             commit only specified files
    -n, --no-verify        bypass pre-commit hook
    --dry-run              show what would be committed
    --short                show status concisely
    --branch               show branch information
    --porcelain            machine-readable output
    -z, --null             terminate entries with NUL
    --amend                amend previous commit
    --no-post-rewrite      bypass post-rewrite hook
    -u, --untracked-files=<mode>
                           show untracked files, optional modes: all, normal, no.
                           [default: all]

DOCOPT;

$result = docopt($doc);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value, JSON_THROW_ON_ERROR).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}
