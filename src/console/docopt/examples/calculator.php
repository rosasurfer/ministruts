#!/usr/bin/env php
<?php
use function rosasurfer\echoPre;
use function rosasurfer\docopt;
use function rosasurfer\json_encode;

if (!is_file($autoload = __DIR__.'/../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require($autoload);

$self = basename($_SERVER['PHP_SELF']);
$doc = <<<DOCOPT

Not a serious example.

Usage:
  $self  <value> ( ( + | - | * | / ) <value> )...
  $self  <function> <value> [( , <value> )]...
  $self  (-h | --help)

Examples:
  $self  1 + 2 + 3 + 4 + 5
  $self  1 + 2 '*' 3 / 4 - 5         # note quotes around '*'
  $self  sum 10 , 20 , 30 , 40

Options:
  -h, --help

DOCOPT;

$result = docopt($doc);

foreach ($result as $key => $value) {
    echoPre($key.': '.json_encode($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}
