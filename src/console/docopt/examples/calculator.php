#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;

if (!is_file($autoload = __DIR__.'/../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require($autoload);

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
    echof($key.': '.json_encode($value, JSON_THROW_ON_ERROR).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}
