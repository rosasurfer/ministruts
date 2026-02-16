#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\toString;

is_file($autoload = __DIR__.'/../../../../vendor/autoload.php') || die("Error: file \"$autoload\" not found".PHP_EOL);
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

$docoptResult = docopt($doc, ['version'=>'1.0.0rc2']);
echof(toString(json_encode($docoptResult)));
