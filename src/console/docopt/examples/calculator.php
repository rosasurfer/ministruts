#!/usr/bin/env php
<?php
use function rosasurfer\echof;
use function rosasurfer\docopt;

require(dirname(realpath(__FILE__)).'/../../../../src/load.php');

$doc = <<<DOCOPT
Not a serious example.

Usage:
  {:cmd:}  <value> ( ( + | - | * | / ) <value> )...
  {:cmd:}  <function> <value> [( , <value> )]...
  {:cmd:}  (-h | --help)

Examples:
  {:cmd:}  1 + 2 + 3 + 4 + 5
  {:cmd:}  1 + 2 '*' 3 / 4 - 5         # note quotes around '*'
  {:cmd:}  sum 10 , 20 , 30 , 40

Options:
  -h, --help

DOCOPT;

$result = docopt($doc);
foreach ($result as $key => $value) {
    echof($key.': '.json_encode($value));
}
