#!/usr/bin/env php
<?php
declare(strict_types=1);

use function rosasurfer\ministruts\docopt;
use function rosasurfer\ministruts\echof;
use function rosasurfer\ministruts\toString;

if (!is_file($autoload = __DIR__.'/../../../../vendor/autoload.php')) {
    echo "File \"$autoload\" not found".PHP_EOL;
    exit(1);
}
require $autoload;

$doc = <<<'DOCOPT'
Usage:
  {:cmd:}  tcp <host> <port> [--timeout=<seconds>]
  {:cmd:}  serial <port> [--baud=9600] [--timeout=<seconds>]
  {:cmd:}  -h | --help | --version

DOCOPT;

$docoptResult = docopt($doc, null, ['version'=>'0.1.1rc']);
echof(toString(json_encode($docoptResult)));
