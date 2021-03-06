#!/usr/bin/env php
<?php
use function rosasurfer\docopt;
use function rosasurfer\echoPre;

require(dirname(realpath(__FILE__)).'/../../../../src/load.php');

$self = basename($_SERVER['PHP_SELF']);
$doc = <<<DOCOPT

Example of a program with many options.

Usage:
  $self  [-hvqrf NAME] [--exclude=PATTERNS]
         [--select=ERRORS | --ignore=ERRORS] [--show-source]
         [--statistics] [--count] [--benchmark] PATH...
  $self  (--doctest | --testsuite=DIR)
  $self  --version

Arguments:
  PATH                 destination path

Options:
  -h --help            show this help message and exit
  --version            show version and exit
  -v --verbose         print status messages
  -q --quiet           report only file names
  -r --repeat          show all occurrences of the same error
  --exclude=PATTERNS   exclude files or directories which match these comma
                       separated patterns [default: .svn,CVS,.bzr,.hg,.git]
  -f NAME --file=NAME  when parsing directories, only check filenames matching
                       these comma separated patterns [default: *.php]
  --select=ERRORS      select errors and warnings (e.g. E,W6)
  --ignore=ERRORS      skip errors and warnings (e.g. E4,W)
  --show-source        show source code for each error
  --statistics         count errors and warnings
  --count              print total number of errors and warnings to stderr
                       and set exit code to 1 if total number is not 0
  --benchmark          measure processing speed
  --testsuite=DIR      run regression tests from dir
  --doctest            run doctest on myself

DOCOPT;

$result = docopt($doc, null, ['version'=>'1.0.0rc2']);
foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value));
}
