#!/usr/bin/env php
<?php
use function rosasurfer\echoPre;
use function rosasurfer\docopt;

require(dirname(realpath(__FILE__)).'/../../../../../etc/vendor/autoload.php');

$doc = <<<HELP
Usage: git remote [-v | --verbose]
       git remote add [-t <branch>] [-m <master>] [-f] [--mirror] <name> <url>
       git remote rename <old> <new>
       git remote rm <name>
       git remote set-head <name> (-a | -d | <branch>)
       git remote [-v | --verbose] show [-n] <name>
       git remote prune [-n | --dry-run] <name>
       git remote [-v | --verbose] update [-p | --prune] [(<group> | <remote>)...]
       git remote set-branches <name> [--add] <branch>...
       git remote set-url <name> <newurl> [<oldurl>]
       git remote set-url --add <name> <newurl>
       git remote set-url --delete <name> <url>

    -v, --verbose         be verbose; must be placed before a subcommand

HELP;

$result = docopt($doc);
foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value));
}
echoPre($result->getArgs());