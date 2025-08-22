#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\json_encode_or_throw;

if (!is_file($autoload = __DIR__.'/../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require $autoload;

$doc = <<<'DOCOPT'
Example of a program which uses the [options] shortcut.

Usage: {:cmd:}  [options] <port>

Options:
  -h --help              show this help message and exit
  --version              show version and exit
  -n, --number N         use N as a number
  -t, --timeout TIMEOUT  set timeout TIMEOUT seconds
  --apply                apply changes to database
  -q                     operate in quiet mode

DOCOPT;

$result = docopt($doc, null, ['version'=>'1.0.0rc2']);

foreach ($result as $key => $value) {
    echof($key.': '.json_encode_or_throw($value).(($type=gettype($value))=='NULL' ? '':" ($type)"));
}
