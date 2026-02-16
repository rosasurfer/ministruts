#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\toString;

is_file($autoload = __DIR__.'/../../../../vendor/autoload.php') || die("Error: file \"$autoload\" not found".PHP_EOL);
require $autoload;

$doc = <<<'DOCOPT'
Usage:
  {:cmd:}  tcp <host> <port> [--timeout=<seconds>]
  {:cmd:}  serial <port> [--baud=9600] [--timeout=<seconds>]
  {:cmd:}  -h | --help | --version

DOCOPT;

$docoptResult = docopt($doc, ['version'=>'0.1.1rc']);
echof(toString(json_encode($docoptResult)));
