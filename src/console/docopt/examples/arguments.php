#!/usr/bin/env php
<?php
use function rosasurfer\echoPre;
use function rosasurfer\docopt;

require(dirname(realpath(__FILE__)).'/../../../../src/load.php');

$self = basename($_SERVER['PHP_SELF']);
$doc = <<<DOCOPT
Process FILE and optionally apply correction to either left-hand or right-hand side.

Usage: $self  [-vqrh] [FILE]...
       $self  (--left | --right) CORRECTION FILE

Arguments:
  FILE        optional input file
  CORRECTION  correction angle, needs FILE, --left or --right to be present

Options:
  -h --help
  -v          verbose mode
  -q          quiet mode
  -r          make report
  --left      use left-hand side
  --right     use right-hand side

DOCOPT;

$result = docopt($doc);
foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value));
}

