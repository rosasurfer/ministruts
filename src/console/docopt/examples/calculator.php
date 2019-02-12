#!/usr/bin/env php
<?php
use function rosasurfer\echoPre;
use function rosasurfer\docopt;

require(dirname(realpath(__FILE__)).'/../../../../etc/vendor/autoload.php');

$self = basename($_SERVER['PHP_SELF']);
$doc = <<<HELP
Not a serious example.

Usage:
  $self <value> ( ( + | - | * | / ) <value> )...
  $self <function> <value> [( , <value> )]...
  $self (-h | --help)

Examples:
  $self 1 + 2 + 3 + 4 + 5
  $self 1 + 2 '*' 3 / 4 - 5         # note quotes around '*'
  $self sum 10 , 20 , 30 , 40

Options:
  -h, --help

HELP;

$result = docopt($doc);
foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value));
}
echoPre($result->getArgs());